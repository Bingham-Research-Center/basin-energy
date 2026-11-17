<?php

namespace Tests\Feature\Frontend;

use App\Domains\Auth\Models\User;
use Tests\TestCase;

class ConfirmPasswordPageTest extends TestCase
{
    public function test_guests_cannot_open_the_password_confirmation_page(): void
    {
        $this->get('/password/confirm')
            ->assertRedirect(route('frontend.auth.login'));
    }

    public function test_authenticated_user_sees_the_styled_password_confirmation_form(): void
    {
        $user = User::factory()->user()->create();
        $this->actingAs($user);

        $this->get('/password/confirm')
            ->assertOk()
            ->assertSee('frontend/css/auth-pages.css')
            ->assertSee('class="page-title bg-1"', false)
            ->assertSee('class="section auth-page"', false)
            ->assertSee('class="auth-card"', false)
            ->assertSee('Verify Your Identity')
            ->assertSee('Please confirm your password before continuing.')
            ->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('action="'.route('frontend.auth.password.confirm').'"', false)
            ->assertSee('Confirm Password');
    }
}
