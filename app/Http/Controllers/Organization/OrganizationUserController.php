<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ContextService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationUserController extends Controller
{
    /**
     * List users and invitations of the active institution (Section 6)
     */
    public function index(Request $request): Response
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403, 'Organização ativa não encontrada.');
        }

        $user = Auth::user();
        $isOrgAdmin = $user->isOrganizationAdmin($organization->id) || $organization->owner_id === $user->id;

        // Members with their assigned hackathons within this organization
        $members = $organization->users()
            ->with([
                'staffHackathons' => function ($q) use ($organization) {
                    $q->where('organization_id', $organization->id)->select('hackathons.id', 'hackathons.title');
                }
            ])
            ->get()
            ->map(function ($member) use ($organization) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'role' => $member->pivot->role,
                    'is_active' => (bool)$member->pivot->is_active,
                    'is_owner' => $member->id === $organization->owner_id,
                    'joined_at' => $member->pivot->created_at?->format('d/m/Y'),
                    'assigned_hackathons' => $member->staffHackathons->map(fn($h) => [
                        'id' => $h->id,
                        'title' => $h->title,
                        'role' => $h->pivot->role,
                    ]),
                ];
            });

        // Pending invitations
        $invitations = $organization->invitations()
            ->where('status', 'pending')
            ->with('invitedBy:id,name')
            ->latest()
            ->get();

        // Hackathons of the org (for quick filter or reference)
        $orgHackathons = $organization->hackathons()->select('id', 'title')->get();

        return Inertia::render('Dashboard/Organization/Users', [
            'organization' => $organization->only(['id', 'name', 'slug']),
            'members' => $members,
            'invitations' => $invitations,
            'hackathons' => $orgHackathons,
            'is_org_admin' => $isOrgAdmin,
        ]);
    }

    /**
     * Invite a user to the institution (Section 7)
     */
    public function invite(Request $request): RedirectResponse
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403, 'Organização ativa não encontrada.');
        }

        $this->authorizeAdmin($organization);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|in:admin,manager,collaborator',
        ]);

        $email = strtolower(trim($validated['email']));
        $role = $validated['role'];

        // Check if user is already a member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $isAlreadyMember = $organization->users()
                ->where('users.id', $existingUser->id)
                ->exists();

            if ($isAlreadyMember) {
                return redirect()->back()->with('error', 'Este usuário já faz parte da instituição.');
            }

            // Directly link existing user or create active membership
            OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $existingUser->id,
                'role' => $role,
                'is_active' => true,
            ]);

            // Sync RBAC role if admin
            if ($role === 'admin') {
                $orgAdminRole = Role::where('name', 'org_admin')->first();
                if ($orgAdminRole) {
                    $existingUser->roles()->attach($orgAdminRole->id, ['organization_id' => $organization->id]);
                }
            }

            NotificationService::notify(
                $existingUser,
                'organization_added',
                'Novo Vínculo Institucional',
                "Você foi adicionado à instituição '{$organization->name}' com a função de " . ucfirst($role) . ".",
                route('context.select')
            );

            AuditService::log('organization.member_added', $organization, null, ['user_id' => $existingUser->id, 'role' => $role], $organization->id);

            return redirect()->back()->with('success', "Usuário {$existingUser->name} ({$email}) vinculado à instituição com sucesso!");
        }

        // Check if there is already a pending invitation for this email
        $existingInvite = OrganizationInvitation::where('organization_id', $organization->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();

        if ($existingInvite) {
            return redirect()->back()->with('error', 'Já existe um convite pendente para este e-mail.');
        }

        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $role,
            'invited_by_id' => Auth::id(),
            'token' => Str::random(40),
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        AuditService::log('organization.invitation_sent', $invitation, null, ['email' => $email, 'role' => $role], $organization->id);

        return redirect()->back()->with('success', "Convite enviado com sucesso para {$email}! Link de cadastro: " . route('register', ['invitation' => $invitation->token]));
    }

    /**
     * Update user role in institution (Section 8)
     */
    public function updateRole(Request $request, string $userId): RedirectResponse
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403);
        }

        $this->authorizeAdmin($organization);

        if ($userId === $organization->owner_id) {
            return redirect()->back()->with('error', 'A função do proprietário da instituição não pode ser alterada.');
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,manager,collaborator',
        ]);

        $membership = OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $oldRole = $membership->role;
        OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->update(['role' => $validated['role']]);

        // Sync org_admin role in model_has_roles
        $user = User::findOrFail($userId);
        $orgAdminRole = Role::where('name', 'org_admin')->first();

        if ($orgAdminRole) {
            if ($validated['role'] === 'admin') {
                if (!$user->roles()->where('roles.id', $orgAdminRole->id)->wherePivot('organization_id', $organization->id)->exists()) {
                    $user->roles()->attach($orgAdminRole->id, ['organization_id' => $organization->id]);
                }
            } else {
                $user->roles()->wherePivot('organization_id', $organization->id)->detach($orgAdminRole->id);
            }
        }

        AuditService::log('organization.role_updated', $organization, ['role' => $oldRole], ['role' => $validated['role']], $organization->id);

        return redirect()->back()->with('success', 'Função do usuário atualizada com sucesso.');
    }

    /**
     * Toggle active/inactive status of a member (Section 6)
     */
    public function toggleStatus(Request $request, string $userId): RedirectResponse
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403);
        }

        $this->authorizeAdmin($organization);

        if ($userId === $organization->owner_id) {
            return redirect()->back()->with('error', 'O proprietário da instituição não pode ser desativado.');
        }

        $membership = OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $newStatus = !$membership->is_active;
        OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->update(['is_active' => $newStatus]);

        $statusText = $newStatus ? 'ativado' : 'desativado';
        return redirect()->back()->with('success', "Usuário {$statusText} com sucesso.");
    }

    /**
     * Remove member from institution
     */
    public function removeMember(Request $request, string $userId): RedirectResponse
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403);
        }

        $this->authorizeAdmin($organization);

        if ($userId === $organization->owner_id) {
            return redirect()->back()->with('error', 'O proprietário da instituição não pode ser removido.');
        }

        DB::transaction(function () use ($organization, $userId) {
            // Remove from organization_user
            OrganizationUser::where('organization_id', $organization->id)
                ->where('user_id', $userId)
                ->delete();

            // Remove from all hackathons belonging to this organization
            $hackathonIds = $organization->hackathons()->pluck('id');
            \App\Models\HackathonMember::whereIn('hackathon_id', $hackathonIds)
                ->where('user_id', $userId)
                ->delete();

            // Detach RBAC roles for this organization
            $user = User::find($userId);
            if ($user) {
                $user->roles()->wherePivot('organization_id', $organization->id)->detach();
            }
        });

        AuditService::log('organization.member_removed', $organization, null, ['user_id' => $userId], $organization->id);

        return redirect()->back()->with('success', 'Vínculo do usuário com a instituição removido.');
    }

    /**
     * Cancel a pending invitation
     */
    public function cancelInvitation(Request $request, string $invitationId): RedirectResponse
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403);
        }

        $this->authorizeAdmin($organization);

        $invitation = OrganizationInvitation::where('id', $invitationId)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $invitation->delete();

        return redirect()->back()->with('success', 'Convite cancelado com sucesso.');
    }

    /**
     * Helper to verify admin permissions
     */
    private function authorizeAdmin(Organization $organization): void
    {
        $user = Auth::user();
        $isOwner = $organization->owner_id === $user->id;
        $isAdmin = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->wherePivot('role', 'admin')
            ->wherePivot('is_active', true)
            ->exists();

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Apenas administradores da instituição podem realizar esta ação.');
        }
    }
}
