<?php

namespace Tests\Feature\Frontend;

use App\Domains\Auth\Models\User;
use App\Domains\Auth\Notifications\Frontend\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetPopupFeedbackTest extends TestCase
{
    private const CONFIRMATION = 'If an account exists with that email address, you will receive a password reset link shortly. Please check your inbox and spam folder.';

    public function test_popup_includes_accessible_confirmation_but_does_not_display_it_initially(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="password-reset-confirmation"', false)
            ->assertSee('data-password-reset-form', false)
            ->assertSee('role="status"', false)
            ->assertSee('Check your email')
            ->assertSee(self::CONFIRMATION)
            ->assertSee('frontend/js/popup-auth.js');
    }

    public function test_existing_account_receives_link_and_popup_gets_neutral_json_confirmation(): void
    {
        Notification::fake();
        $user = User::factory()->user()->create([
            'email' => 'reset-existing@example.test',
        ]);

        $this->postJson(route('frontend.auth.password.email'), [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertExactJson(['message' => __(self::CONFIRMATION)]);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_unknown_account_receives_the_exact_same_popup_acknowledgement(): void
    {
        Notification::fake();

        $this->postJson(route('frontend.auth.password.email'), [
            'email' => 'missing-reset-user@example.test',
        ])
            ->assertOk()
            ->assertExactJson(['message' => __(self::CONFIRMATION)]);

        Notification::assertNothingSent();
    }

    public function test_invalid_email_still_returns_inline_validation_error(): void
    {
        Notification::fake();

        $this->postJson(route('frontend.auth.password.email'), [
            'email' => 'not-an-email',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Notification::assertNothingSent();
    }

    public function test_full_page_password_reset_preserves_redirect_and_neutral_status(): void
    {
        Notification::fake();

        $this->from(route('frontend.auth.password.request'))
            ->post(route('frontend.auth.password.email'), [
                'email' => 'not-registered@example.test',
            ])
            ->assertRedirect(route('frontend.auth.password.request'))
            ->assertSessionHas('status', __(self::CONFIRMATION));

        Notification::assertNothingSent();
    }
}
