<?php

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Tests\Feature\TestCase;

class RegisterTest extends TestCase
{
    /**
     * @test
     * @group register
     * @group auth
     */
    public function user_can_view_registration_form()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200)->assertViewIs('auth.register');
    }

    /**
     * @test
     * @group register
     * @group auth
     */
    public function authenticated_user_cannot_view_registration_form()
    {
        $user = User::factory()->create([
            'email' => 'new@user.com',
        ]);

        $response = $this->actingAs($user)->get(route('register'));

        $response->assertRedirect(route('home'));
    }

    public function invalidUserProvider(): array
    {
        $valid = [
            'name' => 'Valid User',
            'email' => 'valid@email.com',
            'password' => 'validPassword',
            'password_confirmation' => 'validPassword',
        ];

        $keys = ['name', 'email', 'password', 'password_confirmation'];

        $final = [];

        foreach ($keys as $key) {
            $missing = $valid;
            $uk = ucfirst($key);

            unset($missing[$key]);

            $final["Missing " . $uk] = ['data' => $missing];

            //invalid email
            if ($key == "email") {
                $invalid = $valid;
                $invalid[$key] = "some_gibberish_that_is_not_an_email";

                $final["Invalid " . $uk] = ['invalid' => $invalid];

                $invalid = $valid;
                $invalid[$key] = "1234@1234@@";

                $final["Invalid " . $uk . " 2"] = ['invalid' => $invalid];
            }

            if ($key == "password") {
                //Password mismatched
                $mismatched = $valid;
                $mismatched["password_confirmation"] = "somethingDifferent";

                $final["Mismatched " . $uk] = ['mismatched' => $mismatched];
            }
        }

        return $final;
    }

    /**
     * @test
     * @group register
     * @group auth
     * @dataProvider invalidUserProvider
     * @param array $payload
     */
    public function cannot_register_with_invalid_data(array $payload)
    {
        $response = $this->from(route('register'))->post(route('register'), $payload);

        $response->assertRedirect(route('register'));

        $users = User::all()->toArray();

        $this->assertCount(0, $users);
        $this->assertGuest();
    }

    /**
     * @test
     * @group register
     * @group auth
     */
    public function user_can_register()
    {
        $name = "Richard Opoku";
        $email = "richard@minisend.com";
        $password = "veryStrongPassword1234";

        //Also an event is dispatched to acknowledge user registered
        Event::fake();

        $response = $this->post(route('register'), [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect(route('home'));
        $users = User::all()->toArray();
        $this->assertCount(1, $users);

        $user = User::latest()->first();
        $this->assertAuthenticatedAs($user);
        $this->assertEquals($name, $user->name);
        $this->assertEquals($email, $user->email);

        Event::assertDispatched(Registered::class, function ($event) use ($user) {
            return $event->user->id == $user->id;
        });
    }
}
