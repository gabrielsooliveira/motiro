<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HackathonRegistration extends Model
{
    use HasUuids;

    protected $fillable = [
        'hackathon_id',
        'team_id',
        'user_id',
        'status',
        'rejection_reason',
        'notes',
        'custom_answers',
        'submitted_documents',
        'approved_at',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'custom_answers' => 'array',
            'submitted_documents' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
