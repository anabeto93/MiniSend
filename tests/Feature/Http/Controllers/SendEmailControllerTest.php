<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Email;
use App\Models\User;
use App\Jobs\SendEmailJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\TestCase;

class SendEmailControllerTest extends TestCase
{
    /**
     * @test
     * @group send_email
     */
    public function only_authenticated_user_can_send_email()
    {
        $response = $this->json('POST', route('emails.send'));

        $response->assertStatus(401)->assertJsonStructure([
            'status', 'message', 'error_code'
        ]);
    }

    public function invalidEmailDataProvider(): array
    {
        $valid = [
            "sender" => "boehm.genoveva@example.com",
            "recipients" => "eleonore.murphy@example.org",
            "subject" => "Voluptas provident nam perferendis maiores rerum consequatur.",
            "text_content" => "Saepe accusamus natus cumque provident minima sit et. Dolor aliquid labore aut. Et laboriosam deleniti iure.",
            "html_content" => "<html>Simple Content</html>",
             "attachments" => [],
        ];

        $keys = ['recipients', 'subject'];

        $final = [];

        foreach ($keys as $key) {
            $missing = $valid;
            unset($missing[$key]);

            $final["Missing " . $key] = ["data" => $missing];
        }

        //empty both text and html contents
        $missing = $valid;
        unset($missing['text_content']);
        unset($missing['html_content']);

        $final["Missing Content"] = ['data' => $missing];

        //Invalid Attachments
        $invalid = $valid;
        $invalid['attachments'] = "Gibberish";

        $final["Invalid Attachments"] = ['invalid' => $invalid];

        return $final;
    }

