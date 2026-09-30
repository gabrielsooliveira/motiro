<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }
}
