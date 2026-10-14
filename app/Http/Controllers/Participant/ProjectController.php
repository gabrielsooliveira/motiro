<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
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
        $project = Project::with(['hackathon', 'team.members', 'submissions.submitter'])->findOrFail($id);

        return Inertia::render('Organization/Dashboard/Project/Show', [
            'project' => $project,
            'isTeamMember' => $project->team->members->contains('id', Auth::id()),
            'isSubmissionOpen' => $this->isSubmissionOpen($project->hackathon),
        ]);
    }

    /**
     * Create or update project submission by team
     */
    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hackathon_id' => 'required|uuid|exists:hackathons,id',
            'team_id' => 'required|uuid|exists:teams,id',
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
        $team = Team::findOrFail($validated['team_id']);
        $user = Auth::user();

        // 1. Authorize: user must be member of the team
        if (!$team->members->contains('id', $user->id)) {
            abort(403, 'Apenas integrantes da equipe podem submeter o projeto.');
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

            $project = Project::updateOrCreate(
                ['hackathon_id' => $hackathon->id, 'team_id' => $team->id],
                $validated
            );

            // Record immutable version snapshot
            ProjectSubmission::create([
                'project_id' => $project->id,
                'submitted_by' => $user->id,
                'version' => (string) ($project->submissions()->count() + 1) . '.0',
                'snapshot_data' => $project->toArray(),
            ]);

            AuditService::log('project.submitted', $project, null, $project->toArray(), $hackathon->organization_id, $hackathon->id);

            NotificationService::notifyTeam(
                $team,
                'project_submitted',
                'Projeto Submetido',
                "O projeto '{$project->title}' foi submetido com sucesso no Hackathon '{$hackathon->title}'."
            );

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
