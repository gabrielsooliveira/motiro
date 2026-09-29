<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ConflictOfInterest extends Model
{
    use HasUuids;

    protected $table = 'conflicts_of_interest';

    protected $fillable = [
        'hackathon_id',
        'user_id',
        'project_id',
        'team_id',
        'reason',
        'status',
    ];

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
