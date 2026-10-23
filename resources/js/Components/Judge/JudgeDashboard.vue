<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    assignments: {
        type: Array,
        default: () => [],
    },
    pendingCount: {
        type: Number,
        default: 0,
    },
    completedCount: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <div class="judge-section">
        <div class="row g-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'gavel']" class="text-info me-2" />
                            Painel de Avaliação (Juiz)
                        </h5>
                        <div class="d-flex gap-2">
                            <span class="badge bg-warning text-dark">{{ pendingCount }} pendente(s)</span>
                            <span class="badge bg-success">{{ completedCount }} concluída(s)</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div v-if="assignments.length === 0" class="text-center py-4 text-muted">
                            <p class="mb-0">Você não possui projetos atribuídos para avaliação no momento.</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Hackathon</th>
                                        <th>Projeto</th>
                                        <th>Equipe</th>
                                        <th>Status</th>
                                        <th class="text-end">Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="assign in assignments" :key="assign.id">
                                        <td><strong>{{ assign.hackathon ? assign.hackathon.title : '-' }}</strong></td>
                                        <td>{{ assign.project ? assign.project.title : 'Projeto Geral' }}</td>
                                        <td>{{ assign.project && assign.project.team ? assign.project.team.name : '-' }}</td>
                                        <td>
                                            <span v-if="assign.status === 'completed'" class="badge bg-success">Avaliado</span>
                                            <span v-else class="badge bg-warning text-dark">Pendente</span>
                                        </td>
                                        <td class="text-end">
                                            <Link v-if="assign.project_id" :href="route('judge.evaluate', assign.project_id)" class="btn btn-sm btn-primary">
                                                Avaliar
                                            </Link>
                                            <Link v-else :href="route('judge.index')" class="btn btn-sm btn-outline-secondary">
                                                Ver Fila
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
