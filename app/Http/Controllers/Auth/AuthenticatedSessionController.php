<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\FirebaseIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request, FirebaseIdentity $firebase): RedirectResponse
    {
        $data = $request->validated();

        try {
            $identity = $firebase->signIn($data['email'], $data['password']);
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            throw $firebase->unavailable($error);
        }

        $user = User::where('firebase_uid', $identity['uid'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Akun Firebase ini belum terhubung ke WireShoes. Hubungi admin.',
            ]);
        }

        $firebase->updateLocalIdentity($user, $identity['email'], $identity['verified']);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('firebase_uid', $user->firebase_uid);

        return redirect()->intended($user->isStaf() ? route('dashboard', absolute: false) : '/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
