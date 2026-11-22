<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->withoutVite()->get('/register')->assertOk();
    }

    public function test_registration_creates_a_linked_customer_without_local_password(): void
    {
        $email = 'pelanggan@example.com';
        $admin = $this->fakeFirebaseAdmin();
        $admin->shouldReceive('createUser')->once()->with([
            'email' => $email,
            'password' => 'firebase-password',
            'displayName' => 'Test User',
        ])->andReturn($this->firebaseRecord('customer-1', $email));
        $admin->shouldReceive('sendEmailVerificationLink')->once()->with($email, null, 'id');

        $this->post('/register', [
            'nama_lengkap' => 'Test User',
            'username' => 'customer_1',
            'email' => $email,
            'password' => 'firebase-password',
            'password_confirmation' => 'firebase-password',
            'alamat' => 'Jalan Test',
            'role' => 'admin',
        ])->assertRedirect('/login');

        $user = User::where('firebase_uid', 'customer-1')->firstOrFail();
        $this->assertSame(User::ROLE_PELANGGAN, $user->role);
        $this->assertNull($user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('customers', ['user_id' => $user->id, 'nama' => 'Test User']);
        $this->assertGuest();
    }
}