    /**
     * @test
     * @group send_email
     * @dataProvider invalidEmailDataProvider
     * @param array $payload
     */
    public function cannot_send_emails_with_invalid_content(array $payload)
    {
        $user = User::factory()->create();

        $response = $this->from(route('home'))->actingAs($user, 'api')->json('POST', route('emails.send'), $payload);

        $response->assertStatus(422)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'errors' => [],
            ],
        ]);
    }

    /**
     * @test
     * @group send_email
     */
    public function authenticated_user_can_send_email()
    {
        $user = User::factory()->create([ 'email' => 'auth@user.com' ]);

        $valid = Email::factory()->make([ 'from' => $user->email, ])->toArray();
        $valid['sender'] = $valid['from'];
        $valid['recipients'] = [ $valid['to'], ];
        unset($valid['attachments']);

        $response = $this->from(route('home'))->actingAs($user, 'api')->json('POST', route('emails.send'), $valid);

        $response->assertStatus(201)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [],
        ]);

        $emails = Email::all()->toArray();

        $this->assertCount(1, $emails);
        $this->assertEquals($user->email, $emails[0]['from']);
    }

    /**
     * @test
     * @group send_email
     */
    public function only_specific_files_can_be_sent_as_attachments()
    {
        $user = User::factory()->create([ 'email' => 'auth@user.com' ]);

        $valid = Email::factory()->make([ 'from' => $user->email, 'attachments' => null,])->toArray();
        $valid['sender'] = $valid['from'];
        $valid['recipients'] = [ $valid['to'], ];

        Storage::fake('attachments');

        $valid['attachments'] = [
            UploadedFile::fake()->create('file' . rand(1, 100), 1),
            UploadedFile::fake()->create('file' . rand(1, 100), 1),
            UploadedFile::fake()->create('file' . rand(1, 100), 1),
        ];

        $response = $this->from(route('home'))->actingAs($user, 'api')->json('POST', route('emails.send'), $valid);

        $response->assertStatus(422)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'errors' => [],
            ],
        ]);
    }

    public function mimeTypeProvider(): array
    {
        return [
            "CSV" => ["csv"],
            "TXT" => ["txt"],
            "XLS" => ["xls"],
            "XLSX" => ["xlsx"],
            "DOC" => ["doc"],
            "DOCX" => ["docx"],
            "PDF" => ["pdf"],
            "JPEG" => ["jpeg"],
            "PNG" => ["png"],
            "JPG" => ["jpg"],
        ];
    }

    /**
     * @test
     * @group send_email
     * @dataProvider mimeTypeProvider
     * @param string $mime
     */
    public function authenticated_user_can_send_attachments(string $mime)
    {
        $user = User::factory()->create([ 'email' => 'auth@user.com' ]);

        $valid = Email::factory()->make([ 'from' => $user->email, 'attachments' => ['hello.txt'],])->toArray();

        $valid['sender'] = $user->email;
        $valid['recipients'] = [ $valid['to'], ];
        unset($valid['attachments']);

        Storage::fake();

        $valid['attachments'] = [
            UploadedFile::fake()->create('file_' . rand(1, 100) . "." . $mime),
        ];

        $response = $this->from(route('home'))->actingAs($user, 'api')->json('POST', route('emails.send'), $valid);

        $response->assertStatus(201)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [],
        ]);

        $emails = Email::all()->toArray();

        $this->assertCount(1, $emails);
        $this->assertEquals($user->email, $emails[0]['from']);

        foreach ($valid['attachments'] as $attachment) {
            $timestamp = $timestamp = implode("_", explode("-", str_replace(" ", "-", str_replace(":", "-", now()->toDateTimeString()))));
            $name = $timestamp . "_" . $attachment->getClientOriginalName();

            Storage::assertExists('attachments/' . $name);
        }
    }

    /**
     * @test
     * @group send_email
     */
    public function email_job_is_dispatched_to_send_emails()
    {
        Bus::fake();

        $user = User::factory()->create([ 'email' => 'auth@user.com' ]);

        $valid = Email::factory()->make([ 'from' => $user->email, ])->toArray();
        $valid['sender'] = $user->email;
        $valid['recipients'] = [ $valid['to'], ];
        unset($valid['attachments']);

        $response = $this->from(route('home'))->actingAs($user, 'api')->json('POST', route('emails.send'), $valid);

        $response->assertStatus(201)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [],
        ]);

        $email = Email::where('from', $user->email)->first();

        Bus::assertDispatched(SendEmailJob::class, function ($job) use ($email) {
            return $job->email_id === $email->uuid;
        });
    }

    public function emailContentTypeProvider(): array
    {
        return [
            'Text Content' => ['text' => true, 'content' => false,],
            'Html Content' => ['text' => false, 'content' => true,],
        ];
    }

    /**
     * @test
     * @group send_email
     * @dataProvider emailContentTypeProvider
     * @param bool $using_text
     * @param bool $using_html
     */
    public function email_status_is_updated_when_delivered_or_failed(bool $using_text, bool $using_html)
    {
        $user = User::factory()->create([ 'email' => 'auth@user.com' ]);

        $valid = Email::factory()->make([ 'from' => $user->email, ])->toArray();
        $valid['sender'] = $user->email;
        $valid['recipients'] = [ $valid['to'], ];
        unset($valid['status']);
        unset($valid['attachments']);

        if ($using_html) {
            unset($valid['text_content']);
        }

        if ($using_text) {
            unset($valid['html_content']);
        }

        Storage::fake();

        $valid['attachments'] = [
            UploadedFile::fake()->create('file_' . rand(1, 100) . ".csv"),
        ];

        $response = $this->from(route('home'))->actingAs($user, 'api')->json('POST', route('emails.send'), $valid);

        $response->assertStatus(201)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [],
        ]);

        $email = Email::where('from', $user->email)->first();
        $this->assertNotEquals('POSTED', $email->status);
        $this->assertEquals('SENT', $email->status);
    }

    /**
     * @test
     * @group send_email
     */
    public function only_the_number_of_emails_matching_recipients_are_sent()
    {
        $user = User::factory()->create([ 'email' => 'auth@user.com' ]);

        $valid = Email::factory()->make([ 'from' => $user->email, 'attachments' => ['hello.txt'],])->toArray();

        $recipients = ['first@there.com', 'second@there.com'];

        $valid['sender'] = $user->email;
        $valid['recipients'] = $recipients; //two recipients
        unset($valid['attachments']);

        Storage::fake();

        $valid['attachments'] = [
            UploadedFile::fake()->create('file_' . rand(1, 100) . ".csv"),
            UploadedFile::fake()->create('file_' . rand(1, 100) . ".pdf"),
            UploadedFile::fake()->create('file_' . rand(1, 100) . ".png"),
        ];

        $response = $this->from(route('home'))->actingAs($user, 'api')->json('POST', route('emails.send'), $valid);

        $response->assertStatus(201)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [],
        ]);

        $emails = Email::all()->toArray();

        $this->assertCount(count($recipients), $emails);
        $this->assertEquals($user->email, $emails[0]['from']);

        $attachments = [];

        foreach ($valid['attachments'] as $attachment) {
            $timestamp = $timestamp = implode("_", explode("-", str_replace(" ", "-", str_replace(":", "-", now()->toDateTimeString()))));
            $name = 'attachments/' . $timestamp . "_" . $attachment->getClientOriginalName();
            $attachments[] = $name;

            Storage::assertExists($name);
        }

        foreach ($recipients as $recipient) {
            $client_email = Email::where('to', $recipient)->first();

            $this->assertEquals($attachments, $client_email->attachments);
            $this->assertEquals($user->email, $client_email->from);
        }
    }
}
