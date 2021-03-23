<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @group user
     */
    public function user_model_can_generate_api_tokens()
    {
        $user = User::factory()->create();

        $original = $user->api_token;

        $this->assertIsString($new_token = $user->generateApiToken());
        $this->assertNotEquals($original, $new_token);
    }
}
