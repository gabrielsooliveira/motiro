<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\AuditService;
use App\Services\ContextService;
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
     * List users and invitations of the active institution
     */
    public function index(Request $request): Response
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403, 'Organização ativa não encontrada.');
        }

        $user = Auth::user();
        $isOrgAdmin = $user->isOrganizationAdmin($organization->id) || $organization->owner_id === $user->id;

        // Members of this organization
        $members = $organization->users()
            ->get()
            ->map(function ($member) use ($organization) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'role' => $member->pivot->role,
                    'is_active' => (bool) $member->pivot->is_active,
                    'is_owner' => $member->id === $organization->owner_id,
                    'joined_at' => $member->pivot->created_at?->format('d/m/Y'),
                    'assigned_hackathons' => [],
                ];
            });

        // Pending invitations
        $invitations = $organization->invitations()
            ->where('status', 'pending')
            ->with('invitedBy:id,name')
            ->latest()
            ->get();

        // Hackathons of the org
        $orgHackathons = $organization->hackathons()->select('id', 'title')->get();

        return Inertia::render('Organization/Dashboard/Organization/Users', [
            'organization' => $organization->only(['id', 'name', 'slug']),
            'members' => $members,
            'invitations' => $invitations,
            'hackathons' => $orgHackathons,
            'is_org_admin' => $isOrgAdmin,
        ]);
    }

    /**
     * Invite a user to the institution
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
            'role' => 'required|in:owner,admin,manager,collaborator',
        ]);

        $email = strtolower(trim($validated['email']));
        $role = $validated['role'];

        // Check if user is already an active member
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            $isAlreadyMember = $organization->users()
                ->where('users.id', $existingUser->id)
                ->wherePivot('is_active', true)
                ->exists();

            if ($isAlreadyMember) {
                return redirect()->back()->with('error', 'Este usuário já é um membro ativo da instituição.');
            }

            // Link existing user directly
            OrganizationUser::updateOrCreate(
                [
                    'organization_id' => $organization->id,
                    'user_id' => $existingUser->id,
                ],
                [
                    'role' => $role,
                    'is_active' => true,
                ]
            );

            return redirect()->back()->with('success', "Usuário {$existingUser->name} vinculado com sucesso à organização.");
        }

        // Check for existing pending invitation
        $existingInvite = OrganizationInvitation::where('organization_id', $organization->id)
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();

        if ($existingInvite) {
            return redirect()->back()->with('error', 'Já existe um convite pendente para este e-mail.');
        }

        // Create secure invitation
        $token = Str::random(40);
        OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'email' => $email,
            'role' => $role,
            'invited_by' => Auth::id(),
            'token' => $token,
            'status' => 'pending',
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        AuditService::log('organization.invite_sent', $organization, null, ['email' => $email, 'role' => $role], $organization->id);

        return redirect()->back()->with('success', "Convite enviado com sucesso para {$email}.");
    }

    /**
     * Update a member's role
     */
    public function updateRole(Request $request, string $userId): RedirectResponse
    {
        $organization = ContextService::getActiveOrganization($request);
        if (!$organization) {
            abort(403);
        }

        $this->authorizeAdmin($organization);

        $validated = $request->validate([
            'role' => 'required|in:owner,admin,manager,collaborator',
        ]);

        if ($userId === $organization->owner_id && $validated['role'] !== 'owner') {
            return redirect()->back()->with('error', 'O proprietário da instituição não pode ter seu papel alterado.');
        }

        $membership = OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        $oldRole = $membership->role;
        $membership->update(['role' => $validated['role']]);

        AuditService::log('organization.role_updated', $organization, ['role' => $oldRole], ['role' => $validated['role']], $organization->id);

        return redirect()->back()->with('success', 'Função do usuário atualizada com sucesso.');
    }

    /**
     * Toggle active/inactive status of a member
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
        $membership->update(['is_active' => $newStatus]);

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
            OrganizationUser::where('organization_id', $organization->id)
                ->where('user_id', $userId)
                ->delete();
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
