<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\TestCase;

class ForgotPasswordTest extends TestCase
{
    /**
     * @test
     * @group forgot
     * @group auth
     */
    public function user_can_view_password_reset_request_form()
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200)->assertViewIs('auth.passwords.email');
    }

    /**
     * @test
     * @group forgot
     * @group auth
     */
    public function authenticated_user_can_see_password_reset_request_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.request'));
        $response->assertStatus(200)->assertViewIs('auth.passwords.email');
    }

    public function invalidResetRequestProvider(): array
    {
        return [
            'No Email' => ['payload' => []],
            'Empty Email' => ['payload' => ['email' => null,]],
            'Invalid Email' => ['payload' => ['email' => 'joker@gotham']],
        ];
    }

    /**
     * @test
     * @group forgot
     * @group auth
     * @dataProvider invalidResetRequestProvider
     * @param array $payload
     */
    public function cannot_request_reset_link_with_invalid_email(array $payload)
    {
        $response = $this->from(route('password.email'))
            ->post(route('password.email'), $payload);

        $response->assertRedirect(route('password.email'))->assertSessionHasErrors('email');
    }

    /**
     * @test
     * @group forgot
     * @group auth
     */
    public function an_email_with_reset_link_is_sent_when_requested()
    {
        $email = "valid@user.com";
        $user = User::factory()->create([ 'email' => $email ]);

        Notification::fake();
        $response = $this->post(route('password.email'));
        $response->assertRedirect(route('password.email'));

        $tokens = DB::table('password_resets')->get()->toArray();
        $this->assertCount(1, $tokens);

        $token = $tokens[0];

        Notification::assertSentTo($user, ResetPassword::class, function ($notification, $channel) use ($token) {
            return Hash::check($notification->token, $token->token) === true;
        });
    }

    /**
     * @test
     * @group forgot
     * @group auth
     */
    public function unregistered_user_does_not_receive_email_upon_requesting_password_reset()
    {
        Notification::fake();
        $ghost = 'ghost@rider.com';

        $response = $this->from(route('password.email'))->post(route('password.email', [
            'email' => $ghost,
        ]));

        $response->assertRedirect(route('password.email'))->assertSessionHasErrors(['email']);

        Notification::assertNotSentTo(User::factory()->make(['email' => $ghost]), ResetPassword::class);
    }
}
