<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureSessionIsActive
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && (!$user->is_active || $user->admin_force_logout)) {
            $message = $user->admin_force_logout
                ? 'Your access has been temporarily blocked by an administrator.'
                : 'Your account is deactivated. Please contact an administrator.';

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $message,
            ]);
        }

        return $next($request);
    }
}
