<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FirebaseIdentity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Throwable;

class PasswordController extends Controller
{
    public function update(Request $request, FirebaseIdentity $firebase): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:4096'],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();
        $firebase->reauthenticate($user, $data['current_password']);

        try {
            $firebase->admin()->changeUserPassword($user->firebase_uid, $data['password']);
        } catch (Throwable $error) {
            throw $firebase->unavailable($error);
        }

        return back();
    }
}
