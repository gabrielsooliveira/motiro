<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class JudgeAssignment extends Model
{
    use HasUuids;

    protected $fillable = [
        'hackathon_id',
        'user_id',
        'project_id',
        'status',
    ];

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function judge()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
