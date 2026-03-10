<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class OtpVerificationController extends Controller
{
    private const OTP_TTL_MINUTES = 10;
    private const OTP_RESEND_COOLDOWN_SECONDS = 60;
    private const OTP_MAX_ATTEMPTS = 3;
    private const OTP_LOCKOUT_MINUTES = 10;

    public function show()
    {
        $user = Auth::user();
        if (!$user || !$user->otp_required || !is_null($user->otp_verified_at)) {
            return redirect()->route('dashboard');
        }

        if (!$user->otp_code || !$user->otp_expires_at) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'No active OTP found. Please login again.',
            ]);
        }

        return Inertia::render('Auth/OtpVerify');
    }

    public function verify(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->otp_required || !is_null($user->otp_verified_at)) {
            return redirect()->route('dashboard');
        }

        if ($user->otp_locked_until && now()->lessThan($user->otp_locked_until)) {
            return back()->withErrors([
                'code' => 'Too many failed attempts. Please retry later.',
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (!$user->otp_code || !$user->otp_expires_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'No active OTP found. Please login again.',
            ]);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return back()->withErrors([
                'code' => 'OTP expired. Please request a new code.',
            ]);
        }

        if (!Hash::check($validated['code'], $user->otp_code)) {
            $attempts = (int) $user->otp_attempts + 1;
            $payload = ['otp_attempts' => $attempts];

            if ($attempts >= self::OTP_MAX_ATTEMPTS) {
                $payload['otp_locked_until'] = now()->addMinutes(self::OTP_LOCKOUT_MINUTES);
            }

            $user->update($payload);

            return back()->withErrors([
                'code' => $attempts >= self::OTP_MAX_ATTEMPTS
                    ? 'Too many failed attempts. Please retry later.'
                    : 'Invalid OTP code.',
            ]);
        }

        $user->update([
            'otp_required' => false,
            'otp_verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
            'otp_sent_at' => null,
            'otp_attempts' => 0,
            'otp_locked_until' => null,
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        AuditService::log('login', $user, null, [
            'user_agent' => $request->userAgent(),
            'otp_verified' => true,
        ]);

        return redirect()->intended(route('dashboard'))->with('success', 'OTP verified successfully.');
    }

    public function resend(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->otp_required || !is_null($user->otp_verified_at)) {
            return redirect()->route('dashboard');
        }

        if ($user->otp_locked_until && now()->lessThan($user->otp_locked_until)) {
            return back()->withErrors([
                'code' => 'Too many failed attempts. Please retry later.',
            ]);
        }

        if ($user->otp_sent_at && now()->diffInSeconds($user->otp_sent_at) < self::OTP_RESEND_COOLDOWN_SECONDS) {
            $seconds = self::OTP_RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($user->otp_sent_at);
            return back()->withErrors([
                'code' => "Please wait {$seconds} seconds before resending OTP.",
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp_code' => Hash::make($code),
            'otp_sent_at' => now(),
            'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
            'otp_attempts' => 0,
            'otp_locked_until' => null,
        ]);

        try {
            $user->notify(new \App\Notifications\FirstLoginOtpNotification($code, self::OTP_TTL_MINUTES));
        } catch (\Throwable $e) {
            return back()->withErrors([
                'code' => 'Failed to resend OTP email. Please try again.',
            ]);
        }

        return back()->with('success', 'A new OTP code has been sent to your email.');
    }
}
