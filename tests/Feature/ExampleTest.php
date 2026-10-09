<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_page_returns_successful_response(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_catalog_page_returns_successful_response(): void
    {
        $this->get('/catalog')->assertOk();
    }

    public function test_login_page_returns_successful_response(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_register_page_returns_successful_response(): void
    {
        $this->get('/register')->assertOk();
    }
}
