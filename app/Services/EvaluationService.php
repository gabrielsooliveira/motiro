<?php

namespace App\Services;

use App\Models\ConflictOfInterest;
use App\Models\Evaluation;
use App\Models\Hackathon;
use App\Models\JudgeAssignment;
use App\Models\Project;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EvaluationService
{
    /**
     * Distribute projects to judges based on hackathon strategy
     */
    public function distributeProjects(Hackathon $hackathon): void
    {
        $judges = $this->getHackathonJudges($hackathon);
        if ($judges->isEmpty()) {
            throw new Exception('Nenhum juiz cadastrado para este Hackathon.');
        }

        $projects = $hackathon->projects()
            ->whereIn('status', ['submitted', 'locked'])
            ->get();

        if ($projects->isEmpty()) {
            throw new Exception('Nenhum projeto elegível submetido para avaliação.');
        }

        $conflicts = ConflictOfInterest::where('hackathon_id', $hackathon->id)
            ->whereIn('status', ['declared', 'reviewed_by_admin'])
            ->get();

        DB::transaction(function () use ($hackathon, $judges, $projects, $conflicts) {
            // Delete pending non-started assignments
            JudgeAssignment::where('hackathon_id', $hackathon->id)
                ->where('status', 'assigned')
                ->delete();

            if ($hackathon->evaluation_distribution === 'all_to_all') {
                $this->distributeAllToAll($hackathon, $judges, $projects, $conflicts);
            } elseif ($hackathon->evaluation_distribution === 'n_evaluations_per_project') {
                $this->distributeTargeted($hackathon, $judges, $projects, $conflicts, $hackathon->evaluations_per_project_target ?? 3);
            }
        });
    }

    private function getHackathonJudges(Hackathon $hackathon): Collection
    {
        return User::whereHas('roles', function ($q) use ($hackathon) {
            $q->where('name', 'judge')
              ->where(function ($sub) use ($hackathon) {
                  $sub->where('model_has_roles.hackathon_id', $hackathon->id)
                      ->orWhere('model_has_roles.organization_id', $hackathon->organization_id);
              });
        })->get();
    }

    private function distributeAllToAll(Hackathon $hackathon, Collection $judges, Collection $projects, Collection $conflicts): void
    {
        foreach ($projects as $project) {
            foreach ($judges as $judge) {
                if ($this->hasConflict($judge->id, $project, $conflicts)) {
                    continue;
                }

                JudgeAssignment::firstOrCreate([
                    'hackathon_id' => $hackathon->id,
                    'user_id' => $judge->id,
                    'project_id' => $project->id,
                ]);
            }
        }
    }

    private function distributeTargeted(Hackathon $hackathon, Collection $judges, Collection $projects, Collection $conflicts, int $targetPerProject): void
    {
        // Track workload per judge
        $judgeWorkload = [];
        foreach ($judges as $judge) {
            $judgeWorkload[$judge->id] = JudgeAssignment::where('hackathon_id', $hackathon->id)
                ->where('user_id', $judge->id)
                ->count();
        }

        foreach ($projects as $project) {
            $currentAssigned = JudgeAssignment::where('project_id', $project->id)->count();
            $needed = max(0, $targetPerProject - $currentAssigned);

            if ($needed === 0) {
                continue;
            }

            // Filter judges without conflict
            $eligibleJudges = $judges->reject(fn($j) => $this->hasConflict($j->id, $project, $conflicts))
                ->sortBy(fn($j) => $judgeWorkload[$j->id] ?? 0);

            $assignedCount = 0;
            foreach ($eligibleJudges as $judge) {
                if ($assignedCount >= $needed) {
                    break;
                }

                $alreadyAssigned = JudgeAssignment::where('project_id', $project->id)
                    ->where('user_id', $judge->id)
                    ->exists();

                if (!$alreadyAssigned) {
                    JudgeAssignment::create([
                        'hackathon_id' => $hackathon->id,
                        'user_id' => $judge->id,
                        'project_id' => $project->id,
                        'status' => 'assigned',
                    ]);

                    $judgeWorkload[$judge->id] = ($judgeWorkload[$judge->id] ?? 0) + 1;
                    $assignedCount++;
                }
            }
        }
    }

    private function hasConflict(string $judgeId, Project $project, Collection $conflicts): bool
    {
        return $conflicts->contains(function ($conflict) use ($judgeId, $project) {
            return $conflict->user_id === $judgeId && (
                $conflict->project_id === $project->id ||
                ($conflict->team_id && $conflict->team_id === $project->team_id)
            );
        });
    }

    /**
     * Calculate and return ranked projects based on configured scoring algorithm
     */
    public function calculateRankings(Hackathon $hackathon): array
    {
        $projects = $hackathon->projects()
            ->with(['team', 'evaluations.scores.criterion'])
            ->get();

        $ranked = [];

        foreach ($projects as $project) {
            $submittedEvaluations = $project->evaluations->where('status', 'submitted');
            $evalCount = $submittedEvaluations->count();

            if ($evalCount === 0) {
                $ranked[] = [
                    'project' => $project,
                    'evaluations_count' => 0,
                    'final_score' => 0.0,
                    'scores_by_criteria' => [],
                ];
                continue;
            }

            $scores = $submittedEvaluations->pluck('final_score')->map(fn($s) => (float)$s)->values()->all();

            $finalScore = 0.0;
            if ($hackathon->scoring_algorithm === 'trimmed_mean' && count($scores) >= 3) {
                // Remove highest and lowest
                sort($scores);
                array_shift($scores); // drop lowest
                array_pop($scores);   // drop highest
                $finalScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
            } elseif ($hackathon->scoring_algorithm === 'weighted_sum') {
                $finalScore = array_sum($scores);
            } else { // default: weighted_average
                $finalScore = array_sum($scores) / count($scores);
            }

            $ranked[] = [
                'project' => $project,
                'evaluations_count' => $evalCount,
                'final_score' => round($finalScore, 2),
            ];
        }

        // Sort descending by final score
        usort($ranked, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        return $ranked;
    }
}
