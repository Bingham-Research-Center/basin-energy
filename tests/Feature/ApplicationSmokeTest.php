<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    public function test_home_page_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_invalid_page_returns_404(): void
    {
        $response = $this->get('/this-page-does-not-exist');

        $response->assertStatus(404);
    }
}