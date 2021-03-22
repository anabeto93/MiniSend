<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\TestCase;

class LoginTest extends TestCase
{
    /**
     * @test
     * @group auth
     */
    public function user_can_view_login_form()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200)->assertViewIs('auth.login');
    }

    /**
     * @test
     * @group auth
     */
    public function authenticated_user_cannot_view_login_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('home'));
    }

    /**
     * @test
     * @group auth
     */
    public function user_can_login_using_correct_credentials()
    {
        $password = "veryStrongPassword";
        $email = "test@minisend.com";

        $user = User::factory()->create([
            'password' => Hash::make($password),
            'email' => $email,
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function invalidCredentialsProvider(): array
    {
        $user = [
            'email' => 'user@test.com',
            'password' => 'simplePassword',
        ];

        $keys = ['email', 'password'];

        $final = [];

        foreach ($keys as $key) {
            $missing = $user;

            unset($missing[$key]);

            $final["Missing " . ucfirst($key)] = ['data' => $missing,];
        }

        return $final;
    }

    /**
     * @test
     * @group auth
     * @dataProvider invalidCredentialsProvider
     * @param array $payload
     */
    public function cannot_login_with_incomplete_data(array $payload)
    {
        $response = $this->from(route('login'))->post(route('login'), $payload);

        $response->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /**
     * @test
     * @group auth
     */
    public function user_cannot_login_with_incorrect_password()
    {
        $user = [
            'email' => 'user@test.com',
            'password' => 'notHackablePassword',
        ];

        $user = User::factory()->create($user);

        $response = $this->from(route('login'))->post(route('login'), [
            'user' => $user->email,
            'password' => 'compromisedPassword',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    /**
     * @test
     * @group auth
     */
    public function cannot_login_with_non_existent_email()
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'user' => 'ghost@account.com',
            'password' => 'coldFingers',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    /**
     * @test
     * @group auth
     */
    public function authenticated_user_can_logout()
    {
        $auth_user = User::factory()->create();

        $response = $this->actingAs($auth_user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /**
     * @test
     * @group auth
     */
    public function guest_cannot_logout()
    {
        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
