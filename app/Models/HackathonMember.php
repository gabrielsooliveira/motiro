<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HackathonMember extends Model
{
    use HasUuids;

    protected $table = 'hackathon_members';

    protected $fillable = [
        'hackathon_id',
        'user_id',
        'role',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
