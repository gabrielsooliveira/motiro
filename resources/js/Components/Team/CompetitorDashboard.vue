<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    teams: {
        type: Array,
        default: () => [],
    },
    registrations: {
        type: Array,
        default: () => [],
    },
    invitations: {
        type: Array,
        default: () => [],
    },
    projects: {
        type: Array,
        default: () => [],
    },
});

defineEmits(["accept-invite", "decline-invite", "create-team", "invite-member"]);

const page = usePage();
const currentUser = computed(() => page.props.user);

// Primary active team (or first team)
const primaryTeam = computed(() => props.teams[0] || null);

// Primary registration for active team
const activeRegistration = computed(() => {
    if (!primaryTeam.value || !primaryTeam.value.registrations) return null;
    return primaryTeam.value.registrations[0] || null;
});

// Active hackathon linked to team
const activeHackathon = computed(() => {
    return activeRegistration.value ? activeRegistration.value.hackathon : null;
});

// Primary project
const teamProject = computed(() => {
    if (!primaryTeam.value) return null;
    return props.projects.find((p) => p.team_id === primaryTeam.value.id) || null;
});

// Check if current user is leader of primary team
const isLeader = computed(() => {
    if (!primaryTeam.value || !currentUser.value) return false;
    return primaryTeam.value.leader_id === currentUser.value.id;
});

// Stages for the active hackathon
const stages = computed(() => {
    return activeHackathon.value?.stages || [];
});

// Compute current active stage
const currentStage = computed(() => {
    if (!stages.value || stages.value.length === 0) return null;
    const now = new Date();
    // Try to find stage where now is between start and end
    const active = stages.value.find((s) => {
        const start = new Date(s.start_date);
        const end = new Date(s.end_date);
        return now >= start && now <= end;
    });
    return active || stages.value[0];
});

// Days remaining for current stage
const stageDaysRemaining = computed(() => {
    if (!currentStage.value) return null;
    const now = new Date();
    const end = new Date(currentStage.value.end_date);
    const diffTime = end - now;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
});

// Sorted upcoming deadlines
const upcomingDeadlines = computed(() => {
    const deadlines = [];
    if (activeHackathon.value) {
        if (activeHackathon.value.registration_end_date) {
            deadlines.push({
                title: "Encerramento das Inscrições",
                date: new Date(activeHackathon.value.registration_end_date),
                type: "warning",
            });
        }
        if (activeHackathon.value.submission_end_date) {
            deadlines.push({
                title: "Prazo Final de Submissão do Projeto",
                date: new Date(activeHackathon.value.submission_end_date),
                type: "danger",
            });
        }
    }

    stages.value.forEach((s) => {
        deadlines.push({
            title: `Término da etapa: ${s.name}`,
            date: new Date(s.end_date),
            type: "info",
        });
    });

    const now = new Date();
    return deadlines
        .filter((d) => d.date >= now)
        .sort((a, b) => a.date - b.date)
        .slice(0, 4);
});

const getStageStatus = (stage) => {
    const now = new Date();
    const start = new Date(stage.start_date);
    const end = new Date(stage.end_date);

    if (now > end) return "completed";
    if (now >= start && now <= end) return "current";
    return "upcoming";
};
</script>

