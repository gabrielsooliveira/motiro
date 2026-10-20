<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\HackathonGroup;
use App\Models\HackathonRegistration;
use App\Models\Team;
use App\Models\User;
use App\Services\HackathonGroupService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HackathonGroupController extends Controller
{
    public function __construct(
        protected HackathonGroupService $groupService
    ) {}

    /**
     * Cria um novo grupo de participação para um hackathon
     */
    public function store(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'area_of_focus' => 'nullable|string|max:255',
            'technologies' => 'nullable|array',
            'set_as_leader' => 'nullable|boolean',
        ]);

        $leader = (!isset($validated['set_as_leader']) || $validated['set_as_leader']) ? $user : null;

        try {
            $group = $this->groupService->createGroup(
                $hackathon,
                $validated,
                $leader,
                [$user->id],
                $user
            );

            return redirect()->back()->with('success', "Grupo '{$group->name}' criado com sucesso para o Hackathon!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['group' => $e->getMessage()]);
        }
    }

    /**
     * Forma um grupo de participação a partir de uma equipe permanente (guilda)
     */
    public function formFromGuild(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $user = Auth::user();

        $validated = $request->validate([
            'team_id' => 'required|uuid|exists:teams,id',
            'name' => 'nullable|string|max:255',
        ]);

        $guild = Team::findOrFail($validated['team_id']);

        try {
            $group = $this->groupService->formGroupFromGuild(
                $hackathon,
                $guild,
                $user,
                $validated['name'] ?? null
            );

            return redirect()->back()->with('success', "Grupo '{$group->name}' formado com sucesso a partir da guilda '{$guild->name}'!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['group' => $e->getMessage()]);
        }
    }

    /**
     * Eleição ou alteração de líder do grupo de participação
     */
    public function electLeader(Request $request, string $groupId): RedirectResponse
    {
        $group = HackathonGroup::findOrFail($groupId);
        $user = Auth::user();

        $validated = $request->validate([
            'leader_id' => 'required|uuid|exists:users,id',
        ]);

        $newLeader = User::findOrFail($validated['leader_id']);

        try {
            $this->groupService->electLeader($group, $newLeader, $user);

            return redirect()->back()->with('success', "{$newLeader->name} foi definido como líder do grupo!");
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['leader' => $e->getMessage()]);
        }
    }

    /**
     * Membro sai do grupo de participação (retorna para inscrição individual)
     */
    public function leave(string $groupId): RedirectResponse
    {
        $group = HackathonGroup::with('hackathon')->findOrFail($groupId);
        $user = Auth::user();

        $registration = HackathonRegistration::where('hackathon_id', $group->hackathon_id)
            ->where('user_id', $user->id)
            ->where('hackathon_group_id', $group->id)
            ->firstOrFail();

        try {
            $this->groupService->removeParticipantFromGroup($registration, $user);

            return redirect()->back()->with('success', 'Você saiu do grupo de participação.');
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['group' => $e->getMessage()]);
        }
    }
}
