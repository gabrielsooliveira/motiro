<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\ConflictOfInterest;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\JudgeAssignment;
use App\Models\Project;
use App\Services\AuditService;
use App\Services\JudgeService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class JudgeController extends Controller
{
    public function __construct(
        protected JudgeService $judgeService
    ) {}

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

        if (!\Illuminate\Support\Facades\Gate::allows('evaluateProject', $project)) {
            // Check conflict vs assignment to provide clear feedback
            $conflict = ConflictOfInterest::where('hackathon_id', $project->hackathon_id)
                ->where('user_id', $user->id)
                ->where(function ($q) use ($project) {
                    $q->where('project_id', $project->id)->orWhere('team_id', $project->team_id);
                })
                ->exists();

            if ($conflict) {
                abort(403, 'Avaliação bloqueada por conflito de interesse declarado.');
            }

            abort(403, 'Você não está designado para avaliar este projeto.');
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

        $project = Project::with('hackathon.evaluationCriteria')->findOrFail($projectId);
        if (!\Illuminate\Support\Facades\Gate::allows('evaluateProject', $project)) {
            abort(403, 'Acesso não autorizado para avaliar este projeto.');
        }

        try {
            $this->judgeService->storeEvaluation(
                $project,
                Auth::user(),
                $validated['scores'],
                $validated['status'],
                $validated['feedback_positive'] ?? null,
                $validated['feedback_improvement'] ?? null,
                $validated['private_admin_notes'] ?? null
            );

            $msg = $validated['status'] === 'submitted' ? 'Avaliação finalizada com sucesso!' : 'Rascunho salvo com sucesso!';
            return redirect()->route('judge.index')->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['evaluation' => $e->getMessage()]);
        }
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

        try {
            $this->judgeService->declareConflict(Auth::user(), $validated);

            return redirect()->back()->with('success', 'Conflito de interesse registrado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['conflict' => $e->getMessage()]);
        }
    }
}
