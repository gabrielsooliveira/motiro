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
use App\Services\HackathonService;
use Inertia\Response;

class HackathonController extends Controller
{
    public function __construct(
        protected RegistrationService $registrationService,
        protected EvaluationService $evaluationService,
        protected HackathonService $hackathonService
    ) {}

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
            'canManageStaff' => \Illuminate\Support\Facades\Gate::allows('manageStaff', $hackathon),
            'canManageStages' => \Illuminate\Support\Facades\Gate::allows('manageStages', $hackathon),
            'canManageParticipants' => \Illuminate\Support\Facades\Gate::allows('manageParticipants', $hackathon),
            'canUpdate' => \Illuminate\Support\Facades\Gate::allows('update', $hackathon),
        ]);
    }

    /**
     * Show form for creating a new Hackathon with stages and criteria/weights
     */
    public function create(Request $request): Response
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $organizations = Organization::where('owner_id', $user->id)
            ->orWhereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                  ->where('organization_user.is_active', true)
                  ->whereIn('organization_user.role', ['admin', 'manager']);
            })
            ->get();

        if ($organizations->isEmpty()) {
            return redirect()->route('organization.select')->with('error', 'Você precisa estar vinculado a uma instituição como administrador para criar um Hackathon.');
        }

        $activeOrgId = $request->session()->get('active_organization_id');
        $activeOrganization = $organizations->firstWhere('id', $activeOrgId) ?? $organizations->first();

        return Inertia::render('Organization/Dashboard/Hackathon/Create', [
            'organizations' => $organizations,
            'activeOrganization' => $activeOrganization,
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
            'stages' => 'nullable|array',
            'stages.*.name' => 'required|string|max:255',
            'stages.*.description' => 'nullable|string',
            'stages.*.start_date' => 'required|date',
            'stages.*.end_date' => 'required|date',
            'stages.*.order' => 'nullable|integer',
            'stages.*.allowed_actions' => 'nullable|array',
            'criteria' => 'nullable|array',
            'criteria.*.name' => 'required|string|max:255',
            'criteria.*.description' => 'nullable|string',
            'criteria.*.weight' => 'required|numeric|min:0|max:100',
            'criteria.*.min_score' => 'nullable|numeric',
            'criteria.*.max_score' => 'nullable|numeric',
            'criteria.*.order' => 'nullable|integer',
        ]);

        $organization = Organization::findOrFail($validated['organization_id']);
        if (!\Illuminate\Support\Facades\Gate::allows('createHackathon', $organization)) {
            abort(403, 'Acesso não autorizado para criar hackathons nesta instituição.');
        }

        try {
            $hackathon = $this->hackathonService->createHackathon($user, $validated);

            return redirect()->route('hackathon.manage.index', $hackathon->id)
                ->with('success', 'Hackathon criado com sucesso com etapas e pesos de entrega configurados!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['title' => $e->getMessage()]);
        }
    }

    /**
     * Store a new stage for an existing hackathon
     */
    public function storeStage(Request $request, string $id): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('manageStages', $hackathon)) {
            abort(403, 'Acesso não autorizado para alterar etapas ou critérios deste Hackathon.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'order' => 'nullable|integer',
            'allowed_actions' => 'nullable|array',
        ]);

        try {
            $this->hackathonService->storeStage($hackathon, $validated, Auth::user());

            return redirect()->back()->with('success', 'Etapa criada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['name' => $e->getMessage()]);
        }
    }

    /**
     * Update an existing stage
     */
    public function updateStage(Request $request, string $id, string $stageId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('manageStages', $hackathon)) {
            abort(403, 'Acesso não autorizado para alterar etapas ou critérios deste Hackathon.');
        }

        $stage = HackathonStage::where('hackathon_id', $hackathon->id)->findOrFail($stageId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date',
            'order' => 'nullable|integer',
            'allowed_actions' => 'nullable|array',
            'status' => 'nullable|string',
        ]);

        try {
            $this->hackathonService->updateStage($stage, $validated, Auth::user());

            return redirect()->back()->with('success', 'Etapa atualizada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['name' => $e->getMessage()]);
        }
    }

    /**
     * Delete an existing stage
     */
    public function destroyStage(string $id, string $stageId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('manageStages', $hackathon)) {
            abort(403, 'Acesso não autorizado para alterar etapas deste Hackathon.');
        }

        $stage = HackathonStage::where('hackathon_id', $hackathon->id)->findOrFail($stageId);

        try {
            $this->hackathonService->deleteStage($stage, Auth::user());

            return redirect()->back()->with('success', 'Etapa removida com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['stage' => $e->getMessage()]);
        }
    }

    /**
     * Store a new criterion / delivery weight
     */
    public function storeCriterion(Request $request, string $id): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('manageStages', $hackathon)) {
            abort(403, 'Acesso não autorizado para configurar critérios de avaliação.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'required|numeric|min:0|max:100',
            'min_score' => 'nullable|numeric|min:0',
            'max_score' => 'nullable|numeric',
            'order' => 'nullable|integer',
        ]);

        try {
            $this->hackathonService->storeCriterion($hackathon, $validated, Auth::user());

            return redirect()->back()->with('success', 'Critério / Entrega criado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['name' => $e->getMessage()]);
        }
    }

    /**
     * Update an existing criterion
     */
    public function updateCriterion(Request $request, string $id, string $criterionId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('manageStages', $hackathon)) {
            abort(403, 'Acesso não autorizado para alterar critérios de avaliação.');
        }

        $criterion = EvaluationCriterion::where('hackathon_id', $hackathon->id)->findOrFail($criterionId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'weight' => 'sometimes|numeric|min:0|max:100',
            'min_score' => 'nullable|numeric|min:0',
            'max_score' => 'nullable|numeric',
            'order' => 'nullable|integer',
        ]);

        try {
            $this->hackathonService->updateCriterion($criterion, $validated, Auth::user());

            return redirect()->back()->with('success', 'Critério / Entrega atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['name' => $e->getMessage()]);
        }
    }

    /**
     * Delete an existing criterion
     */
    public function destroyCriterion(string $id, string $criterionId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('manageStages', $hackathon)) {
            abort(403, 'Acesso não autorizado para remover critérios de avaliação.');
        }

        $criterion = EvaluationCriterion::where('hackathon_id', $hackathon->id)->findOrFail($criterionId);

        try {
            $this->hackathonService->deleteCriterion($criterion, Auth::user());

            return redirect()->back()->with('success', 'Critério / Entrega removido com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['criterion' => $e->getMessage()]);
        }
    }

    /**
     * Update hackathon settings
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('update', $hackathon)) {
            abort(403, 'Acesso não autorizado para editar as configurações deste Hackathon.');
        }

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

        try {
            $this->hackathonService->updateHackathon($hackathon, $validated, Auth::user());

            return redirect()->back()->with('success', 'Hackathon atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['title' => $e->getMessage()]);
        }
    }

    /**
     * Distribute projects to judges
     */
    public function distributeJudging(string $id): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($id);
        if (!\Illuminate\Support\Facades\Gate::allows('manageStaff', $hackathon)) {
            abort(403, 'Acesso não autorizado para gerenciar ou distribuir projetos para jurados.');
        }

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
        if (!\Illuminate\Support\Facades\Gate::allows('publishResults', $hackathon)) {
            abort(403, 'Acesso não autorizado para publicar resultados deste Hackathon.');
        }

        try {
            $this->hackathonService->publishResults($hackathon, Auth::user());

            return redirect()->back()->with('success', 'Resultados publicados oficialmente!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['hackathon' => $e->getMessage()]);
        }
    }

    private function authorizeAdmin(Hackathon $hackathon): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if (!\Illuminate\Support\Facades\Gate::allows('manage', $hackathon)) {
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
