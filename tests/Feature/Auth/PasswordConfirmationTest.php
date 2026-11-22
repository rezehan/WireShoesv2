<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_firebase_handles_legacy_password_confirmation_and_reset_urls(): void
    {
        $this->get('/confirm-password')->assertNotFound();
        $this->get('/reset-password/laravel-token')->assertNotFound();
    }
}
