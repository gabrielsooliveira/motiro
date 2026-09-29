<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriterion extends Model
{
    use HasUuids;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'hackathon_id',
        'name',
        'description',
        'weight',
        'min_score',
        'max_score',
        'is_required',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'min_score' => 'float',
            'max_score' => 'float',
            'is_required' => 'boolean',
        ];
    }

    public function hackathon()
    {
        return $this->belongsTo(Hackathon::class);
    }

    public function scores()
    {
        return $this->hasMany(EvaluationScore::class, 'criteria_id');
    }
}
