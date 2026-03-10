<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->otp_required && is_null($user->otp_verified_at)) {
            if (!$user->otp_code || !$user->otp_expires_at) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'OTP session is invalid. Please login again.',
                ]);
            }

            return redirect()->route('otp.verify.show');
        }

        return $next($request);
    }
}
