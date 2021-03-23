<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\Feature\TestCase;

class ViewEmailControllerTest extends TestCase
{
    /**
     * @test
     * @group view_email
     */
    public function unauthenticated_user_cannot_view_emails()
    {
        $ghost_id = Str::uuid()->toString();
        $response = $this->get(route('emails.show', ['email' => $ghost_id]));

        $response->assertRedirect(route('login'));

        //through API
        $response = $this->json('GET', route('api.emails.show', ['email' => $ghost_id]));
        $response->assertStatus(401);
    }

    /**
     * @test
     * @group view_email
     */
    public function user_cannot_view_non_existent_email()
    {
        $user = User::factory()->create([ 'email_verified_at' => now() ]);

        $ghost_email = Str::uuid()->toString();

        $response = $this->actingAs($user)->get(route('emails.show', ['email' => $ghost_email]));

        $response->assertStatus(404);

        //through API
        $response = $this->actingAs($user, 'api')->json('GET', route('api.emails.show', ['email' => $ghost_email]));

        $response->assertStatus(404)->assertJsonStructure([
            'status', 'message', 'error_code',
        ]);
    }

    /**
     * @test
     * @group view_email
     */
    public function user_can_view_email_details()
    {
        $user = User::factory()->create([ 'email_verified_at' => now() ]);
        $email = Email::factory()->create();

        $response = $this->actingAs($user)->from(route('home'))->get(route('emails.show', ['email' => $email->uuid]));

        $response->assertStatus(200)->assertSee([
            $email->email, $email->text_content,
        ]);

        //through API
        $response = $this->actingAs($user, 'api')->json('GET', route('api.emails.show', ['email' => $email->uuid]));

        $response->assertStatus(200)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'email',
            ]
        ]);
    }
}
