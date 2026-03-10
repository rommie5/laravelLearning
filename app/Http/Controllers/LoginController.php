<?php

namespace App\Http\Controllers;

use App\Notifications\FirstLoginOtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Services\AuditService;
use Inertia\Inertia;

class LoginController extends Controller
{
    private const OTP_TTL_MINUTES = 10;

    public function show()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account is deactivated.']);
            }

            if ($user->admin_force_logout) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your access has been temporarily blocked by an administrator.']);
            }

            $request->session()->regenerate();

            if ($user->otp_required && is_null($user->otp_verified_at)) {
                $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $sendState = 'An OTP code has been sent to your email.';
                try {
                    $user->update([
                        'otp_code' => Hash::make($code),
                        'otp_sent_at' => now(),
                        'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
                        'otp_attempts' => 0,
                        'otp_locked_until' => null,
                    ]);
                    $user->notify(new FirstLoginOtpNotification($code, self::OTP_TTL_MINUTES));
                } catch (\Throwable $e) {
                    Log::error('OTP email send failed during first login.', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'error' => $e->getMessage(),
                    ]);
                    $sendState = 'OTP page opened, but sending email failed. Click Resend OTP.';
                }

                return redirect()->route('otp.verify.show')->with('success', $sendState);
            }

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            AuditService::log('login', $user, null, [
                'user_agent' => $request->userAgent()
            ]);

            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        AuditService::log('logout');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
