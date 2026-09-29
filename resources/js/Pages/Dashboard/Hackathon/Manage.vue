<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
    rankings: {
        type: Array,
        default: null,
    },
});

const activeTab = ref("registrations");

const handleStatusUpdate = (registrationId, status) => {
    router.patch(route("registration.status.update", registrationId), {
        status: status,
    });
};

const handleDistributeJudging = () => {
    if (confirm("Deseja distribuir os projetos submetidos entre os juízes cadastrados?")) {
        router.post(route("hackathon.manage.distribute", props.hackathon.id));
    }
};

const handlePublishResults = () => {
    if (confirm("Deseja publicar os resultados e finalizar este Hackathon?")) {
        router.post(route("hackathon.manage.publish", props.hackathon.id));
    }
};
</script>

<template>
    <Head>
        <title>Gerenciar | {{ hackathon.title }}</title>
    </Head>

    <div class="py-4">
        <!-- Hackathon Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <Link :href="route('dashboard.index')" class="text-decoration-none text-muted mb-1 d-inline-block">
                    &larr; Voltar para o Dashboard
                </Link>
                <h1 class="fw-bold mb-0">{{ hackathon.title }}</h1>
                <span class="badge bg-primary text-uppercase">{{ hackathon.modality }}</span>
                <span class="badge bg-secondary ms-2 text-uppercase">{{ hackathon.status }}</span>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-info" @click="handleDistributeJudging">
                    <font-awesome-icon :icon="['fas', 'share-nodes']" class="me-1" />
                    Distribuir para Juízes
                </button>
                <button class="btn btn-success" @click="handlePublishResults">
                    <font-awesome-icon :icon="['fas', 'award']" class="me-1" />
                    Publicar Resultados
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <button class="nav-link" :class="{ active: activeTab === 'registrations' }" @click="activeTab = 'registrations'">
                    Inscrições ({{ hackathon.registrations ? hackathon.registrations.length : 0 }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: activeTab === 'projects' }" @click="activeTab = 'projects'">
                    Projetos ({{ hackathon.projects ? hackathon.projects.length : 0 }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: activeTab === 'stages' }" @click="activeTab = 'stages'">
                    Etapas ({{ hackathon.stages ? hackathon.stages.length : 0 }})
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" :class="{ active: activeTab === 'criteria' }" @click="activeTab = 'criteria'">
                    Critérios de Avaliação ({{ hackathon.evaluation_criteria ? hackathon.evaluation_criteria.length : 0 }})
                </button>
            </li>
            <li v-if="rankings" class="nav-item">
                <button class="nav-link" :class="{ active: activeTab === 'rankings' }" @click="activeTab = 'rankings'">
                    Ranking Oficial
                </button>
            </li>
        </ul>

        <!-- Tab 1: Registrations Management -->
        <div v-if="activeTab === 'registrations'" class="card shadow-sm border-0">
            <div class="card-body">
                <div v-if="!hackathon.registrations || hackathon.registrations.length === 0" class="text-center py-4 text-muted">
                    Nenhuma inscrição registrada até o momento.
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Equipe</th>
                                <th>Integrantes</th>
                                <th>Status</th>
                                <th class="text-end">Ações Administrativas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reg in hackathon.registrations" :key="reg.id">
                                <td><strong>{{ reg.team ? reg.team.name : 'Equipe' }}</strong></td>
                                <td>{{ reg.team && reg.team.members ? reg.team.members.length : '-' }} membro(s)</td>
                                <td>
                                    <span class="badge" :class="{
                                        'bg-success': reg.status === 'approved',
                                        'bg-warning text-dark': reg.status === 'pending',
                                        'bg-danger': reg.status === 'rejected',
                                        'bg-dark': reg.status === 'disqualified',
                                    }">{{ reg.status }}</span>
                                </td>
                                <td class="text-end">
                                    <button v-if="reg.status !== 'approved'" class="btn btn-sm btn-success me-2" @click="handleStatusUpdate(reg.id, 'approved')">
                                        Aprovar
                                    </button>
                                    <button v-if="reg.status !== 'rejected'" class="btn btn-sm btn-outline-danger me-2" @click="handleStatusUpdate(reg.id, 'rejected')">
                                        Rejeitar
                                    </button>
                                    <button v-if="reg.status !== 'disqualified'" class="btn btn-sm btn-dark" @click="handleStatusUpdate(reg.id, 'disqualified')">
                                        Desclassificar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 2: Projects -->
        <div v-if="activeTab === 'projects'" class="card shadow-sm border-0">
            <div class="card-body">
                <div v-if="!hackathon.projects || hackathon.projects.length === 0" class="text-center py-4 text-muted">
                    Nenhum projeto submetido até o momento.
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Projeto</th>
                                <th>Equipe</th>
                                <th>Status</th>
                                <th>Links</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="proj in hackathon.projects" :key="proj.id">
                                <td><strong>{{ proj.title }}</strong></td>
                                <td>{{ proj.team ? proj.team.name : '-' }}</td>
                                <td><span class="badge bg-primary">{{ proj.status }}</span></td>
                                <td>
                                    <a v-if="proj.github_repository_url" :href="proj.github_repository_url" target="_blank" class="btn btn-sm btn-outline-dark me-2">GitHub</a>
                                    <a v-if="proj.demo_url" :href="proj.demo_url" target="_blank" class="btn btn-sm btn-outline-primary">Demo</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab 3: Stages -->
        <div v-if="activeTab === 'stages'" class="card shadow-sm border-0">
            <div class="card-body">
                <div v-for="stage in hackathon.stages" :key="stage.id" class="timeline-stage-item">
                    <h6 class="fw-bold mb-1">{{ stage.name }}</h6>
                    <small class="text-muted d-block mb-1">
                        {{ new Date(stage.start_date).toLocaleDateString() }} até {{ new Date(stage.end_date).toLocaleDateString() }}
                    </small>
                    <p class="mb-0 text-secondary">{{ stage.description || 'Sem descrição adicional.' }}</p>
                </div>
            </div>
        </div>

        <!-- Tab 4: Evaluation Criteria -->
        <div v-if="activeTab === 'criteria'" class="card shadow-sm border-0">
            <div class="card-body">
                <div class="row g-3">
                    <div v-for="crit in hackathon.evaluation_criteria" :key="crit.id" class="col-md-6">
                        <div class="evaluation-criteria-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0">{{ crit.name }}</h6>
                                <span class="badge bg-info text-dark">Peso: {{ (crit.weight * 100).toFixed(0) }}%</span>
                            </div>
                            <small class="text-muted">Notas de {{ crit.min_score }} até {{ crit.max_score }}</small>
                            <p class="mb-0 mt-2 text-secondary">{{ crit.description || 'Critério padrão de avaliação.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 5: Official Rankings -->
        <div v-if="activeTab === 'rankings' && rankings" class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Posição</th>
                                <th>Projeto</th>
                                <th>Equipe</th>
                                <th>Avaliações</th>
                                <th>Nota Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, idx) in rankings" :key="item.project.id">
                                <td><strong class="fs-5">#{{ idx + 1 }}</strong></td>
                                <td>{{ item.project.title }}</td>
                                <td>{{ item.project.team ? item.project.team.name : '-' }}</td>
                                <td>{{ item.evaluations_count }}</td>
                                <td><span class="badge bg-success fs-6">{{ item.final_score }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
