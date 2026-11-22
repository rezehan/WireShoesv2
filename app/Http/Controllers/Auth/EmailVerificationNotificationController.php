<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FirebaseIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request, FirebaseIdentity $firebase): RedirectResponse
    {
        $user = $request->user();
        $record = $firebase->refresh($user);

        if ($user->hasVerifiedEmail()) {
            return $this->verifiedRedirect($request);
        }

        try {
            $firebase->admin()->sendEmailVerificationLink($record->email, null, 'id');
        } catch (Throwable $error) {
            throw $firebase->unavailable($error);
        }

        return back()->with('status', 'verification-link-sent');
    }

    public function refresh(Request $request, FirebaseIdentity $firebase): RedirectResponse
    {
        $user = $request->user();
        $firebase->refresh($user);

        if ($user->hasVerifiedEmail()) {
            return $this->verifiedRedirect($request);
        }

        return back()->with('status', 'verification-pending');
    }

    private function verifiedRedirect(Request $request): RedirectResponse
    {
        return redirect()->intended($request->user()->isStaf() ? route('dashboard', absolute: false) : '/');
    }
}
