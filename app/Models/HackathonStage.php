<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HackathonStage extends Model
{
    use HasUuids;

    protected $table = 'hackathon_stages';

    protected $fillable = [
        'hackathon_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'order' => 'integer',
        ];
    }

    public function hackathon(): BelongsTo
    {
        return $this->belongsTo(Hackathon::class, 'hackathon_id');
    }
}
