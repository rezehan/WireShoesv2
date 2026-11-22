<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $this->withoutVite()->get('/forgot-password')->assertOk();
    }

    public function test_firebase_sends_password_reset_link(): void
    {
        $email = 'pelanggan@example.com';
        config(['services.firebase.api_key' => 'test-web-key']);
        Http::fake(['identitytoolkit.googleapis.com/*' => Http::response([])]);

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $email])
            ->assertRedirect('/forgot-password')->assertSessionHas('status');

        Http::assertSent(fn (Request $request) => $request['requestType'] === 'PASSWORD_RESET'
            && $request['email'] === $email);
    }

    public function test_unknown_email_receives_same_response(): void
    {
        $email = 'unknown@example.com';
        config(['services.firebase.api_key' => 'test-web-key']);
        Http::fake(['identitytoolkit.googleapis.com/*' => Http::response([
            'error' => ['message' => 'EMAIL_NOT_FOUND'],
        ], 400)]);

        $this->from('/forgot-password')->post('/forgot-password', ['email' => $email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', 'Jika email terdaftar, Firebase akan mengirim tautan untuk mengatur ulang password.');
    }
}
