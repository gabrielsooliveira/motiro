<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\HackathonMember;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HackathonMemberController extends Controller
{
    /**
     * Add staff member to hackathon (Sections 9 & 10)
     */
    public function store(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::with('organization')->findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id',
            'role' => 'required|in:admin,manager,collaborator',
        ]);

        $userId = $validated['user_id'];
        $role = $validated['role'];

        // Verify that the user belongs to the institution
        $belongsToOrg = $hackathon->organization->users()
            ->where('users.id', $userId)
            ->wherePivot('is_active', true)
            ->exists();

        if (!$belongsToOrg && $hackathon->organization->owner_id !== $userId) {
            return redirect()->back()->with('error', 'O usuário deve primeiro pertencer à instituição antes de ser adicionado ao hackathon.');
        }

        // Check if already in hackathon staff
        $existing = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->update(['role' => $role]);
            return redirect()->back()->with('success', 'Função do responsável atualizada com sucesso!');
        }

        $member = HackathonMember::create([
            'hackathon_id' => $hackathon->id,
            'user_id' => $userId,
            'role' => $role,
        ]);

        $user = User::find($userId);
        NotificationService::notify(
            $user,
            'hackathon_staff_assigned',
            'Designação em Hackathon',
            "Você foi designado como " . ucfirst($role) . " no Hackathon '{$hackathon->title}'.",
            route('hackathon.manage.index', $hackathon->id)
        );

        AuditService::log('hackathon.staff_added', $hackathon, null, ['user_id' => $userId, 'role' => $role], $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', "Usuário {$user->name} adicionado à equipe responsável do Hackathon!");
    }

    /**
     * Update role of staff member
     */
    public function update(Request $request, string $hackathonId, string $memberId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $validated = $request->validate([
            'role' => 'required|in:admin,manager,collaborator',
        ]);

        $member = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('id', $memberId)
            ->firstOrFail();

        $oldRole = $member->role;
        $member->update(['role' => $validated['role']]);

        AuditService::log('hackathon.staff_updated', $hackathon, ['role' => $oldRole], ['role' => $validated['role']], $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Função do responsável atualizada com sucesso!');
    }

    /**
     * Remove staff member from hackathon
     */
    public function destroy(string $hackathonId, string $memberId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeAdmin($hackathon);

        $member = HackathonMember::where('hackathon_id', $hackathon->id)
            ->where('id', $memberId)
            ->firstOrFail();

        $userId = $member->user_id;
        $member->delete();

        AuditService::log('hackathon.staff_removed', $hackathon, null, ['user_id' => $userId], $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Responsável removido da equipe do Hackathon.');
    }

    private function authorizeAdmin(Hackathon $hackathon): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        if (!\Illuminate\Support\Facades\Gate::allows('manageStaff', $hackathon)) {
            abort(403, 'Acesso não autorizado para gerenciar a equipe deste Hackathon.');
        }
    }
}
