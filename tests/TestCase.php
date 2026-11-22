<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract\Auth;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Mockery;
use Mockery\MockInterface;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsFirebase(User $user): static
    {
        return $this->actingAs($user->fresh())->withSession(['firebase_uid' => $user->firebase_uid]);
    }

    protected function fakeFirebaseAdmin(): MockInterface
    {
        $admin = Mockery::mock(Auth::class);
        Firebase::shouldReceive('auth')->andReturn($admin);

        return $admin;
    }

    protected function firebaseRecord(string $uid, string $email, bool $verified = false): UserRecord
    {
        return UserRecord::fromResponseData([
            'localId' => $uid,
            'createdAt' => '1700000000000',
            'email' => $email,
            'emailVerified' => $verified,
            'lastLoginAt' => '1700000000000',
        ]);
    }
}
