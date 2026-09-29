<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Evaluation;
use App\Models\Hackathon;
use App\Models\JudgeAssignment;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Main unified dashboard with views based on context and roles
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // 1. Competitor Data: Teams with members, registrations and hackathon stages
        $userTeams = $user->teams()->with([
            'members',
            'registrations.hackathon.stages',
            'projects.hackathon.stages'
        ])->get();

        $userRegistrations = $user->registrations()->with([
            'hackathon.stages',
            'team.members'
        ])->get();

        $pendingInvitations = $user->teamInvitations()->with('team')->where('status', 'pending')->get();
        $competitorProjects = Project::whereIn('team_id', $userTeams->pluck('id'))->with('hackathon.stages')->get();

        // 2. Judge Data
        $judgeAssignments = JudgeAssignment::where('user_id', $user->id)
            ->with(['hackathon.evaluationCriteria', 'project.team'])
            ->get();
        $completedEvaluations = Evaluation::where('user_id', $user->id)->where('status', 'submitted')->count();
        $pendingEvaluationsCount = $judgeAssignments->count() - $completedEvaluations;

        // 3. Admin / Organizer Data (Organizations owned or where user has admin role)
        $userOrganizations = $user->organizations()->with(['hackathons.stages'])->get();
        $managedHackathons = Hackathon::whereIn('organization_id', $userOrganizations->pluck('id'))
            ->withCount(['registrations', 'projects'])
            ->with(['stages', 'registrations.team'])
            ->get();

        // 4. Notifications
        $notifications = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/Home', [
            'user' => $user,
            'competitor' => [
                'teams' => $userTeams,
                'registrations' => $userRegistrations,
                'invitations' => $pendingInvitations,
                'projects' => $competitorProjects,
            ],
            'judge' => [
                'assignments' => $judgeAssignments,
                'pendingCount' => max(0, $pendingEvaluationsCount),
                'completedCount' => $completedEvaluations,
            ],
            'admin' => [
                'organizations' => $userOrganizations,
                'hackathons' => $managedHackathons,
            ],
            'notifications' => $notifications,
        ]);
    }
}
