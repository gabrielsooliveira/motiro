<?php

namespace App\Services;

use App\Models\ConflictOfInterest;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class JudgeService
{
    /**
     * Registra ou atualiza a avaliação de um projeto por um jurado
     */
    public function storeEvaluation(
        Project $project,
        User $judge,
        array $scoresData,
        string $status,
        ?string $positiveFeedback = null,
        ?string $improvementFeedback = null,
        ?string $privateNotes = null
    ): Evaluation {
        // 1. Verifica se o usuário tem designação ativa para o projeto ou hackathon
        $isAssigned = JudgeAssignment::where('hackathon_id', $project->hackathon_id)
            ->where('user_id', $judge->id)
            ->where(function ($q) use ($project) {
                $q->whereNull('project_id')->orWhere('project_id', $project->id);
            })
            ->exists();

        if (!$isAssigned) {
            throw new Exception('Você não está designado para avaliar este projeto.');
        }

        // 2. Verifica se há conflito de interesse declarado ou confirmado
        $hasConflict = ConflictOfInterest::where('hackathon_id', $project->hackathon_id)
            ->where('user_id', $judge->id)
            ->where(function ($q) use ($project) {
                $q->where('project_id', $project->id)->orWhere('team_id', $project->team_id);
            })
            ->exists();

        if ($hasConflict) {
            throw new Exception('Avaliação bloqueada devido a conflito de interesse registrado.');
        }

        // 3. Executa em transação e calcula a nota final ponderada
        return DB::transaction(function () use (
            $project,
            $judge,
            $scoresData,
            $status,
            $positiveFeedback,
            $improvementFeedback,
            $privateNotes
        ) {
            $totalScore = 0.0;
            $criteriaMap = $project->hackathon->evaluationCriteria->keyBy('id');

            foreach ($scoresData as $item) {
                $criterion = $criteriaMap->get($item['criteria_id']);
                $weight = $criterion ? (float)$criterion->weight : 1.0;
                $scoreVal = (float)$item['score'];

                if ($criterion) {
                    if ($scoreVal < $criterion->min_score || $scoreVal > $criterion->max_score) {
                        throw new Exception("A nota para o critério '{$criterion->name}' deve estar entre {$criterion->min_score} e {$criterion->max_score}.");
                    }
                }

                $totalScore += ($scoreVal * $weight);
            }

            $evaluation = Evaluation::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $judge->id],
                [
                    'hackathon_id' => $project->hackathon_id,
                    'final_score' => $totalScore,
                    'feedback_positive' => $positiveFeedback,
                    'feedback_improvement' => $improvementFeedback,
                    'private_admin_notes' => $privateNotes,
                    'status' => $status,
                    'submitted_at' => $status === 'submitted' ? Carbon::now() : null,
                ]
            );

            foreach ($scoresData as $scoreItem) {
                EvaluationScore::updateOrCreate(
                    [
                        'evaluation_id' => $evaluation->id,
                        'criteria_id' => $scoreItem['criteria_id'],
                    ],
                    [
                        'score' => $scoreItem['score'],
                        'comment' => $scoreItem['comment'] ?? null,
                    ]
                );
            }

            // Atualiza status da atribuição do juiz
            JudgeAssignment::where('hackathon_id', $project->hackathon_id)
                ->where('user_id', $judge->id)
                ->where('project_id', $project->id)
                ->update([
                    'status' => $status === 'submitted' ? 'completed' : 'in_progress',
                ]);

            AuditService::log(
                'evaluation.save',
                $evaluation,
                null,
                ['status' => $status, 'final_score' => $totalScore],
                $project->hackathon->organization_id,
                $project->hackathon_id
            );

            return $evaluation;
        });
    }

    /**
     * Declara conflito de interesse entre juiz e projeto/equipe
     */
    public function declareConflict(User $judge, array $data): ConflictOfInterest
    {
        return DB::transaction(function () use ($judge, $data) {
            $conflict = ConflictOfInterest::updateOrCreate(
                [
                    'hackathon_id' => $data['hackathon_id'],
                    'user_id' => $judge->id,
                    'project_id' => $data['project_id'] ?? null,
                    'team_id' => $data['team_id'] ?? null,
                ],
                [
                    'reason' => $data['reason'],
                    'status' => 'declared',
                ]
            );

            // Remove a atribuição pendente se houver para o projeto
            if (!empty($data['project_id'])) {
                JudgeAssignment::where('hackathon_id', $data['hackathon_id'])
                    ->where('user_id', $judge->id)
                    ->where('project_id', $data['project_id'])
                    ->delete();
            }

            AuditService::log('judge.conflict_declared', $conflict, null, $conflict->toArray(), null, $data['hackathon_id']);

            return $conflict;
        });
    }
}
