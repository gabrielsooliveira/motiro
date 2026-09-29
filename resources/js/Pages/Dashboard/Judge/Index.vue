<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    assignments: {
        type: Array,
        default: () => [],
    },
    evaluations: {
        type: Object,
        default: () => ({}),
    },
    conflicts: {
        type: Object,
        default: () => ({}),
    },
});

const conflictForm = useForm({
    hackathon_id: "",
    project_id: "",
    reason: "",
});

const declareConflict = (assignment) => {
    const reason = prompt("Informe o motivo do conflito de interesse (ex: vínculo com integrante):");
    if (!reason) return;

    conflictForm.hackathon_id = assignment.hackathon_id;
    conflictForm.project_id = assignment.project_id;
    conflictForm.reason = reason;

    conflictForm.post(route("judge.conflict.declare"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head>
        <title>Painel do Juiz | Avaliações</title>
    </Head>

    <div class="py-4">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h1 class="fw-bold mb-1">Painel de Avaliação do Juiz</h1>
                <p class="text-muted mb-0">Avalie os projetos atribuídos e registre eventuais conflitos de interesse.</p>
            </div>
            <Link :href="route('dashboard.index')" class="btn btn-outline-secondary">
                Voltar para Dashboard
            </Link>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div v-if="assignments.length === 0" class="text-center py-5 text-muted">
                    <h5>Nenhum projeto pendente de avaliação.</h5>
                    <p>Você concluiu todas as suas atribuições ou ainda não foi designado para projetos.</p>
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Hackathon</th>
                                <th>Projeto</th>
                                <th>Equipe</th>
                                <th>Minha Avaliação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="assign in assignments" :key="assign.id">
                                <td><strong>{{ assign.hackathon ? assign.hackathon.title : '-' }}</strong></td>
                                <td>{{ assign.project ? assign.project.title : 'Todos os Projetos' }}</td>
                                <td>{{ assign.project && assign.project.team ? assign.project.team.name : '-' }}</td>
                                <td>
                                    <span v-if="assign.status === 'completed'" class="badge bg-success">Concluída</span>
                                    <span v-else class="badge bg-warning text-dark">Pendente</span>
                                </td>
                                <td class="text-end">
                                    <template v-if="assign.project_id">
                                        <Link :href="route('judge.evaluate', assign.project_id)" class="btn btn-sm btn-primary me-2">
                                            {{ assign.status === 'completed' ? 'Revisar Avaliação' : 'Avaliar Agora' }}
                                        </Link>
                                        <button class="btn btn-sm btn-outline-danger" @click="declareConflict(assign)">
                                            Conflito
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
