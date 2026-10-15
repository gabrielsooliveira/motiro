<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\HackathonRegistration;
use App\Models\Project;
use App\Models\ProjectSubmission;
use App\Models\Team;
use App\Services\AuditService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show project details or submission editor
     */
    public function show(string $id): Response
    {
        $project = Project::with(['hackathon', 'team.members', 'user', 'submissions.submitter'])->findOrFail($id);
        $user = Auth::user();

        $isAuthor = ($project->team && $project->team->members->contains('id', $user->id)) ||
            ($project->user_id === $user->id);

        return Inertia::render('Organization/Dashboard/Project/Show', [
            'project' => $project,
            'isTeamMember' => $isAuthor,
            'isSubmissionOpen' => $this->isSubmissionOpen($project->hackathon),
        ]);
    }

    /**
     * Create or update project submission by team or individual
     */
    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hackathon_id' => 'required|uuid|exists:hackathons,id',
            'team_id' => 'nullable|uuid|exists:teams,id',
            'title' => 'required|string|max:255',
            'short_description' => 'nullable|string|max:500',
            'problem_statement' => 'nullable|string',
            'solution_description' => 'nullable|string',
            'target_audience' => 'nullable|string|max:255',
            'technologies' => 'nullable|array',
            'pitch' => 'nullable|string',
            'video_url' => 'nullable|url',
            'github_repository_url' => 'nullable|url',
            'demo_url' => 'nullable|url',
            'documentation_url' => 'nullable|url',
        ]);

        $hackathon = Hackathon::findOrFail($validated['hackathon_id']);
        $user = Auth::user();
        $team = !empty($validated['team_id']) ? Team::findOrFail($validated['team_id']) : null;

        // 1. Authorize: team submission or individual submission
        if ($team) {
            if ($hackathon->registration_type === 'individual') {
                return redirect()->back()->withErrors(['project' => 'Este Hackathon aceita apenas submissões individuais.']);
            }
            if (!$team->members->contains('id', $user->id)) {
                abort(403, 'Apenas integrantes da equipe podem submeter o projeto.');
            }
        } else {
            if ($hackathon->requiresTeam()) {
                return redirect()->back()->withErrors(['project' => 'Este Hackathon exige submissão vinculada a uma equipe.']);
            }
            $userRegistration = HackathonRegistration::where('hackathon_id', $hackathon->id)
                ->where('user_id', $user->id)
                ->first();

            if (!$userRegistration) {
                abort(403, 'Você precisa estar inscrito no Hackathon para submeter um projeto.');
            }
        }

        // 2. Check submission window
        if (!$this->isSubmissionOpen($hackathon)) {
            return redirect()->back()->withErrors(['project' => 'O período de submissão de projetos está encerrado.']);
        }

        $project = DB::transaction(function () use ($validated, $hackathon, $team, $user) {
            $slug = Str::slug($validated['title']);
            $count = 1;
            while (Project::where('slug', $slug)->where('hackathon_id', $hackathon->id)->exists()) {
                $slug = Str::slug($validated['title']) . '-' . $count;
                $count++;
            }
            $validated['slug'] = $slug;
            $validated['status'] = 'submitted';
            $validated['submitted_at'] = Carbon::now();
            $validated['user_id'] = $user->id;

            if ($team) {
                $project = Project::updateOrCreate(
                    ['hackathon_id' => $hackathon->id, 'team_id' => $team->id],
                    $validated
                );
            } else {
                $project = Project::updateOrCreate(
                    ['hackathon_id' => $hackathon->id, 'user_id' => $user->id, 'team_id' => null],
                    $validated
                );
            }

            // Record immutable version snapshot
            ProjectSubmission::create([
                'project_id' => $project->id,
                'submitted_by' => $user->id,
                'version' => (string) ($project->submissions()->count() + 1) . '.0',
                'snapshot_data' => $project->toArray(),
            ]);

            AuditService::log('project.submitted', $project, null, $project->toArray(), $hackathon->organization_id, $hackathon->id);

            if ($team) {
                NotificationService::notifyTeam(
                    $team,
                    'project_submitted',
                    'Projeto Submetido',
                    "O projeto '{$project->title}' foi submetido com sucesso no Hackathon '{$hackathon->title}'."
                );
            } else {
                NotificationService::notify(
                    $user,
                    'project_submitted',
                    'Projeto Submetido',
                    "Seu projeto '{$project->title}' foi submetido com sucesso no Hackathon '{$hackathon->title}'."
                );
            }

            return $project;
        });

        return redirect()->back()->with('success', 'Projeto submetido com sucesso!');
    }

    private function isSubmissionOpen(Hackathon $hackathon): bool
    {
        if ($hackathon->submissions_reopened) {
            return true;
        }

        $now = Carbon::now();
        if ($hackathon->submission_start_date && $now->lt($hackathon->submission_start_date)) {
            return false;
        }

        if ($hackathon->submission_end_date && $now->gt($hackathon->submission_end_date)) {
            return false;
        }

        return true;
    }
}
