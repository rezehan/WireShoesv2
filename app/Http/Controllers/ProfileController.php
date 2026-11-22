<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\FirebaseIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => true,
            'status' => session('status'),
        ]);
    }

    public function update(ProfileUpdateRequest $request, FirebaseIdentity $firebase): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $email = Str::lower($data['email']);
        $emailChangeRequested = $email !== $user->email;

        if ($emailChangeRequested) {
            if ($firebase->emailInUse($user, $email)) {
                throw ValidationException::withMessages(['email' => 'Email sudah dipakai.']);
            }

            if (empty($data['current_password'])) {
                throw ValidationException::withMessages(['current_password' => 'Masukkan password sekarang untuk mengganti email.']);
            }

            $token = $firebase->reauthenticate($user, $data['current_password']);
            $firebase->sendEmailChange($email, $token);
        }

        $user->update(['name' => $data['name']]);

        return redirect()->route('profile.edit')->with('status', $emailChangeRequested ? 'email-change-requested' : null);
    }

    public function destroy(Request $request, FirebaseIdentity $firebase): RedirectResponse
    {
        $data = $request->validate(['current_password' => ['required', 'string', 'max:4096']]);
        $user = $request->user();

        $firebase->reauthenticate($user, $data['current_password']);

        DB::transaction(function () use ($user, $firebase): void {
            $user->delete();

            try {
                $firebase->admin()->deleteUser($user->firebase_uid);
            } catch (Throwable $error) {
                throw $firebase->unavailable($error);
            }
        });

        $user->setRememberToken(null);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
