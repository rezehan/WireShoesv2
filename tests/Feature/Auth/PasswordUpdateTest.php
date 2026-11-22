<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_is_updated_in_firebase(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1', 'password' => null]);
        $this->partialMock(FirebaseIdentity::class)->shouldReceive('reauthenticate')->once()->andReturn('test-token');
        $this->fakeFirebaseAdmin()->shouldReceive('changeUserPassword')->once()
            ->with('customer-1', 'new-firebase-password')->andReturn($this->firebaseRecord('customer-1', $user->email));

        $this->actingAsFirebase($user)->from('/profile')->put('/password', [
            'current_password' => 'firebase-password',
            'password' => 'new-firebase-password',
            'password_confirmation' => 'new-firebase-password',
        ])->assertRedirect('/profile')->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->password);
    }

    public function test_wrong_password_does_not_change_firebase_account(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1']);
        $this->partialMock(FirebaseIdentity::class)->shouldReceive('reauthenticate')->once()
            ->andThrow(ValidationException::withMessages(['current_password' => 'Email atau password salah.']));
        $this->fakeFirebaseAdmin()->shouldNotReceive('changeUserPassword');

        $this->actingAsFirebase($user)->from('/profile')->put('/password', [
            'current_password' => 'wrong',
            'password' => 'new-firebase-password',
            'password_confirmation' => 'new-firebase-password',
        ])->assertRedirect('/profile')->assertSessionHasErrors('current_password');
    }
}
