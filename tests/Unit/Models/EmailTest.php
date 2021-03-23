<?php

namespace Tests\Unit\Models;

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function can_create_email()
    {
        $email = Email::factory()->create();

        $this->assertInstanceOf(Email::class, $email);
    }
}
