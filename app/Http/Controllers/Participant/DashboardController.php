<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Hackathon;
use App\Models\HackathonRegistration;
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

    /**
     * Authenticated Participant Hackathon Discovery (Section 10-15)
     */
    public function hackathons(Request $request): Response
    {
        $user = Auth::user();

        // Ensure session knows we are in competitor context
        if ($request->session()->get('current_context') !== ContextService::CONTEXT_COMPETITOR) {
            ContextService::setCompetitorContext($request);
        }

        // Base query: only published, non-draft hackathons
        $query = Hackathon::with(['organization', 'stages'])
            ->whereIn('status', ['published', 'registration', 'submission', 'voting', 'finished']);

        // 1. Search (title, description, category, organization)
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('organization', function ($orgQuery) use ($search) {
                      $orgQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Modality (online, presential, hybrid)
        if ($request->filled('modality')) {
            $query->where('modality', $request->query('modality'));
        }

        // 3. Registration type (individual, team_only, both)
        if ($request->filled('registration_type')) {
            $regType = $request->query('registration_type');
            if ($regType === 'individual') {
                $query->whereIn('registration_type', ['individual', 'both']);
            } elseif ($regType === 'team_only') {
                $query->where('registration_type', 'team_only');
            }
        }

        // 4. Status filter: 'open', 'upcoming', 'in_progress', 'finished'
        if ($request->filled('status')) {
            $status = $request->query('status');
            $now = now();
            if ($status === 'open') {
                $query->where(function ($q) use ($now) {
                    $q->where('status', 'registration')
                      ->orWhere(function ($sub) use ($now) {
                          $sub->where('status', 'published')
                              ->where(function ($d) use ($now) {
                                  $d->whereNull('registration_start_date')
                                    ->orWhere('registration_start_date', '<=', $now);
                              })
                              ->where(function ($d) use ($now) {
                                  $d->whereNull('registration_end_date')
                                    ->orWhere('registration_end_date', '>=', $now);
                              });
                      });
                });
            } elseif ($status === 'upcoming') {
                $query->where(function ($q) use ($now) {
                    $q->where('registration_start_date', '>', $now)
                      ->orWhere('start_date', '>', $now);
                });
            } elseif ($status === 'in_progress') {
                $query->whereIn('status', ['submission', 'voting']);
            } elseif ($status === 'finished') {
                $query->where('status', 'finished');
            }
        }

        // 5. My Status filter: 'all', 'registered', 'not_registered'
        $userRegistrations = $user->registrations()->with('team')->get();
        $registeredHackathonIds = $userRegistrations->pluck('hackathon_id')->unique()->toArray();

        $userTeamIds = $user->teams()->pluck('teams.id')->toArray();
        $teamRegisteredHackathonIds = HackathonRegistration::whereIn('team_id', $userTeamIds)
            ->pluck('hackathon_id')
            ->unique()
            ->toArray();

        $allUserRegisteredHackathonIds = array_unique(array_merge($registeredHackathonIds, $teamRegisteredHackathonIds));

        if ($request->query('my_status') === 'registered') {
            $query->whereIn('id', $allUserRegisteredHackathonIds);
        } elseif ($request->query('my_status') === 'not_registered') {
            $query->whereNotIn('id', $allUserRegisteredHackathonIds);
        }

        // 6. Sorting
        $sort = $request->query('sort', 'latest');
        if ($sort === 'oldest') {
            $query->oldest();
        } elseif ($sort === 'deadline') {
            $query->orderByRaw('CASE WHEN registration_end_date IS NULL THEN 1 ELSE 0 END, registration_end_date ASC');
        } elseif ($sort === 'title') {
            $query->orderBy('title', 'asc');
        } else {
            $query->latest();
        }

        $hackathons = $query->withCount(['registrations', 'projects'])
            ->paginate(9)
            ->withQueryString();

        $hackathons->through(function ($hackathon) use ($user, $allUserRegisteredHackathonIds) {
            $isRegistered = in_array($hackathon->id, $allUserRegisteredHackathonIds);
            $registration = null;
            if ($isRegistered) {
                $registration = $hackathon->registrations()
                    ->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->orWhereHas('team.members', function ($teamQ) use ($user) {
                              $teamQ->where('users.id', $user->id);
                          });
                    })
                    ->with('team')
                    ->first();
            }

            $now = now();
            $isRegistrationOpen = (
                $hackathon->status === 'registration' ||
                ($hackathon->status === 'published' &&
                 (!$hackathon->registration_start_date || $hackathon->registration_start_date->lte($now)) &&
                 (!$hackathon->registration_end_date || $hackathon->registration_end_date->gte($now)))
            );

            $isRegistrationClosed = (
                in_array($hackathon->status, ['submission', 'voting', 'finished']) ||
                ($hackathon->registration_end_date && $hackathon->registration_end_date->lt($now))
            );

            return [
                'id' => $hackathon->id,
                'title' => $hackathon->title,
                'slug' => $hackathon->slug,
                'description' => $hackathon->description,
                'category' => $hackathon->category,
                'tags' => $hackathon->tags,
                'modality' => $hackathon->modality,
                'location' => $hackathon->location,
                'status' => $hackathon->status,
                'banner_url' => $hackathon->banner_url,
                'logo_url' => $hackathon->logo_url,
                'start_date' => $hackathon->start_date?->toIso8601String(),
                'end_date' => $hackathon->end_date?->toIso8601String(),
                'registration_start_date' => $hackathon->registration_start_date?->toIso8601String(),
                'registration_end_date' => $hackathon->registration_end_date?->toIso8601String(),
                'min_team_members' => $hackathon->min_team_members,
                'max_team_members' => $hackathon->max_team_members,
                'registration_type' => $hackathon->registration_type,
                'requires_team' => $hackathon->requiresTeam(),
                'allows_individual' => $hackathon->allowsIndividualRegistration(),
                'allows_team' => $hackathon->allowsTeamRegistration(),
                'registrations_count' => $hackathon->registrations_count,
                'projects_count' => $hackathon->projects_count,
                'organization' => $hackathon->organization ? [
                    'id' => $hackathon->organization->id,
                    'name' => $hackathon->organization->name,
                    'logo' => $hackathon->organization->logo,
                ] : null,
                'is_registered' => $isRegistered,
                'user_registration' => $registration ? [
                    'id' => $registration->id,
                    'status' => $registration->status,
                    'team_id' => $registration->team_id,
                    'team_name' => $registration->team?->name,
                    'created_at' => $registration->created_at?->toIso8601String(),
                ] : null,
                'is_registration_open' => $isRegistrationOpen,
                'is_registration_closed' => $isRegistrationClosed,
            ];
        });

        $userTeams = $user->teams()->with('members')->get()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name,
                'members_count' => $team->members->count(),
                'is_leader' => $team->leader_id === Auth::id(),
            ];
        });

        return Inertia::render('Participant/Dashboard/Hackathons', [
            'hackathons' => $hackathons,
            'filters' => [
                'search' => $request->query('search', ''),
                'modality' => $request->query('modality', ''),
                'status' => $request->query('status', ''),
                'registration_type' => $request->query('registration_type', ''),
                'my_status' => $request->query('my_status', ''),
                'sort' => $sort,
            ],
            'userTeams' => $userTeams,
        ]);
    }
}
