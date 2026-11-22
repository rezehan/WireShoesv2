<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Kreait\Firebase\Auth\UserRecord;
use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Exception\Auth\UserNotFound;
use Kreait\Laravel\Firebase\Facades\Firebase;
use RuntimeException;
use Throwable;

class FirebaseIdentity
{
    public const UNAVAILABLE = 'Firebase sedang tidak dapat dihubungi. Coba lagi nanti.';

    public function admin(): Auth
    {
        return Firebase::auth();
    }

    /** @return array{uid: string, email: string, verified: bool, token: string} */
    public function signIn(string $email, #[\SensitiveParameter] string $password, string $errorField = 'email'): array
    {
        $result = $this->request('signInWithPassword', [
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]);

        if ($result->failed()) {
            $error = $this->firebaseErrorCode($result->json('error.message'));

            if (in_array($error, ['INVALID_LOGIN_CREDENTIALS', 'INVALID_PASSWORD', 'EMAIL_NOT_FOUND', 'USER_DISABLED', 'INVALID_EMAIL'], true)) {
                throw ValidationException::withMessages([$errorField => 'Email atau password salah.']);
            }

            if ($error === 'TOO_MANY_ATTEMPTS_TRY_LATER') {
                throw ValidationException::withMessages([$errorField => 'Terlalu banyak percobaan. Coba lagi nanti.']);
            }

            Log::warning('Firebase login request rejected.', [
                'status' => $result->status(),
                'firebase_code' => $error ?? $this->firebaseErrorCode($result->json('error.status')),
                'firebase_reason' => $this->firebaseErrorCode($result->json('error.details.0.reason')),
            ]);

            if ($error === 'OPERATION_NOT_ALLOWED') {
                throw ValidationException::withMessages([
                    'firebase' => 'Aktifkan Email/Password di Firebase Authentication untuk memakai login ini.',
                ]);
            }

            throw new RuntimeException('Firebase sign-in failed.');
        }

        $token = $result->json('idToken');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Firebase returned no ID token.');
        }

        try {
            $claims = $this->admin()->verifyIdToken($token)->claims();
        } catch (Throwable $error) {
            Log::warning('Firebase login token verification failed.', ['exception_type' => $error::class]);

            throw $error;
        }

        $uid = $claims->get('sub');
        $email = $claims->get('email');

        if (! is_string($uid) || $uid === '' || ! is_string($email) || $email === '') {
            throw new RuntimeException('Firebase ID token is missing identity claims.');
        }

        return [
            'uid' => $uid,
            'email' => $email,
            'verified' => $claims->get('email_verified', false) === true,
            'token' => $token,
        ];
    }

    public function reauthenticate(User $user, #[\SensitiveParameter] string $password): string
    {
        try {
            $identity = $this->signIn($user->email, $password, 'current_password');
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            throw $this->unavailable($error);
        }

        if ($user->firebase_uid !== $identity['uid']) {
            throw ValidationException::withMessages([
                'current_password' => 'Identitas akun berubah. Keluar lalu masuk kembali.',
            ]);
        }

        return $identity['token'];
    }

    public function updateLocalIdentity(User $user, string $email, bool $verified): void
    {
        $email = Str::lower($email);

        if ($user->email !== $email && $this->emailInUse($user, $email)) {
            $user->forceFill(['email_verified_at' => null])->save();

            throw ValidationException::withMessages([
                'email' => 'Email Firebase ini sudah terhubung ke akun WireShoes lain. Hubungi admin.',
            ]);
        }

        $user->email = $email;
        $user->email_verified_at = $verified
            ? ($user->isDirty('email') ? now() : ($user->email_verified_at ?? now()))
            : null;

        $user->save();
    }

    public function emailInUse(User $user, string $email): bool
    {
        return User::query()
            ->whereKeyNot($user->getKey())
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->exists();
    }

    public function refresh(User $user): UserRecord
    {
        try {
            $record = $this->admin()->getUser($user->firebase_uid);
        } catch (UserNotFound) {
            abort(403, 'Akun Firebase sudah tidak tersedia. Hubungi admin.');
        } catch (Throwable $error) {
            throw $this->unavailable($error);
        }

        abort_if($record->disabled || ! $record->email, 403, 'Akun Firebase tidak aktif.');

        $this->updateLocalIdentity($user, $record->email, $record->emailVerified);

        return $record;
    }

    public function sendPasswordReset(string $email): void
    {
        try {
            $response = $this->request('sendOobCode', [
                'requestType' => 'PASSWORD_RESET',
                'email' => Str::lower($email),
            ]);

            if (! $response->successful() && $response->json('error.message') !== 'EMAIL_NOT_FOUND') {
                throw new RuntimeException('Firebase password reset request failed.');
            }
        } catch (Throwable $error) {
            throw $this->unavailable($error);
        }
    }

    public function sendEmailChange(string $email, #[\SensitiveParameter] string $token): void
    {
        try {
            $response = $this->request('sendOobCode', [
                'requestType' => 'VERIFY_AND_CHANGE_EMAIL',
                'newEmail' => $email,
                'idToken' => $token,
            ]);

            if ($response->successful()) {
                return;
            }

            if (in_array($response->json('error.message'), ['EMAIL_EXISTS', 'INVALID_EMAIL'], true)) {
                throw ValidationException::withMessages(['email' => 'Email tidak valid atau sudah dipakai.']);
            }

            throw new RuntimeException('Firebase email change failed.');
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            throw $this->unavailable($error);
        }
    }

    public function unavailable(Throwable $error): ValidationException
    {
        Log::warning('Firebase authentication operation failed.', ['exception_type' => $error::class]);

        return ValidationException::withMessages(['firebase' => self::UNAVAILABLE]);
    }

    private function firebaseErrorCode(mixed $value): ?string
    {
        if (is_string($value) && preg_match('/\A([A-Z][A-Z0-9_]{1,79})(?:\s*:.*)?\z/s', $value, $parts)) {
            return $parts[1];
        }

        return null;
    }

    private function request(string $action, #[\SensitiveParameter] array $data): Response
    {
        $key = config('services.firebase.api_key');

        if (! is_string($key) || trim($key) === '' || str_contains($key, '...')) {
            throw ValidationException::withMessages([
                'firebase' => 'Firebase Web API key belum diisi di konfigurasi Laravel.',
            ]);
        }

        return Http::acceptJson()
            ->withHeaders(['X-Firebase-Locale' => 'id'])
            ->connectTimeout(5)
            ->timeout(12)
            ->withQueryParameters(['key' => trim($key)])
            ->post("https://identitytoolkit.googleapis.com/v1/accounts:{$action}", $data);
    }
}
