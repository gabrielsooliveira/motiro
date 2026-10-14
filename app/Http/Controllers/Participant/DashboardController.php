<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Hackathon;
use App\Models\JudgeAssignment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Team;
use App\Services\ContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Dedicated Competitor Dashboard (Section 1.1)
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // Ensure session knows we are in competitor context
        if ($request->session()->get('current_context') !== ContextService::CONTEXT_COMPETITOR) {
            ContextService::setCompetitorContext($request);
        }

        // 1. Teams where user is member or leader
        $userTeams = $user->teams()->with([
            'members',
            'registrations.hackathon.stages',
            'projects.hackathon.stages'
        ])->get();

        // 2. Hackathons user is registered in
        $userRegistrations = $user->registrations()->with([
            'hackathon.organization',
            'hackathon.stages',
            'hackathon.challenges',
            'team.members'
        ])->get();

        $registeredHackathonIds = $userRegistrations->pluck('hackathon_id')->unique();

        // 3. Available Hackathons to discover & participate
        $availableHackathons = Hackathon::with(['organization', 'stages'])
            ->whereIn('status', ['published', 'registration', 'submission', 'voting'])
            ->whereNotIn('id', $registeredHackathonIds)
            ->withCount(['registrations', 'projects'])
            ->latest()
            ->take(6)
            ->get();

        // 4. Pending team invitations
        $pendingInvitations = $user->teamInvitations()->with('team')->where('status', 'pending')->get();

        // 5. Competitor Projects & Submissions
        $competitorProjects = Project::whereIn('team_id', $userTeams->pluck('id'))
            ->with(['hackathon.stages', 'submissions'])
            ->get();

        // 6. Judge Data (if user is assigned as judge in any hackathon)
        $judgeAssignments = JudgeAssignment::where('user_id', $user->id)
            ->with(['hackathon.evaluationCriteria', 'project.team'])
            ->get();
        $completedEvaluations = Evaluation::where('user_id', $user->id)->where('status', 'submitted')->count();
        $pendingEvaluationsCount = $judgeAssignments->count() - $completedEvaluations;

        // 7. Notifications
        $notifications = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Participant/Dashboard/Home', [
            'user' => $user,
            'competitor' => [
                'teams' => $userTeams,
                'registrations' => $userRegistrations,
                'invitations' => $pendingInvitations,
                'projects' => $competitorProjects,
                'available_hackathons' => $availableHackathons,
            ],
            'judge' => [
                'assignments' => $judgeAssignments,
                'pendingCount' => max(0, $pendingEvaluationsCount),
                'completedCount' => $completedEvaluations,
            ],
            'notifications' => $notifications,
        ]);
    }
}
