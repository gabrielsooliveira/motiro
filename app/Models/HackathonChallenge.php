<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HackathonChallenge extends Model
{
    use HasUuids;

    protected $table = 'hackathon_challenges';

    protected $fillable = [
        'hackathon_id',
        'title',
        'description',
        'order',
        'requirements',
        'prize',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }
}
