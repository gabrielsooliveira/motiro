<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
    teamsCount: {
        type: Number,
        default: 0,
    },
    userTeams: {
        type: Array,
        default: () => [],
    },
    userRegisteredTeamId: {
        type: String,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.user);
const activeTab = ref("overview");
const showRegisterModal = ref(false);

const registrationForm = useForm({
    hackathon_id: props.hackathon.id,
    team_id: props.userTeams?.[0]?.id || "",
});

const isUserRegistered = computed(() => !!props.userRegisteredTeamId);

const submitRegistration = () => {
    registrationForm.post(route("registration.store"), {
        onSuccess: () => {
            showRegisterModal.value = false;
        },
    });
};

const getStatusBadge = (status) => {
    const map = {
        published: "bg-success",
        registration: "bg-primary",
        submission: "bg-warning text-dark",
        voting: "bg-info text-dark",
        finished: "bg-secondary",
        draft: "bg-light text-dark border",
    };
    return map[status] || "bg-secondary";
};
</script>

<template>
    <Head>
        <title>Hackathons |{{ hackathon.title }}</title>
    </Head>

    <div class="container py-4">
        <!-- Breadcrumb & Back Link -->
        <div class="mb-3">
            <Link :href="route('hackathon.index')" class="text-decoration-none text-muted fw-semibold">
                <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                Voltar para lista de Hackathons
            </Link>
        </div>

        <!-- Banner Card -->
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
            <div class="position-relative">
                <img
                    :src="hackathon.banner_url || 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200&auto=format&fit=crop&q=80'"
                    class="w-100 object-fit-cover"
                    alt="Banner do Hackathon"
                    style="height: 260px;"
                />
                <div class="position-absolute top-0 end-0 p-3">
                    <span class="badge" :class="getStatusBadge(hackathon.status)">
                        {{ hackathon.status }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-light text-dark border text-capitalize">
                                <font-awesome-icon :icon="['fas', 'location-dot']" class="me-1" />
                                {{ hackathon.modality }}
                            </span>
                            <span v-if="hackathon.category" class="badge bg-primary bg-opacity-10 text-primary">
                                {{ hackathon.category }}
                            </span>
                        </div>

                        <h1 class="fw-bold mb-2">{{ hackathon.title }}</h1>
                        <p class="text-muted mb-3">{{ hackathon.description || 'Nenhuma descrição fornecida.' }}</p>

                        <div class="d-flex flex-wrap gap-4 text-secondary small">
                            <div>
                                <font-awesome-icon :icon="['fas', 'building']" class="me-1" />
                                <strong>Organização:</strong> {{ hackathon.organization ? hackathon.organization.name : 'Motirõ' }}
                            </div>
                            <div>
                                <font-awesome-icon :icon="['fas', 'users']" class="me-1" />
                                <strong>Equipes Aprovadas:</strong> {{ teamsCount }}
                            </div>
                            <div>
                                <font-awesome-icon :icon="['fas', 'user-group']" class="me-1" />
                                <strong>Integrantes por equipe:</strong> {{ hackathon.min_team_members }} a {{ hackathon.max_team_members }}
                            </div>
                        </div>
                    </div>

                    <!-- Call to action: Inscrição -->
                    <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                        <template v-if="!user">
                            <Link :href="route('login')" class="btn btn-primary btn-lg shadow-sm">
                                <font-awesome-icon :icon="['fas', 'right-to-bracket']" class="me-2" />
                                Entrar para se Inscrever
                            </Link>
                        </template>

                        <template v-else-if="isUserRegistered">
                            <div class="alert alert-success d-inline-block text-start mb-0 py-2 px-3">
                                <font-awesome-icon :icon="['fas', 'circle-check']" class="me-1" />
                                <strong>Sua equipe já está inscrita!</strong>
                            </div>
                        </template>

                        <template v-else>
                            <button
                                v-if="userTeams.length > 0"
                                class="btn btn-primary btn-lg shadow-sm"
                                @click="showRegisterModal = true"
                            >
                                <font-awesome-icon :icon="['fas', 'user-plus']" class="me-2" />
                                Inscrever Minha Equipe
                            </button>
                            <div v-else class="text-lg-end">
                                <Link :href="route('dashboard.index')" class="btn btn-primary shadow-sm">
                                    Criar Equipe no Dashboard para Inscrever
                                </Link>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs for Hackathon Details -->
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'overview' }"
                    @click="activeTab = 'overview'"
                >
                    Visão Geral & Regulamento
                </button>
            </li>
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'stages' }"
                    @click="activeTab = 'stages'"
                >
                    Cronograma & Etapas ({{ hackathon.stages ? hackathon.stages.length : 0 }})
                </button>
            </li>
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'criteria' }"
                    @click="activeTab = 'criteria'"
                >
                    Critérios de Avaliação ({{ hackathon.evaluation_criteria ? hackathon.evaluation_criteria.length : 0 }})
                </button>
            </li>
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'teams' }"
                    @click="activeTab = 'teams'"
                >
                    Equipes Confirmadas ({{ teamsCount }})
                </button>
            </li>
        </ul>

        <!-- Tab 1: Overview & Rules -->
        <div v-if="activeTab === 'overview'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3">Regulamento e Diretrizes</h4>
                <div v-if="hackathon.rules" class="text-secondary lh-lg mb-4" style="white-space: pre-line;">
                    {{ hackathon.rules }}
                </div>
                <div v-else class="text-muted">
                    Nenhum regulamento detalhado cadastrado pela organização.
                </div>

                <div class="border-top pt-4 mt-4 row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-1">Período de Inscrição</h6>
                            <p class="mb-0 text-muted small">
                                {{ hackathon.registration_start_date ? new Date(hackathon.registration_start_date).toLocaleDateString() : 'Aberto' }} até
                                {{ hackathon.registration_end_date ? new Date(hackathon.registration_end_date).toLocaleDateString() : 'Encerramento' }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-1">Submissão dos Projetos</h6>
                            <p class="mb-0 text-muted small">
                                {{ hackathon.submission_start_date ? new Date(hackathon.submission_start_date).toLocaleDateString() : 'Durante o evento' }} até
                                {{ hackathon.submission_end_date ? new Date(hackathon.submission_end_date).toLocaleDateString() : 'Prazo final' }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-1">Contato Oficial</h6>
                            <p class="mb-0 text-muted small">
                                {{ hackathon.contact_email || 'contato@motiro.test' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Stages Timeline -->
        <div v-if="activeTab === 'stages'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div v-if="!hackathon.stages || hackathon.stages.length === 0" class="text-muted text-center py-4">
                    Nenhuma etapa cadastrada para este hackathon.
                </div>
                <div v-else>
                    <div v-for="stage in hackathon.stages" :key="stage.id" class="timeline-stage-item">
                        <h5 class="fw-bold mb-1">{{ stage.name }}</h5>
                        <div class="badge bg-light text-dark border mb-2">
                            {{ new Date(stage.start_date).toLocaleDateString() }} às {{ new Date(stage.start_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                            — {{ new Date(stage.end_date).toLocaleDateString() }} às {{ new Date(stage.end_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                        </div>
                        <p class="text-secondary mb-0">{{ stage.description || 'Etapa do cronograma oficial.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Evaluation Criteria -->
        <div v-if="activeTab === 'criteria'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div v-if="!hackathon.evaluation_criteria || hackathon.evaluation_criteria.length === 0" class="text-muted text-center py-4">
                    Nenhum critério de avaliação cadastrado.
                </div>
                <div v-else class="row g-3">
                    <div v-for="crit in hackathon.evaluation_criteria" :key="crit.id" class="col-md-6">
                        <div class="evaluation-criteria-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0">{{ crit.name }}</h6>
                                <span class="badge bg-primary">Peso: {{ (crit.weight * 100).toFixed(0) }}%</span>
                            </div>
                            <small class="text-muted">Faixa de notas: {{ crit.min_score }} a {{ crit.max_score }}</small>
                            <p class="mb-0 mt-2 text-secondary">{{ crit.description || 'Critério oficial considerado pelos jurados.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 4: Teams -->
        <div v-if="activeTab === 'teams'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div v-if="!hackathon.registrations || hackathon.registrations.length === 0" class="text-muted text-center py-4">
                    Nenhuma equipe confirmada até o momento. Seja a primeira a se inscrever!
                </div>
                <div v-else class="row g-3">
                    <div v-for="reg in hackathon.registrations.filter(r => r.status === 'approved')" :key="reg.id" class="col-md-6 col-lg-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <h6 class="fw-bold mb-1">{{ reg.team ? reg.team.name : 'Equipe' }}</h6>
                            <small class="text-muted">{{ reg.team?.members?.length || 0 }} membro(s)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Team Registration -->
        <div v-if="showRegisterModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitRegistration">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Inscrever Equipe</h5>
                            <button type="button" class="btn-close" @click="showRegisterModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Selecione sua Equipe</label>
                                <select v-model="registrationForm.team_id" class="form-select" required>
                                    <option v-for="team in userTeams" :key="team.id" :value="team.id">
                                        {{ team.name }} ({{ team.members ? team.members.length : 1 }} integrante(s))
                                    </option>
                                </select>
                                <small class="text-muted">Apenas equipes das quais você é líder são elegíveis.</small>
                            </div>

                            <div v-if="registrationForm.errors.registration" class="alert alert-danger py-2 small">
                                {{ registrationForm.errors.registration }}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showRegisterModal = false">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="registrationForm.processing">
                                Confirmar Inscrição
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
