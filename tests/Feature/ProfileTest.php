<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1']);
        $this->withoutVite()->actingAsFirebase($user)->get('/profile')->assertOk();
    }

    public function test_users_can_update_their_name_in_laravel(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1']);
        $this->actingAsFirebase($user)->patch('/profile', ['name' => 'Nama baru', 'email' => $user->email])
            ->assertRedirect('/profile')->assertSessionHasNoErrors();
        $this->assertSame('Nama baru', $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_change_requires_firebase_verification_before_it_is_saved_locally(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1']);
        $this->partialMock(FirebaseIdentity::class)->shouldReceive('reauthenticate')->once()->andReturn('test-token');
        config(['services.firebase.api_key' => 'test-web-key']);
        Http::fake(['identitytoolkit.googleapis.com/*' => Http::response([])]);

        $this->actingAsFirebase($user)->patch('/profile', [
            'name' => $user->name,
            'email' => 'baru@example.com',
            'current_password' => 'firebase-password',
        ])->assertRedirect('/profile')->assertSessionHas('status', 'email-change-requested');

        $this->assertSame($user->email, $user->fresh()->email);
        Http::assertSent(fn (Request $request) => $request['requestType'] === 'VERIFY_AND_CHANGE_EMAIL'
            && $request['newEmail'] === 'baru@example.com'
            && $request['idToken'] === 'test-token');
    }

    public function test_users_can_delete_their_account_after_authenticating_again(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1']);
        $this->partialMock(FirebaseIdentity::class)->shouldReceive('reauthenticate')->once()->andReturn('test-token');
        $this->fakeFirebaseAdmin()->shouldReceive('deleteUser')->once()->with('customer-1');

        $this->actingAsFirebase($user)->delete('/profile', ['current_password' => 'firebase-password'])
            ->assertRedirect('/');
        $this->assertGuest();
        $this->assertModelMissing($user);
    }

    public function test_wrong_password_does_not_delete_account(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1']);
        $this->partialMock(FirebaseIdentity::class)->shouldReceive('reauthenticate')->once()
            ->andThrow(ValidationException::withMessages(['current_password' => 'Email atau password salah.']));
        $this->fakeFirebaseAdmin()->shouldNotReceive('deleteUser');

        $this->actingAsFirebase($user)->from('/profile')->delete('/profile', ['current_password' => 'wrong'])
            ->assertRedirect('/profile')->assertSessionHasErrors('current_password');
        $this->assertModelExists($user);
    }
}
