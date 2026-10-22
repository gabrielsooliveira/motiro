<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'avatar',
        'bio',
        'phone',
        'email',
        'password',
        'city',
        'state',
        'education_institution',
        'education_course',
        'professional_experience',
        'skills',
        'technologies',
        'github_url',
        'linkedin_url',
        'portfolio_url',
        'website_url',
        'is_profile_public',
        'lgpd_consent_at',
        'terms_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->username)) {
                $user->username = static::generateUsername($user->name);
            }
        });
    }

    private static function generateUsername(?string $name): string
    {
        $base = Str::slug($name ?: 'user');
        $username = $base ?: 'user';
        $count = 1;

        while (static::where('username', $username)->exists()) {
            $username = $base . $count;
            $count++;
        }

        return $username;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'skills' => 'array',
            'technologies' => 'array',
            'is_profile_public' => 'boolean',
            'lgpd_consent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_user', 'user_id', 'organization_id')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }
}
