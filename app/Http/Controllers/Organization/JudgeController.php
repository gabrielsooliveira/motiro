<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\ConflictOfInterest;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\Project;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JudgeController extends Controller
{
    /**
     * Judge dashboard: list projects assigned to the logged-in judge
     */
    public function index(): Response
    {
        $user = Auth::user();

        $assignments = JudgeAssignment::with([
            'hackathon.evaluationCriteria',
            'project.team',
        ])
        ->where('user_id', $user->id)
        ->get();

        $evaluations = Evaluation::where('user_id', $user->id)
            ->with('scores')
            ->get()
            ->keyBy('project_id');

        $conflicts = ConflictOfInterest::where('user_id', $user->id)->get()->keyBy('project_id');

        return Inertia::render('Organization/Dashboard/Judge/Index', [
            'assignments' => $assignments,
            'evaluations' => $evaluations,
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Evaluation screen for a specific project
     */
    public function evaluate(string $projectId): Response
    {
        $user = Auth::user();

        $project = Project::with([
            'hackathon.evaluationCriteria',
            'team.members',
            'submissions',
        ])->findOrFail($projectId);

        // Check assignment
        $isAssigned = JudgeAssignment::where('hackathon_id', $project->hackathon_id)
            ->where('user_id', $user->id)
            ->where(function ($q) use ($projectId) {
                $q->whereNull('project_id')->orWhere('project_id', $projectId);
            })
            ->exists();

        if (!$isAssigned) {
            abort(403, 'Você não está designado para avaliar este projeto.');
        }

        // Check conflict of interest
        $conflict = ConflictOfInterest::where('hackathon_id', $project->hackathon_id)
            ->where('user_id', $user->id)
            ->where(function ($q) use ($project) {
                $q->where('project_id', $project->id)->orWhere('team_id', $project->team_id);
            })
            ->first();

        if ($conflict) {
            abort(403, 'Avaliação bloqueada por conflito de interesse declarado.');
        }

        $existingEvaluation = Evaluation::with('scores')
            ->where('project_id', $project->id)
            ->where('user_id', $user->id)
            ->first();

        return Inertia::render('Organization/Dashboard/Judge/Evaluate', [
            'project' => $project,
            'criteria' => $project->hackathon->evaluationCriteria,
            'evaluation' => $existingEvaluation,
        ]);
    }

    /**
     * Save draft or finalize evaluation
     */
    public function storeEvaluation(Request $request, string $projectId): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,submitted',
            'scores' => 'required|array',
            'scores.*.criteria_id' => 'required|uuid|exists:evaluation_criteria,id',
            'scores.*.score' => 'required|numeric|min:0|max:10',
            'scores.*.comment' => 'nullable|string',
            'feedback_positive' => 'nullable|string',
            'feedback_improvement' => 'nullable|string',
            'private_admin_notes' => 'nullable|string',
        ]);

        $user = Auth::user();
        $project = Project::with('hackathon.evaluationCriteria')->findOrFail($projectId);

        $evaluation = DB::transaction(function () use ($validated, $user, $project) {
            // Calculate weighted final score
            $totalScore = 0.0;
            $criteriaMap = $project->hackathon->evaluationCriteria->keyBy('id');

            foreach ($validated['scores'] as $item) {
                $criterion = $criteriaMap->get($item['criteria_id']);
                $weight = $criterion ? (float)$criterion->weight : 1.0;
                $totalScore += ((float)$item['score'] * $weight);
            }

            $evaluation = Evaluation::updateOrCreate(
                ['project_id' => $project->id, 'user_id' => $user->id],
                [
                    'hackathon_id' => $project->hackathon_id,
                    'final_score' => $totalScore,
                    'feedback_positive' => $validated['feedback_positive'] ?? null,
                    'feedback_improvement' => $validated['feedback_improvement'] ?? null,
                    'private_admin_notes' => $validated['private_admin_notes'] ?? null,
                    'status' => $validated['status'],
                    'submitted_at' => $validated['status'] === 'submitted' ? Carbon::now() : null,
                ]
            );

            // Sync scores
            foreach ($validated['scores'] as $scoreData) {
                EvaluationScore::updateOrCreate(
                    ['evaluation_id' => $evaluation->id, 'criteria_id' => $scoreData['criteria_id']],
                    ['score' => $scoreData['score'], 'comment' => $scoreData['comment'] ?? null]
                );
            }

            // Update assignment status
            if ($validated['status'] === 'submitted') {
                JudgeAssignment::where('hackathon_id', $project->hackathon_id)
                    ->where('user_id', $user->id)
                    ->where('project_id', $project->id)
                    ->update(['status' => 'completed']);
            }

            AuditService::log("evaluation.{$validated['status']}", $evaluation, null, ['final_score' => $totalScore], $project->hackathon->organization_id, $project->hackathon_id);

            return $evaluation;
        });

        $msg = $validated['status'] === 'submitted' ? 'Avaliação finalizada com sucesso!' : 'Rascunho salvo com sucesso!';
        return redirect()->route('judge.index')->with('success', $msg);
    }

    /**
     * Declare a conflict of interest
     */
    public function declareConflict(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hackathon_id' => 'required|uuid|exists:hackathons,id',
            'project_id' => 'nullable|uuid|exists:projects,id',
            'team_id' => 'nullable|uuid|exists:teams,id',
            'reason' => 'required|string|max:1000',
        ]);

        $user = Auth::user();

        ConflictOfInterest::create([
            'hackathon_id' => $validated['hackathon_id'],
            'user_id' => $user->id,
            'project_id' => $validated['project_id'] ?? null,
            'team_id' => $validated['team_id'] ?? null,
            'reason' => $validated['reason'],
            'status' => 'declared',
        ]);

        // Remove from judge assignment queue if already assigned
        if (!empty($validated['project_id'])) {
            JudgeAssignment::where('hackathon_id', $validated['hackathon_id'])
                ->where('user_id', $user->id)
                ->where('project_id', $validated['project_id'])
                ->delete();
        }

        AuditService::log('conflict_of_interest.declared', $user, null, $validated, null, $validated['hackathon_id']);

        return redirect()->back()->with('success', 'Conflito de interesse registrado com sucesso!');
    }
}