<template>
    <div class="competitor-hub">
        <!-- 1. Pending Team Invitations Alert -->
        <div v-if="invitations.length > 0" class="alert alert-primary d-flex align-items-center justify-content-between mb-4 shadow-sm border-0">
            <div>
                <h6 class="fw-bold mb-1">
                    <font-awesome-icon :icon="['fas', 'envelope']" class="me-2 text-primary" />
                    Você possui {{ invitations.length }} convite(s) pendente(s) para ingressar em equipes!
                </h6>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <div v-for="invite in invitations" :key="invite.id" class="badge bg-white text-dark border p-2 d-flex align-items-center gap-2">
                        <span>Equipe: <strong>{{ invite.team ? invite.team.name : 'Equipe' }}</strong></span>
                        <button class="btn btn-sm btn-success py-0 px-2" @click="$emit('accept-invite', invite.id)">Aceitar</button>
                        <button class="btn btn-sm btn-outline-danger py-0 px-2" @click="$emit('decline-invite', invite.id)">Recusar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Status da Participação Rápido (Se o competidor possui equipe cadastrada) -->
        <div v-if="primaryTeam" class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-3">
                <div class="row g-3 text-center text-md-start align-items-center">
                    <div class="col-md-3 border-end-md">
                        <small class="text-muted d-block fw-semibold text-uppercase">Minha Equipe</small>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <font-awesome-icon :icon="['fas', 'circle-check']" class="text-success" />
                            <strong class="text-truncate">{{ primaryTeam.name }}</strong>
                        </div>
                    </div>

                    <div class="col-md-3 border-end-md">
                        <small class="text-muted d-block fw-semibold text-uppercase">Inscrição no Evento</small>
                        <div class="mt-1">
                            <span v-if="activeRegistration?.status === 'approved'" class="badge bg-success">
                                <font-awesome-icon :icon="['fas', 'check']" class="me-1" /> Aprovada
                            </span>
                            <span v-else-if="activeRegistration?.status === 'pending'" class="badge bg-warning text-dark">
                                Em Análise
                            </span>
                            <span v-else class="text-muted small">
                                Não inscrito em evento
                            </span>
                        </div>
                    </div>

                    <div class="col-md-3 border-end-md">
                        <small class="text-muted d-block fw-semibold text-uppercase">Projeto da Equipe</small>
                        <div class="mt-1">
                            <span v-if="teamProject" class="badge bg-primary">
                                <font-awesome-icon :icon="['fas', 'circle-check']" class="me-1" /> Enviado
                            </span>
                            <span v-else class="badge bg-secondary">
                                <font-awesome-icon :icon="['fas', 'clock']" class="me-1" /> Ainda não enviado
                            </span>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted d-block fw-semibold text-uppercase">Etapa Atual</small>
                        <div class="mt-1">
                            <strong v-if="currentStage" class="text-primary text-truncate d-block">
                                {{ currentStage.name }}
                            </strong>
                            <span v-else class="text-muted small">Sem etapas ativas</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Seções Centrais: Minha Equipe & Etapa Atual -->
        <div class="row g-4">
            <!-- Coluna da Equipe -->
            <div class="col-lg-6">
                <!-- Estado Vazio: Sem Equipe -->
                <div v-if="!primaryTeam" class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                        <div class="empty-state-icon text-muted mb-3 fs-1">
                            <font-awesome-icon :icon="['fas', 'users']" />
                        </div>
                        <h5 class="fw-bold mb-2">Você ainda não faz parte de uma equipe.</h5>
                        <p class="text-secondary small mb-4">
                            Encontre um Hackathon disponível e monte sua equipe com outros talentos para começar a competir.
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <Link :href="route('hackathon.index')" class="btn btn-outline-primary btn-sm fw-semibold">
                                Explorar Hackathons
                            </Link>
                            <button class="btn btn-primary btn-sm fw-semibold" @click="$emit('create-team')">
                                + Criar Equipe
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Detalhes da Equipe Ativa -->
                <div v-else class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'users-rectangle']" class="text-primary me-2" />
                            Minha Equipe
                        </h5>
                        <Link :href="route('team.show', primaryTeam.id)" class="btn btn-sm btn-outline-primary">
                            Ver Equipe &rarr;
                        </Link>
                    </div>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h4 class="fw-bold mb-1">{{ primaryTeam.name }}</h4>
                                <p class="text-secondary small mb-0">{{ primaryTeam.description || 'Sem descrição cadastrada.' }}</p>
                            </div>
                            <span v-if="isLeader" class="badge bg-primary">Você é o Líder</span>
                            <span v-else class="badge bg-light text-dark border">Integrante</span>
                        </div>

                        <!-- Hackathon Relacionado -->
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <small class="text-muted d-block fw-semibold text-uppercase">Hackathon Vinculado</small>
                            <div v-if="activeHackathon" class="d-flex justify-content-between align-items-center mt-1">
                                <div>
                                    <strong class="text-dark">{{ activeHackathon.title }}</strong>
                                    <div class="small text-muted">Modalidade: {{ activeHackathon.modality }}</div>
                                </div>
                                <Link :href="route('hackathon.show', activeHackathon.slug)" class="btn btn-sm btn-link text-decoration-none">
                                    Ver Detalhes
                                </Link>
                            </div>
                            <div v-else class="text-muted small mt-1">
                                Esta equipe ainda não está inscrita em nenhum hackathon ativo.
                                <Link :href="route('hackathon.index')" class="d-block mt-1 text-primary fw-semibold">
                                    Encontrar um Hackathon para Inscrever &rarr;
                                </Link>
                            </div>
                        </div>

                        <!-- Integrantes da Equipe -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="fw-bold mb-0 small text-uppercase text-muted">
                                    Integrantes ({{ primaryTeam.members ? primaryTeam.members.length : 1 }})
                                </h6>
                                <Link v-if="isLeader" :href="route('team.show', primaryTeam.id)" class="btn btn-sm btn-link p-0 small text-decoration-none">
                                    + Convidar Integrante
                                </Link>
                            </div>

                            <ul class="list-group list-group-flush border rounded-3">
                                <li
                                    v-for="member in primaryTeam.members"
                                    :key="member.id"
                                    class="list-group-item d-flex justify-content-between align-items-center py-2"
                                >
                                    <div class="d-flex align-items-center gap-2">
                                        <font-awesome-icon :icon="['fas', 'user']" class="text-muted small" />
                                        <span class="fw-semibold small">{{ member.name }}</span>
                                    </div>
                                    <span
                                        class="badge"
                                        :class="member.id === primaryTeam.leader_id ? 'bg-warning text-dark' : 'bg-light text-dark border'"
                                    >
                                        {{ member.id === primaryTeam.leader_id ? 'Líder' : 'Membro' }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna da Etapa Atual & Timeline -->
            <div class="col-lg-6">
                <!-- Se não participa de hackathon ainda -->
                <div v-if="!activeHackathon" class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                        <div class="empty-state-icon text-muted mb-3 fs-1">
                            <font-awesome-icon :icon="['fas', 'calendar-xmark']" />
                        </div>
                        <h5 class="fw-bold mb-2">Você ainda não está participando de nenhum Hackathon.</h5>
                        <p class="text-secondary small mb-4">
                            Explore as competições abertas, verifique as datas e inscreva sua equipe para desbloquear a timeline de etapas.
                        </p>
                        <div>
                            <Link :href="route('hackathon.index')" class="btn btn-primary btn-sm fw-semibold">
                                Explorar Hackathons Abertos
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Painel da Etapa Atual & Cronograma -->
                <div v-else class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'timeline']" class="text-warning me-2" />
                            Etapa Atual do Hackathon
                        </h5>
                        <span v-if="stageDaysRemaining !== null" class="badge bg-warning text-dark fw-semibold">
                            <font-awesome-icon :icon="['fas', 'hourglass-half']" class="me-1" />
                            {{ stageDaysRemaining }} dia(s) restante(s)
                        </span>
                    </div>

                    <div class="card-body">
                        <!-- Destaque da Etapa Atual -->
                        <div v-if="currentStage" class="alert alert-warning border-0 p-3 mb-4 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-dark text-white text-uppercase">Você está aqui</span>
                                <small class="text-dark fw-bold">
                                    {{ new Date(currentStage.start_date).toLocaleDateString() }} até {{ new Date(currentStage.end_date).toLocaleDateString() }}
                                </small>
                            </div>
                            <h5 class="fw-bold text-dark mt-2 mb-1">{{ currentStage.name }}</h5>
                            <p class="mb-0 text-dark small opacity-75">
                                {{ currentStage.description || 'Acompanhe as orientações da organização para esta etapa.' }}
                            </p>
                        </div>

                        <!-- Timeline Cronológica das Etapas -->
                        <h6 class="fw-bold small text-uppercase text-muted mb-3">Cronograma de Etapas</h6>
                        <div class="timeline-container">
                            <div
                                v-for="stage in stages"
                                :key="stage.id"
                                class="timeline-stage-item"
                                :class="{
                                    'is-completed': getStageStatus(stage) === 'completed',
                                    'is-active': getStageStatus(stage) === 'current',
                                }"
                            >
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="small" :class="getStageStatus(stage) === 'current' ? 'text-primary fw-bold' : ''">
                                        {{ stage.name }}
                                    </strong>
                                    <span v-if="getStageStatus(stage) === 'completed'" class="text-success small fw-bold">
                                        <font-awesome-icon :icon="['fas', 'check']" /> Concluída
                                    </span>
                                    <span v-else-if="getStageStatus(stage) === 'current'" class="badge bg-warning text-dark small">
                                        Em Andamento
                                    </span>
                                    <span v-else class="text-muted small">
                                        {{ new Date(stage.start_date).toLocaleDateString() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Seção Próximos Prazos & Projeto da Equipe -->
        <div class="row g-4 mt-1">
            <!-- Próximos Prazos -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'bell']" class="text-danger me-2" />
                            Próximos Prazos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div v-if="upcomingDeadlines.length === 0" class="text-muted small text-center py-4">
                            Nenhum prazo iminente registrado.
                        </div>
                        <ul v-else class="list-group list-group-flush">
                            <li
                                v-for="(deadline, idx) in upcomingDeadlines"
                                :key="idx"
                                class="list-group-item d-flex justify-content-between align-items-center px-0 py-3"
                            >
                                <div class="d-flex align-items-center gap-2">
                                    <font-awesome-icon :icon="['fas', 'clock']" class="text-danger" />
                                    <div>
                                        <strong class="d-block small">{{ deadline.title }}</strong>
                                        <small class="text-muted">Data limite: {{ deadline.date.toLocaleDateString() }} às {{ deadline.date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}</small>
                                    </div>
                                </div>
                                <span class="badge bg-light text-dark border">Prioritário</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Projeto da Equipe -->
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'rocket']" class="text-primary me-2" />
                            Projeto da Equipe
                        </h5>
                        <Link v-if="teamProject" :href="route('project.show', teamProject.id)" class="btn btn-sm btn-outline-primary">
                            Ver Projeto
                        </Link>
                    </div>

                    <div class="card-body">
                        <!-- Sem projeto enviado -->
                        <div v-if="!teamProject" class="text-center py-4">
                            <div class="empty-state-icon text-muted mb-2 fs-2">
                                <font-awesome-icon :icon="['fas', 'folder-plus']" />
                            </div>
                            <h6 class="fw-bold mb-1">Sua equipe ainda não enviou um projeto.</h6>
                            <p class="text-secondary small mb-3">
                                Prepare a solução, links do GitHub e demonstração para submeter antes do prazo limite.
                            </p>
                            <Link
                                v-if="primaryTeam && activeHackathon"
                                :href="route('hackathon.show', activeHackathon.slug)"
                                class="btn btn-primary btn-sm fw-semibold"
                            >
                                Submeter Projeto
                            </Link>
                        </div>

                        <!-- Projeto enviado -->
                        <div v-else>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="fw-bold mb-0">{{ teamProject.title }}</h5>
                                <span class="badge bg-success">Status: {{ teamProject.status }}</span>
                            </div>
                            <p class="text-secondary small mb-3">{{ teamProject.short_description || 'Projeto submetido com sucesso.' }}</p>

                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a v-if="teamProject.github_repository_url" :href="teamProject.github_repository_url" target="_blank" class="btn btn-sm btn-outline-dark">
                                    <font-awesome-icon :icon="['fab', 'github']" class="me-1" /> Repositório
                                </a>
                                <a v-if="teamProject.demo_url" :href="teamProject.demo_url" target="_blank" class="btn btn-sm btn-outline-primary">
                                    Link Demo
                                </a>
                                <Link :href="route('project.show', teamProject.id)" class="btn btn-sm btn-primary">
                                    Editar Submissão
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
