<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'initials',
        'password',
        'is_active',
        'admin_force_logout',
        'two_factor_enabled',
        'otp_required',
        'otp_code',
        'otp_expires_at',
        'otp_sent_at',
        'otp_verified_at',
        'otp_attempts',
        'otp_locked_until',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
        'initials',
        'avatar_color',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin_force_logout' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'otp_required' => 'boolean',
            'otp_expires_at' => 'datetime',
            'otp_sent_at' => 'datetime',
            'otp_verified_at' => 'datetime',
            'otp_locked_until' => 'datetime',
        ];
    }

    /**
     * Get the user's avatar URL natively.
     */
    public function getAvatarUrlAttribute()
    {
        return $this->avatar ? asset('storage/avatars/' . $this->avatar) : null;
    }

    /**
     * Get exactly computed initials for the user.
     */
    public function getInitialsAttribute($value)
    {
        if (!empty($value)) {
            return strtoupper(substr($value, 0, 5));
        }

        $words = explode(' ', trim($this->name));
        $initials = substr($words[0], 0, 1);
        if (isset($words[1])) {
            $initials .= substr($words[1], 0, 1);
        }

        return strtoupper($initials);
    }

    /**
     * Generate consistent background color based on user ID hash.
     */
    public function getAvatarColorAttribute()
    {
        $colors = ['#1E3A8A', '#7C3AED', '#059669', '#DC2626', '#EA580C', '#2563EB', '#9333EA', '#0D9488'];
        return $colors[$this->id % count($colors)];
    }
}
