<?php

namespace App\Policies;

use App\Models\ConflictOfInterest;
use App\Models\Evaluation;
use App\Models\JudgeAssignment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class JudgePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can evaluate a specific project.
     * Regardless of being owner, manager or collaborator, the user MUST be explicitly assigned as judge
     * and MUST NOT have a declared conflict of interest.
     */
    public function evaluateProject(User $user, Project $project): bool
    {
        // 1. Check valid assignment
        $isAssigned = JudgeAssignment::where('hackathon_id', $project->hackathon_id)
            ->where('user_id', $user->id)
            ->where(function ($q) use ($project) {
                $q->whereNull('project_id')->orWhere('project_id', $project->id);
            })
            ->exists();

        if (!$isAssigned) {
            return false;
        }

        // 2. Check conflict of interest
        $hasConflict = ConflictOfInterest::where('hackathon_id', $project->hackathon_id)
            ->where('user_id', $user->id)
            ->where(function ($q) use ($project) {
                $q->where('project_id', $project->id)
                  ->orWhere('team_id', $project->team_id);
            })
            ->exists();

        if ($hasConflict) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can modify an existing evaluation.
     * Submitted evaluations cannot be altered once finalized.
     */
    public function updateEvaluation(User $user, Evaluation $evaluation): bool
    {
        if ($evaluation->user_id !== $user->id) {
            return false;
        }

        // Finalized evaluations cannot be changed unless permitted
        if ($evaluation->status === 'submitted') {
            return false;
        }

        return true;
    }
}
