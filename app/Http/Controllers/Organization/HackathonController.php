<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EvaluationCriterion;
use App\Models\Hackathon;
use App\Models\HackathonStage;
use App\Models\Organization;
use App\Services\AuditService;
use App\Services\EvaluationService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HackathonController extends Controller
{
    protected RegistrationService $registrationService;
    protected EvaluationService $evaluationService;

    public function __construct(RegistrationService $registrationService, EvaluationService $evaluationService)
    {
        $this->registrationService = $registrationService;
        $this->evaluationService = $evaluationService;
    }

    /**
     * Public discovery of hackathons
     */
    public function index(Request $request): Response
    {
        $query = Hackathon::with('organization')
            ->whereIn('status', ['published', 'registration', 'submission', 'voting', 'finished', 'draft']);

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('modality')) {
            $query->where('modality', $request->query('modality'));
        }

        $hackathons = $query->withCount(['registrations', 'projects'])->latest()->paginate(12)->withQueryString();

        return Inertia::render('Site/Hackathon/Index', [
            'hackathons' => $hackathons,
            'filters' => $request->only(['search', 'modality']),
        ]);
    }

    /**
     * Public page of a hackathon
     */
    public function show(string $slug): Response
    {
        $hackathon = Hackathon::with([
            'organization',
            'stages',
            'evaluationCriteria',
            'registrations.team.members',
            'projects.team',
        ])
        ->where('slug', $slug)
        ->firstOrFail();

        $user = Auth::user();
        $userTeams = [];
        $userRegisteredTeamId = null;

        if ($user) {
            // Teams the user is leader of
            $userTeams = $user->ledTeams()->with('members')->get();
            // Check if user has an already registered team
            $registeredIds = $hackathon->registrations()->pluck('team_id')->toArray();
            $userTeamIds = $user->teams()->pluck('teams.id')->toArray();
            $intersect = array_intersect($registeredIds, $userTeamIds);
            $userRegisteredTeamId = !empty($intersect) ? reset($intersect) : null;
        }

        return Inertia::render('Site/Hackathon/Show', [
            'hackathon' => $hackathon,
            'teamsCount' => $hackathon->registrations()->where('status', 'approved')->count(),
            'userTeams' => $userTeams,
            'userRegisteredTeamId' => $userRegisteredTeamId,
        ]);
    }

    /**
     * Admin view for managing a Hackathon
     */
    public function manage(string $id): Response
    {
        $hackathon = Hackathon::with([
            'organization.users' => function ($q) {
                $q->wherePivot('is_active', true)->select('users.id', 'users.name', 'users.email', 'users.avatar');
            },
            'members.user:id,name,email,avatar',
            'challenges',
            'stages',
            'evaluationCriteria',
            'registrations.user:id,name,email,avatar',
            'registrations.team.members',
            'teams.members',
            'teams.leader',
            'projects.team',
            'projects.user',
            'judgeAssignments.judge',
            'conflictsOfInterest.user',
            'auditLogs.user',
        ])->findOrFail($id);

        $this->authorizeAdmin($hackathon);

        $user = Auth::user();
        $isOrgAdmin = $hackathon->organization && ($hackathon->organization->owner_id === $user->id || $user->isOrganizationAdmin($hackathon->organization_id));
        $isHackathonAdmin = $hackathon->members()->where('user_id', $user->id)->where('role', 'admin')->exists();

        return Inertia::render('Organization/Dashboard/Hackathon/Manage', [
            'hackathon' => $hackathon,
            'rankings' => $hackathon->results_published ? $this->evaluationService->calculateRankings($hackathon) : null,
            'canManageStaff' => $isOrgAdmin || $isHackathonAdmin,
        ]);
    }

    /**
     * Create a new Hackathon
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'organization_id' => 'required|uuid|exists:organizations,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'modality' => 'required|in:presential,online,hybrid',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'registration_start_date' => 'nullable|date',
            'registration_end_date' => 'nullable|date|after_or_equal:registration_start_date',
            'registration_type' => 'nullable|in:individual,team_only,individual_or_team',
            'allow_team_creation' => 'nullable|boolean',
            'allow_team_invites' => 'nullable|boolean',
            'allow_team_requests' => 'nullable|boolean',
            'allow_org_team_management' => 'nullable|boolean',
            'team_formation_deadline' => 'nullable|date',
            'team_management_deadline' => 'nullable|date',
            'min_team_members' => 'required|integer|min:1',
            'max_team_members' => 'required|integer|gte:min_team_members',
            'max_teams' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'rules' => 'nullable|string',
        ]);

        // Check user permission on organization
        $isOrgAdmin = $user->isOrganizationAdmin($validated['organization_id']) || Organization::where('id', $validated['organization_id'])->where('owner_id', $user->id)->exists();
        if (!$isOrgAdmin) {
            abort(403, 'Apenas administradores da instituição podem criar hackathons.');
        }

        $regType = $validated['registration_type'] ?? 'individual_or_team';
        $validated['registration_type'] = $regType;
        $validated['requires_team'] = ($regType === 'team_only');

        $slug = Str::slug($validated['title']);
        $count = 1;
        while (Hackathon::where('slug', $slug)->exists()) {
            $slug = Str::slug($validated['title']) . '-' . $count;
            $count++;
        }
        $validated['slug'] = $slug;
        $validated['status'] = 'draft';

        $hackathon = Hackathon::create($validated);

        // Assign creator as admin in hackathon_members
        \App\Models\HackathonMember::create([
            'hackathon_id' => $hackathon->id,
            'user_id' => $user->id,
            'role' => 'admin',
        ]);

        // Create default stages
        $this->createDefaultStages($hackathon);

        // Create default evaluation criteria
        $this->createDefaultCriteria($hackathon);

        AuditService::log('hackathon.create', $hackathon, null, $hackathon->toArray(), $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Hackathon criado com sucesso!');
    }

    /**
     * Update hackathon settings
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'modality' => 'sometimes|in:presential,online,hybrid',
            'location' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'registration_start_date' => 'nullable|date',
            'registration_end_date' => 'nullable|date',
            'registration_type' => 'nullable|in:individual,team_only,individual_or_team',
            'allow_team_creation' => 'nullable|boolean',
            'allow_team_invites' => 'nullable|boolean',
            'allow_team_requests' => 'nullable|boolean',
            'allow_org_team_management' => 'nullable|boolean',
            'team_formation_deadline' => 'nullable|date',
            'team_management_deadline' => 'nullable|date',
            'min_team_members' => 'sometimes|integer|min:1',
            'max_team_members' => 'sometimes|integer|gte:min_team_members',
            'max_teams' => 'nullable|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'rules' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if (isset($validated['registration_type'])) {
            $validated['requires_team'] = ($validated['registration_type'] === 'team_only');
        }

        $oldValues = $hackathon->toArray();
        $hackathon->update($validated);

        AuditService::log('hackathon.update', $hackathon, $oldValues, $hackathon->toArray(), $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Hackathon atualizado com sucesso!');
    }

    /**
     * Distribute projects to judges
     */
    public function distributeJudging(string $id): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        $this->authorizeAdmin($hackathon);

        $this->evaluationService->distributeProjects($hackathon);
        AuditService::log('hackathon.distribute_judging', $hackathon, null, null, $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Projetos distribuídos aos juízes com sucesso!');
    }

    /**
     * Publish hackathon final results
     */
    public function publishResults(string $id): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        $this->authorizeAdmin($hackathon);

        $hackathon->update(['results_published' => true, 'status' => 'finished']);
        AuditService::log('hackathon.publish_results', $hackathon, null, ['results_published' => true], $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Resultados publicados oficialmente!');
    }

    private function authorizeAdmin(Hackathon $hackathon): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if (!$user->canManageHackathon($hackathon)) {
            abort(403, 'Acesso não autorizado para gerenciar este Hackathon.');
        }
    }

    private function createDefaultStages(Hackathon $hackathon): void
    {
        $stages = [
            ['name' => 'Divulgação e Inscrições', 'order' => 1, 'start_date' => now(), 'end_date' => now()->addDays(14)],
            ['name' => 'Desenvolvimento', 'order' => 2, 'start_date' => now()->addDays(15), 'end_date' => now()->addDays(17)],
            ['name' => 'Submissão de Projetos', 'order' => 3, 'start_date' => now()->addDays(17), 'end_date' => now()->addDays(18)],
            ['name' => 'Avaliação dos Juízes', 'order' => 4, 'start_date' => now()->addDays(18), 'end_date' => now()->addDays(20)],
            ['name' => 'Resultado e Premiação', 'order' => 5, 'start_date' => now()->addDays(21), 'end_date' => now()->addDays(21)],
        ];

        foreach ($stages as $stage) {
            HackathonStage::create(array_merge($stage, ['hackathon_id' => $hackathon->id]));
        }
    }

    private function createDefaultCriteria(Hackathon $hackathon): void
    {
        $criteria = [
            ['name' => 'Inovação e Criatividade', 'weight' => 0.30, 'order' => 1],
            ['name' => 'Impacto e Viabilidade', 'weight' => 0.25, 'order' => 2],
            ['name' => 'Qualidade Técnica e Execução', 'weight' => 0.25, 'order' => 3],
            ['name' => 'Design e UX/UI', 'weight' => 0.10, 'order' => 4],
            ['name' => 'Apresentação (Pitch)', 'weight' => 0.10, 'order' => 5],
        ];

        foreach ($criteria as $criterion) {
            EvaluationCriterion::create(array_merge($criterion, ['hackathon_id' => $hackathon->id]));
        }
    }
}
