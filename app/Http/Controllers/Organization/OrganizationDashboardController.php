<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\Project;
use App\Services\ContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationDashboardController extends Controller
{
    /**
     * Institutional Administrative Dashboard (Section 5)
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $organization = ContextService::getActiveOrganization($request);

        if (!$organization) {
            abort(403, 'Nenhuma organização ativa selecionada.');
        }

        $isOrgAdmin = $user->isOrganizationAdmin($organization->id) || $organization->owner_id === $user->id;

        // Base query for hackathons belonging to this institution
        $hackathonsQuery = Hackathon::where('organization_id', $organization->id);

        // If user is not an institution admin, only show hackathons they are assigned to
        if (!$isOrgAdmin) {
            $userHackathonIds = $user->staffHackathons()
                ->where('organization_id', $organization->id)
                ->pluck('hackathons.id');

            $hackathonsQuery->whereIn('id', $userHackathonIds);
        }

        $allOrgHackathons = (clone $hackathonsQuery)->get();
        $hackathonIds = $allOrgHackathons->pluck('id');

        // Calculate KPIs
        $totalHackathons = $allOrgHackathons->count();
        $activeHackathons = $allOrgHackathons->whereIn('status', ['registration', 'submission', 'voting'])->count();
        $finishedHackathons = $allOrgHackathons->where('status', 'finished')->count();

        $totalRegistrations = \App\Models\HackathonRegistration::whereIn('hackathon_id', $hackathonIds)->count();
        $totalTeams = \App\Models\HackathonRegistration::whereIn('hackathon_id', $hackathonIds)
            ->whereNotNull('team_id')
            ->distinct('team_id')
            ->count('team_id');

        $totalSubmissions = Project::whereIn('hackathon_id', $hackathonIds)
            ->where('status', 'submitted')
            ->count();

        $totalParticipants = \App\Models\HackathonRegistration::whereIn('hackathon_id', $hackathonIds)
            ->where('status', 'approved')
            ->count();

        $upcomingEvents = (clone $hackathonsQuery)
            ->where('start_date', '>=', Carbon::now())
            ->orderBy('start_date')
            ->take(5)
            ->get(['id', 'title', 'slug', 'start_date', 'status', 'modality']);

        // Recent hackathons list with rich details (Section 5)
        $recentHackathons = (clone $hackathonsQuery)
            ->with([
                'members.user:id,name,email,avatar',
                'stages',
            ])
            ->withCount(['registrations', 'projects'])
            ->latest()
            ->take(10)
            ->get();

        $membersCount = $organization->users()->wherePivot('is_active', true)->count();

        return Inertia::render('Organization/Dashboard/Organization/Dashboard', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
                'type' => $organization->type,
                'logo' => $organization->logo,
                'website' => $organization->website,
                'description' => $organization->description,
                'is_owner' => $organization->owner_id === $user->id,
            ],
            'kpis' => [
                'total_hackathons' => $totalHackathons,
                'active_hackathons' => $activeHackathons,
                'finished_hackathons' => $finishedHackathons,
                'total_registrations' => $totalRegistrations,
                'total_teams' => $totalTeams,
                'total_submissions' => $totalSubmissions,
                'total_participants' => $totalParticipants,
                'total_members' => $membersCount,
            ],
            'upcoming_events' => $upcomingEvents,
            'recent_hackathons' => $recentHackathons,
            'is_org_admin' => $isOrgAdmin,
        ]);
    }
}
