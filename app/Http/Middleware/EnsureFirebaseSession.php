<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureFirebaseSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (! $user->firebase_uid || $request->session()->get('firebase_uid') !== $user->firebase_uid)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $next($request);
    }
}
