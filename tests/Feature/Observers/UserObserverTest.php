<?php

namespace Tests\Feature\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\TestCase;

class UserObserverTest extends TestCase
{
    /**
     * @test
     * @group register
     * @group auth
     */
    public function an_api_token_is_created_when_a_user_is_created()
    {
        $name = "Richard Opoku";
        $email = "richard@minisend.com";
        $password = "veryStrongPassword1234";

        $payload = User::factory()->make([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'api_token' => null,
        ])->toArray();

        $payload = array_merge($payload, [
            'password' => Hash::make($password),
        ]);

        $user = User::create($payload);

        $this->assertNotNull($user->fresh()->api_token);
    }
}
