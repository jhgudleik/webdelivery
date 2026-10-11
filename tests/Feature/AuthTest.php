<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Тесты аутентификации (web-интерфейс, сессии).
 *
 * Покрывает:
 * - доступ к страницам входа/регистрации гостю и авторизованному;
 * - успешный логин, регистрацию, логаут.
 */
class AuthTest extends TestCase
{
    /**
     * Гость может открыть страницу входа.
     */
    public function test_login_page_not_auth(): void
    {
        $this->get(route('login'))->assertOk();
    }

    /**
     * Гость может открыть страницу регистрации.
     */
    public function test_register_page_not_auth(): void
    {
        $this->get(route('register'))->assertOk();
    }

    /**
     * Авторизованный пользователь перенаправляется с /register.
     */
    public function test_register_page_redirects_when_auth(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertStatus(302);
    }

    /**
     * Авторизованный пользователь перенаправляется с /login.
     */
    public function test_login_page_redirects_when_auth(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertStatus(302);
    }

    /**
     * Успешный логин существующего пользователя.
     */
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

    /**
     * Регистрация нового пользователя.
     */
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
        $this->assertNotNull($user);
        $this->assertEquals($data['name'], $user->name);
        $this->assertEquals($data['email'], $user->email);
        $this->assertTrue(Hash::check($data['password'], $user->password));
    }

    /**
     * Логаут пользователя.
     */
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