<?php

namespace Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_page_not_auth(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_register_page_not_auth(): void
    {
        $this->get(route('register'))->assertOk();
    }

    public function test_register_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertStatus(302);
    }

    public function test_login_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertStatus(302);
    }

    public function test_login(): void
    {
        $user = User::factory([
            'password' => Hash::make('password'),
            'email' => 'test' . rand(100, 10000) . '@mail.ru'
        ])->create();

        $this->assertFalse(Auth::check());

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $this->assertAuthenticated();
    }

    public function test_register(): void
    {
        $data = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $this->assertNull(
            User::query()->where('email', $data['email'])->first()
        );

        $response = $this->post(route('register'), $data);

        $response->assertStatus(302);

        $user = User::query()->where('email', $data['email'])->first();
        $this->assertNotNull(
            $user
        );

        $this->assertEquals($data['name'], $user->name);
        $this->assertEquals($data['email'], $user->email);
        $this->assertTrue(Hash::check($data['password'], $user->password));
    }

    public function test_logout(): void
    {
        $user = User::factory([
            'password' => Hash::make('password'),
            'email' => 'test' . rand(100, 10000) . '@mail.ru'
        ])->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $this->assertTrue(Auth::check());

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertStatus(302);

        $this->assertFalse(Auth::check());
    }
}
