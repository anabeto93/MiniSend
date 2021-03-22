<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Feature\TestCase;

class EmailVerificationTest extends TestCase
{
    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function guest_cannot_view_verification_notice()
    {
        $response = $this->get(route('verification.notice'));

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function unverified_authenticated_user_sees_verification_notice()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200)->assertViewIs('auth.verify');
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function verified_user_cannot_see_verification_notice()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertRedirect(route('home'));
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function verified_user_cannot_revisit_their_verification_route()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get($this->getUserVerificationRoute($user));
        $response->assertRedirect(route('home'));
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function guest_user_cannot_see_their_verification_notice()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->get($this->getUserVerificationRoute($user));

        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @group email_verification
     * @group auth
    */
    public function authenticated_user_cannot_verify_others()
    {
        $unverified_user1 = User::factory()->create([
            'id' => 1,
            'email_verified_at' => null,
        ]);

        $unverified_user2 = User::factory()->create([
            'id' => 2,
            'email_verified_at' => null,
        ]);

        //get verification of user2
        $verification_route = $this->getUserVerificationRoute($unverified_user2);

        //user 1 cannot verify user2
        $response = $this->actingAs($unverified_user1)->get($verification_route);

        $response->assertForbidden();
        //also ensure user2 did not get verified
        $this->assertFalse($unverified_user2->fresh()->hasVerifiedEmail());
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function user_cannot_visit_invalid_verification_route()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $invalid_route = $this->getInvalidUserVerificationRoute($user);

        $response = $this->get($invalid_route);
        $response->assertRedirect(route('login'));
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function user_can_verify_themselves()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->assertNull($user->email_verified_at);

        $response = $this->actingAs($user)->get($this->getUserVerificationRoute($user));

        $response->assertRedirect(route('home'));

        //ensure they were really verified
        $user = $user->fresh();

        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function user_can_request_verification_email_be_resent()
    {
        Notification::fake();
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->from(route('verification.notice'))->post(route('verification.resend'));

        Notification::assertSentTo($user, VerifyEmail::class);
        $response->assertRedirect(route('verification.notice'));
    }

    /**
     * @test
     * @group email_verification
     * @group auth
     */
    public function guest_user_cannot_request_resend_of_verification_email()
    {
        $response = $this->post(route('verification.resend'));

        $response->assertRedirect(route('login'));
    }

    /**
     * @param User $user
     * @return string
     */
    protected function getUserVerificationRoute(User $user)
    {
        return URL::signedRoute('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);
    }

    /**
     * @param User $user
     * @return string
     */
    protected function getInvalidUserVerificationRoute(User $user)
    {
        return route('verification.verify', [
            'id' => $user->id,
            'hash' => 'attemptedHashingSomething'
        ]);
    }
}
