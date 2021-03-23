<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Email;
use App\Models\User;
use Tests\Feature\TestCase;

class FetchEmailsControllerTest extends TestCase
{
    /**
     * @test
     * @group get_emails
     */
    public function unauthenticated_user_cannot_fetch_their_emails()
    {
        $existing_emails = Email::factory()->count(5)->create();

        $this->get(route('emails.index'))->assertRedirect(route('login'));

        //send json requests
        $response = $this->json('GET', route('emails.index'));

        $response->assertStatus(401);
    }

    /**
     * @test
     * @group get_emails
     */
    public function user_can_fetch_their_paginated_emails()
    {
        $user = User::factory()->create();

        Email::factory()->count(5)->create();

        //send json requests
        $response = $this->actingAs($user, 'api')->json('GET', route('emails.index'));

        $response->assertStatus(200)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'emails' => [
                    'current_page', 'data', 'first_page_url', 'from', 'last_page',
                    'last_page_url', 'links' => [

                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     * @group get_emails
     */
    public function can_fetch_specific_number_per_page_of_emails()
    {
        $user = User::factory()->create();

        Email::factory()->count(15)->create();

        $per_page = 3;
        $current_page = 4;

        //send json requests
        $route = route('emails.index', [
            'page' => $current_page, 'per_page' => $per_page
        ]);
        $response = $this->actingAs($user, 'api')->json('GET', $route);

        $response->assertStatus(200)->assertJsonStructure([
            'status', 'message', 'error_code', 'data' => [
                'emails' => [
                    'current_page', 'data', 'first_page_url', 'from', 'last_page',
                    'last_page_url', 'links' => [

                    ],
                ],
            ],
        ]);

        $data = $response->json('data.emails');

        $this->assertEquals($current_page, $data['current_page']);
        $this->assertEquals($per_page, $data['per_page']);
    }
}
