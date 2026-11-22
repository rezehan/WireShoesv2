<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FirebaseIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', ['status' => session('status')]);
    }

    public function store(Request $request, FirebaseIdentity $firebase): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        $firebase->sendPasswordReset($data['email']);

        return back()->with('status', 'Jika email terdaftar, Firebase akan mengirim tautan untuk mengatur ulang password.');
    }
}
