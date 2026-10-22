<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HackathonMember extends Model
{
    use HasUuids, SoftDeletes;

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

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
