<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\Feature\TestCase;

class ResetPasswordTest extends TestCase
{
    protected function home(): string
    {
        return route('home');
    }

    protected function getResetRoute(): string
    {
        return '/password/reset';
    }

    protected function getUserResetRoute(string $token): string
    {
        return route('password.reset', $token);
    }

    protected function getUserResetToken(User $user): string
    {
        return Password::broker()->createToken($user);
    }

    protected function getInvalidUserToken(): string
    {
        return "simple_invalid_token";
    }

    /**
     * @test
     * @group reset
     * @group auth
     */
    public function user_can_view_password_reset_form()
    {
        $user = User::factory()->create([ 'email' => 'test@user.com' ]);

        $token = $this->getUserResetToken($user);

        $response = $this->get($this->getUserResetRoute($token));

        $response->assertStatus(200)->assertViewIs('auth.passwords.reset')
            ->assertViewHas('token', $token);
    }

    /**
     * @test
     * @group reset
     * @group auth
     */
    public function authenticated_user_can_view_password_reset_form()
    {
        $user = User::factory()->create([ 'email' => 'test@user.com' ]);

        $token = $this->getUserResetToken($user);

        $response = $this->actingAs($user)->get($this->getUserResetRoute($token));

        $response->assertStatus(200)->assertViewIs('auth.passwords.reset')
            ->assertViewHas('token', $token);
    }

    public function invalidPasswordResetProvider(): array
    {
        $valid = [
            'email' => 'good@email.com',
            'password' => 'simplePass',
            'password_confirmation' => 'simplePass',
            'token' => 'to_be_generated'
        ];

        $keys = ['email', 'password', 'password_confirmation', 'token'];
        $final = [];

        foreach ($keys as $key) {
            $uk = ucwords(implode(" ", explode("_", $key)));

            $missing = $valid;
            unset($missing[$key]);

            $final["Missing " . $uk] = ['data' => $missing, true,];

            if ($key == 'token') {
                $missing[$key] = "This_Is_Just_Invalid";

                $final["Invalid " . $uk] = ['invalid' => $missing, false,];
            } elseif ($key == 'password') {
                //mismatch
                $mismatch = $valid;

                $mismatch['password_confirmation'] = "Something_Else";

                $final["Mismatched " . $uk] = ['mismatch' => $mismatch, true,];
            }
        }

        return $final;
    }

    /**
     * @test
     * @group reset
     * @group auth
     * @dataProvider invalidPasswordResetProvider
     * @param array $payload
     * @param bool $use_valid_token
     */
    public function user_cannot_reset_password_with_invalid_data(array $payload, bool $use_valid_token)
    {
        $props = [];

        foreach (['email', 'password'] as $key) {
            if (array_key_exists($key, $payload)) {
                $props[$key] = $key == 'password' ? Hash::make('old' . $payload[$key]) : 'old' . $payload[$key];
            }
        }

        $user = User::factory()->create($props);

        if ($use_valid_token) {
            $token = $this->getUserResetToken($user);
        } else {
            $token = $this->getInvalidUserToken();
        }

        $response = $this->from($this->getUserResetRoute($token))->post($this->getResetRoute(), $payload);

        $response->assertRedirect($this->getUserResetRoute($token));
        //ensure non of the user credentials changed
        $updated = $user->fresh();

        $this->assertEquals($updated->email, $user->email);

        if (array_key_exists('password', $payload)) {
            $this->assertTrue(Hash::check('old' . $payload['password'], $updated->password));
        }
        $this->assertGuest();
    }

    /**
     * @test
     * @group reset
     * @group auth
     */
    public function user_can_reset_password()
    {
        Event::fake();

        $user = User::factory()->create([
            'email' => 'valid@user.com',
            'password' => Hash::make('OldCompromisedPassword'),
        ]);

        $new_password = "cannotHackThisNewPassword";

        $response = $this->post($this->getResetRoute(), [
            'token' => $this->getUserResetToken($user),
            'email' => $user->email,
            'password' => $new_password,
            'password_confirmation' => $new_password,
        ]);

        $response->assertRedirect($this->home());
    }
}
