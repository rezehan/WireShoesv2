<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Token\DataSet;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Token\Signature;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->withoutVite()->get('/login')->assertOk();
    }

    public function test_firebase_login_creates_a_laravel_session_for_staff(): void
    {
        $user = User::factory()->unverified()->create([
            'firebase_uid' => 'staff-1',
            'password' => null,
            'role' => User::ROLE_KASIR,
        ]);

        config(['services.firebase.api_key' => 'test-web-key']);
        Http::fake(['identitytoolkit.googleapis.com/*' => Http::response(['idToken' => 'test-token'])]);
        $this->fakeFirebaseAdmin()->shouldReceive('verifyIdToken')->once()->with('test-token')->andReturn(new Plain(
            new DataSet([], ''),
            new DataSet(['sub' => $user->firebase_uid, 'email' => $user->email, 'email_verified' => true], ''),
            new Signature('', ''),
        ));

        $this->post('/login', ['email' => $user->email, 'password' => 'firebase-password'])
            ->assertRedirect('/dashboard')
            ->assertSessionHas('firebase_uid', $user->firebase_uid);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(User::ROLE_KASIR, $user->fresh()->role);

        Http::assertSent(fn (Request $request) => $request['email'] === $user->email
            && $request['returnSecureToken'] === true
            && str_contains($request->url(), 'accounts:signInWithPassword?key=test-web-key'));
    }

    public function test_local_password_does_not_grant_access_when_firebase_rejects_it(): void
    {
        $user = User::factory()->create();
        config(['services.firebase.api_key' => 'test-web-key']);
        Http::fake(['identitytoolkit.googleapis.com/*' => Http::response([
            'error' => ['message' => 'INVALID_LOGIN_CREDENTIALS'],
        ], 400)]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_old_session_is_required_to_sign_in_through_firebase(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_linking_existing_staff_preserves_role_and_user_id(): void
    {
        $user = User::factory()->unverified()->create(['role' => User::ROLE_ADMIN]);
        $this->fakeFirebaseAdmin()->shouldReceive('getUserByEmail')->once()->with($user->email)
            ->andReturn($this->firebaseRecord('staff-1', $user->email, true));

        $this->artisan('firebase:link-user', ['email' => $user->email])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'firebase_uid' => 'staff-1',
            'password' => null,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create(['firebase_uid' => 'customer-1']);

        $this->actingAsFirebase($user)->post('/logout')
            ->assertRedirect('/')
            ->assertSessionMissing('firebase_uid');
        $this->assertGuest();
    }
}
