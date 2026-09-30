<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Hackathon;
use App\Models\HackathonChallenge;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HackathonChallengeController extends Controller
{
    public function store(Request $request, string $hackathonId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeManage($hackathon);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'prize' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:1',
        ]);

        $order = $validated['order'] ?? ($hackathon->challenges()->max('order') + 1);

        $challenge = HackathonChallenge::create([
            'hackathon_id' => $hackathon->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'requirements' => $validated['requirements'] ?? null,
            'prize' => $validated['prize'] ?? null,
            'order' => $order,
        ]);

        AuditService::log('challenge.created', $challenge, null, $challenge->toArray(), $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Desafio criado com sucesso!');
    }

    public function update(Request $request, string $hackathonId, string $challengeId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeManage($hackathon);

        $challenge = HackathonChallenge::where('hackathon_id', $hackathon->id)->findOrFail($challengeId);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'prize' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:1',
        ]);

        $challenge->update($validated);

        AuditService::log('challenge.updated', $challenge, null, $challenge->toArray(), $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Desafio atualizado com sucesso!');
    }

    public function destroy(string $hackathonId, string $challengeId): RedirectResponse
    {
        $hackathon = Hackathon::findOrFail($hackathonId);
        $this->authorizeManage($hackathon);

        $challenge = HackathonChallenge::where('hackathon_id', $hackathon->id)->findOrFail($challengeId);
        $challenge->delete();

        AuditService::log('challenge.deleted', $challenge, null, null, $hackathon->organization_id, $hackathon->id);

        return redirect()->back()->with('success', 'Desafio removido com sucesso!');
    }

    private function authorizeManage(Hackathon $hackathon): void
    {
        $user = Auth::user();
        if (!$user || !$user->canManageHackathon($hackathon)) {
            abort(403, 'Acesso não autorizado para gerenciar desafios deste Hackathon.');
        }
    }
}
