<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Базовые smoke-тесты: проверяют, что основные страницы отдают 200.
 *
 * Эти тесты не проверяют бизнес-логику, только то, что роуты работают
 * и не выбрасывают 500. Запускаются на чистой БД (RefreshDatabase).
 */
class ExampleTest extends TestCase
{
    /**
     * GET / — главная страница.
     */
    public function test_home_page_returns_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    /**
     * GET /catalog — каталог товаров.
     */
    public function test_catalog_page_returns_successful_response(): void
    {
        $this->get('/catalog')->assertOk();
    }

    /**
     * GET /login — страница входа.
     */
    public function test_login_page_returns_successful_response(): void
    {
        $this->get('/login')->assertOk();
    }

    /**
     * GET /register — страница регистрации.
     */
    public function test_register_page_returns_successful_response(): void
    {
        $this->get('/register')->assertOk();
    }
}