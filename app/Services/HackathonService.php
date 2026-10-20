<?php

namespace App\Services;

use App\Models\EvaluationCriterion;
use App\Models\Hackathon;
use App\Models\HackathonMember;
use App\Models\HackathonStage;
use App\Models\Organization;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HackathonService
{
    /**
     * Cria um novo Hackathon com etapas e critérios personalizados ou defaults.
     */
    public function createHackathon(User $creator, array $data): Hackathon
    {
        $organizationId = $data['organization_id'];
        $isOrgAdmin = $creator->isOrganizationAdmin($organizationId)
            || Organization::where('id', $organizationId)->where('owner_id', $creator->id)->exists();

        if (!$isOrgAdmin) {
            throw new Exception('Apenas administradores da instituição podem criar hackathons.');
        }

        $regType = $data['registration_type'] ?? 'individual_or_team';
        $data['registration_type'] = $regType;
        $data['requires_team'] = ($regType === 'team_only');

        $slug = Str::slug($data['title']);
        $count = 1;
        while (Hackathon::where('slug', $slug)->exists()) {
            $slug = Str::slug($data['title']) . '-' . $count;
            $count++;
        }
        $data['slug'] = $slug;
        $data['status'] = $data['status'] ?? 'draft';

        if ($data['status'] === 'registration') {
            if (empty($data['registration_start_date']) || \Carbon\Carbon::parse($data['registration_start_date'])->isFuture()) {
                $data['registration_start_date'] = now();
            }
        }

        $stagesData = $data['stages'] ?? null;
        $criteriaData = $data['criteria'] ?? null;
        unset($data['stages'], $data['criteria']);

        return DB::transaction(function () use ($creator, $data, $stagesData, $criteriaData) {
            $hackathon = Hackathon::create($data);

            // Vincula criador como admin do hackathon
            HackathonMember::create([
                'hackathon_id' => $hackathon->id,
                'user_id' => $creator->id,
                'role' => 'admin',
            ]);

            // Criação das etapas com prazos
            if (!empty($stagesData)) {
                foreach ($stagesData as $index => $stage) {
                    HackathonStage::create([
                        'hackathon_id' => $hackathon->id,
                        'name' => $stage['name'],
                        'description' => $stage['description'] ?? null,
                        'start_date' => $stage['start_date'],
                        'end_date' => $stage['end_date'],
                        'order' => $stage['order'] ?? ($index + 1),
                        'allowed_actions' => $stage['allowed_actions'] ?? null,
                        'status' => 'upcoming',
                        'visibility' => 'public',
                    ]);
                }
            } else {
                $this->createDefaultStages($hackathon);
            }

            // Criação dos critérios e pesos de entrega
            if (!empty($criteriaData)) {
                foreach ($criteriaData as $index => $crit) {
                    $weight = (float)$crit['weight'];
                    if ($weight > 1) {
                        $weight = $weight / 100;
                    }
                    EvaluationCriterion::create([
                        'hackathon_id' => $hackathon->id,
                        'name' => $crit['name'],
                        'description' => $crit['description'] ?? null,
                        'weight' => $weight,
                        'min_score' => $crit['min_score'] ?? 0,
                        'max_score' => $crit['max_score'] ?? 10,
                        'is_required' => true,
                        'order' => $crit['order'] ?? ($index + 1),
                    ]);
                }
            } else {
                $this->createDefaultCriteria($hackathon);
            }

            AuditService::log('hackathon.create', $hackathon, null, $hackathon->toArray(), $hackathon->organization_id, $hackathon->id);

            return $hackathon;
        });
    }

    /**
     * Atualiza as configurações de um Hackathon
     */
    public function updateHackathon(Hackathon $hackathon, array $data, User $actor): Hackathon
    {
        $this->authorizeAdmin($hackathon, $actor);

        if (isset($data['registration_type'])) {
            $data['requires_team'] = ($data['registration_type'] === 'team_only');
        }

        $oldValues = $hackathon->toArray();
        $hackathon->update($data);

        AuditService::log('hackathon.update', $hackathon, $oldValues, $hackathon->toArray(), $hackathon->organization_id, $hackathon->id);

        return $hackathon;
    }

    /**
     * Atualiza o status do Hackathon (draft, published, registration, submission, voting, finished).
     */
    public function updateStatus(Hackathon $hackathon, string $status, User $actor): Hackathon
    {
        $this->authorizeAdmin($hackathon, $actor);

        $oldValues = ['status' => $hackathon->status];
        $updates = ['status' => $status];

        if ($status === 'registration' && $hackathon->registration_start_date && $hackathon->registration_start_date->isFuture()) {
            $updates['registration_start_date'] = now();
        }

        $hackathon->update($updates);

        AuditService::log('hackathon.status_update', $hackathon, $oldValues, $updates, $hackathon->organization_id, $hackathon->id);

        return $hackathon;
    }

    /**
     * Adiciona uma nova etapa ao cronograma com prazos
     */
    public function storeStage(Hackathon $hackathon, array $data, User $actor): HackathonStage
    {
        $this->authorizeAdmin($hackathon, $actor);

        $data['hackathon_id'] = $hackathon->id;
        $data['order'] = $data['order'] ?? (($hackathon->stages()->max('order') ?? 0) + 1);
        $data['status'] = 'upcoming';

        $stage = HackathonStage::create($data);
        AuditService::log('stage.create', $stage, null, $stage->toArray(), $hackathon->organization_id, $hackathon->id);

        return $stage;
    }

    /**
     * Atualiza os prazos e dados de uma etapa
     */
    public function updateStage(HackathonStage $stage, array $data, User $actor): HackathonStage
    {
        $this->authorizeAdmin($stage->hackathon, $actor);

        $old = $stage->toArray();
        $stage->update($data);
        AuditService::log('stage.update', $stage, $old, $stage->toArray(), $stage->hackathon->organization_id, $stage->hackathon_id);

        return $stage;
    }

    /**
     * Remove uma etapa de forma segura
     */
    public function deleteStage(HackathonStage $stage, User $actor): void
    {
        $this->authorizeAdmin($stage->hackathon, $actor);

        $stage->delete();
        AuditService::log('stage.delete', $stage, $stage->toArray(), null, $stage->hackathon->organization_id, $stage->hackathon_id);
    }

    /**
     * Adiciona um critério de avaliação / peso de entrega
     */
    public function storeCriterion(Hackathon $hackathon, array $data, User $actor): EvaluationCriterion
    {
        $this->authorizeAdmin($hackathon, $actor);

        $weight = (float)$data['weight'];
        if ($weight > 1) {
            $weight = $weight / 100;
        }

        $data['weight'] = $weight;
        $data['hackathon_id'] = $hackathon->id;
        $data['min_score'] = $data['min_score'] ?? 0;
        $data['max_score'] = $data['max_score'] ?? 10;
        $data['order'] = $data['order'] ?? (($hackathon->evaluationCriteria()->max('order') ?? 0) + 1);

        $criterion = EvaluationCriterion::create($data);
        AuditService::log('criterion.create', $criterion, null, $criterion->toArray(), $hackathon->organization_id, $hackathon->id);

        return $criterion;
    }

    /**
     * Atualiza um critério de avaliação / peso de entrega
     */
    public function updateCriterion(EvaluationCriterion $criterion, array $data, User $actor): EvaluationCriterion
    {
        $this->authorizeAdmin($criterion->hackathon, $actor);

        if (isset($data['weight'])) {
            $weight = (float)$data['weight'];
            if ($weight > 1) {
                $weight = $weight / 100;
            }
            $data['weight'] = $weight;
        }

        $old = $criterion->toArray();
        $criterion->update($data);
        AuditService::log('criterion.update', $criterion, $old, $criterion->toArray(), $criterion->hackathon->organization_id, $criterion->hackathon_id);

        return $criterion;
    }

    /**
     * Remove um critério de avaliação
     */
    public function deleteCriterion(EvaluationCriterion $criterion, User $actor): void
    {
        $this->authorizeAdmin($criterion->hackathon, $actor);

        $criterion->delete();
        AuditService::log('criterion.delete', $criterion, $criterion->toArray(), null, $criterion->hackathon->organization_id, $criterion->hackathon_id);
    }

    /**
     * Publica oficialmente os resultados e finaliza o hackathon
     */
    public function publishResults(Hackathon $hackathon, User $actor): void
    {
        $this->authorizeAdmin($hackathon, $actor);

        $hackathon->update(['results_published' => true, 'status' => 'finished']);
        AuditService::log('hackathon.publish_results', $hackathon, null, ['results_published' => true], $hackathon->organization_id, $hackathon->id);
    }

    private function authorizeAdmin(Hackathon $hackathon, User $actor): void
    {
        if (!$actor->canManageHackathon($hackathon)) {
            throw new Exception('Acesso não autorizado para gerenciar este Hackathon.');
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
