<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HackathonStage extends Model
{
    use HasUuids;

    protected $fillable = [
        'hackathon_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'order',
        'status',
        'rules',
        'allowed_actions',
        'visibility',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'rules' => 'array',
            'allowed_actions' => 'array',
            'attachments' => 'array',
        ];
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }
}
