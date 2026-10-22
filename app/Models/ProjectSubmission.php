<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectSubmission extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'project_submissions';

    protected $fillable = [
        'project_id',
        'submitted_by',
        'version',
        'snapshot_data',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
