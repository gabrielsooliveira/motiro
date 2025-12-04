<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{

    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name',
        'username',
        'bio',
        'phone',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // só gera se não foi definido manualmente
            if (empty($user->username)) {
                $user->username = static::generateUsername($user->name);
            }
        });
    }

    private static function generateUsername($name)
    {
        // 1) transforma o nome em slug
        $base = Str::slug($name);

        $username = $base;
        $count = 1;

        // 2) verifica se já existe
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
        ];
    }
}
