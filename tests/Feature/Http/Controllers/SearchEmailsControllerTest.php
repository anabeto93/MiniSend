<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Email;
use App\Models\User;
use Tests\Feature\TestCase;

class SearchEmailsControllerTest extends TestCase
{
    /**
     * @test
     * @group search_emails
     */
    public function unauthenticated_user_cannot_search_emails()
    {
        $existing_emails = Email::factory()->count(5)->create();

        foreach ($existing_emails as $email) {
            $this->get(route('emails.search', ['recipient' => $email->to]))
                ->assertRedirect(route('login'));

            //send json requests
            $response = $this->json('GET', route('emails.search', [
                'recipient' => $email->to,
            ]));

            $response->assertStatus(401);
        }
    }

    /**
     * @test
     * @group search_emails
     */
    public function cannot_search_without_specifying_parameters()
    {
        $user = User::factory()->create();
        $recipient = "no@params.com";

        Email::factory()->count(5)->create();

        $params = [/* No Parameters Specified */];

        $route = route('emails.search', $params);

        $response = $this->actingAs($user, 'api')->json('GET', $route);

        $response->assertStatus(422)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'errors' => [
                    'sender', 'recipient', 'subject',
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group search_emails
     */
    public function user_can_search_emails_by_recipient()
    {
        $user = User::factory()->create();
        $recipient = "test@user.com";

        $existing_emails = Email::factory()->count(5)->create([
            'to' => $recipient,
        ]);

        $route = route('emails.search', ['recipient' => $recipient]);

        $response = $this->actingAs($user, 'api')->json('GET', $route);
        $response->assertStatus(200)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'emails'
            ],
        ]);
    }

    /**
     * @test
     * @group search_emails
     */
    public function user_can_search_emails_by_sender()
    {
        $user = User::factory()->create();
        $recipient = "test@user.com";

        $existing_emails = Email::factory()->count(5)->create([
            'from' => $recipient,
        ]);

        $route = route('emails.search', ['sender' => $recipient]);

        $response = $this->actingAs($user, 'api')->json('GET', $route);
        $response->assertStatus(200)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'emails'
            ],
        ]);
    }

    /**
     * @test
     * @group search_emails
     */
    public function user_can_search_emails_by_subject()
    {
        $user = User::factory()->create();
        $recipient = "test@user.com";
        $common_subject = "Sweet Caroline";

        $existing_emails = Email::factory()->count(5)->create([
            'from' => $recipient,
            'subject' => $common_subject,
        ]);

        $route = route('emails.search', ['subject' => $common_subject]);

        $response = $this->actingAs($user, 'api')->json('GET', $route);
        $response->assertStatus(200)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'emails'
            ],
        ]);
    }

    public function emailSearchProvider(): array
    {
        return [
            'All parameters' => ['sender' => true, 'subject' => true, 'recipient' => true,],
            'Sender and Subject' => ['sender' => true, 'subject' => true, 'recipient' => false,],
            'Sender and Recipient' => ['sender' => true, 'subject' => false, 'recipient' => true,],
            'Subject and Recipient' => ['sender' => false, 'subject' => true, 'recipient' => true,],
        ];
    }

    /**
     * @test
     * @group search_emails
     * @dataProvider emailSearchProvider
     * @param bool $using_sender
     * @param bool $using_subject
     * @param bool $using_recipient
     */
    public function can_search_emails_on_a_combination_of_parameters(bool $using_sender, bool $using_subject, bool $using_recipient)
    {
        $count = 5;
        $user = User::factory()->create([
            'email' => 'admin@user.com',
        ]);

        $recipient = "recv@curl.com";
        $subject = "Important Subject";
        $sender = "common@sender.com";

        $existing_emails = Email::factory()->count($count)->create([
            'to' => $recipient,
            'subject' => $subject,
            'from' => $sender,
        ]);

        $params = [];

        if ($using_subject) {
            $params['subject'] = $subject;
        }

        if ($using_recipient) {
            $params['recipient'] = $recipient;
        }

        if ($using_sender) {
            $params['sender'] = $sender;
        }

        $route = route('emails.search', $params);

        $response = $this->actingAs($user, 'api')->json('GET', $route);
        $response->assertStatus(200)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [],
        ]);

        $this->assertCount($count, $response->json('data.emails'));
    }
}
