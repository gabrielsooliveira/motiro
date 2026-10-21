<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    teams: {
        type: Array,
        default: () => [],
    },
    groups: {
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

const activeSection = ref("overview");

// Primary active registration: from props.registrations or primaryTeam
const activeRegistration = computed(() => {
    if (props.registrations && props.registrations.length > 0) {
        return props.registrations[0];
    }
    if (props.teams && props.teams[0]?.registrations) {
        return props.teams[0].registrations[0] || null;
    }
    return null;
});

// Primary active team: from activeRegistration or props.teams
const primaryTeam = computed(() => {
    if (activeRegistration.value?.team) {
        return activeRegistration.value.team;
    }
    return props.teams[0] || null;
});

// Active hackathon linked to registration or team
const activeHackathon = computed(() => {
    return activeRegistration.value?.hackathon || (primaryTeam.value?.registrations ? primaryTeam.value.registrations[0]?.hackathon : null);
});

// Whether participant is individual (no team assigned)
const isIndividual = computed(() => {
    if (!activeRegistration.value) return !primaryTeam.value;
    return !activeRegistration.value.team_id && !primaryTeam.value;
});

// Is team eligible (minimum members met) or individual eligible
const isEligible = computed(() => {
    if (activeRegistration.value) {
        return Boolean(activeRegistration.value.is_eligible);
    }
    if (primaryTeam.value && activeHackathon.value) {
        return (primaryTeam.value.members?.length || 0) >= (activeHackathon.value.min_team_members || 1);
    }
    return false;
});

// Primary project (team project or individual project)
const teamProject = computed(() => {
    if (primaryTeam.value) {
        return props.projects.find((p) => p.team_id === primaryTeam.value.id) || null;
    }
    if (activeHackathon.value) {
        return props.projects.find((p) => p.hackathon_id === activeHackathon.value.id) || props.projects[0] || null;
    }
    return props.projects[0] || null;
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

        <!-- Navegação das Seções do Participante -->
        <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3 flex-wrap">
            <li class="nav-item">
                <Link
                    class="nav-link"
                    :class="{ active: $page.url === '/participante' || $page.url === '/participante/dashboard' || $page.url === '/dashboard' }"
                    :href="route('participant.dashboard')"
                >
                    <font-awesome-icon :icon="['fas', 'gauge-high']" class="me-1" />
                    Visão Geral
                </Link>
            </li>
            <li class="nav-item">
                <Link
                    class="nav-link"
                    :class="{ active: $page.url.startsWith('/participante/guildas') || $page.url.startsWith('/participante/equipes') || $page.url.startsWith('/dashboard/teams') || $page.url.startsWith('/dashboard/guilds') }"
                    :href="route().has('participant.guilds.index') ? route('participant.guilds.index') : route('participant.teams.index')"
                >
                    <font-awesome-icon :icon="['fas', 'shield-halved']" class="me-1" />
                    Minhas Guildas
                    <span class="badge ms-1" :class="($page.url.startsWith('/participante/guildas') || $page.url.startsWith('/participante/equipes') || $page.url.startsWith('/dashboard/teams') || $page.url.startsWith('/dashboard/guilds')) ? 'bg-white text-primary' : 'bg-primary-subtle text-primary'">
                        {{ teams.length }}
                    </span>
                </Link>
            </li>
            <li class="nav-item">
                <Link
                    class="nav-link"
                    :class="{ active: $page.url.startsWith('/participante/grupos') || $page.url.startsWith('/dashboard/groups') }"
                    :href="route('participant.groups.index')"
                >
                    <font-awesome-icon :icon="['fas', 'people-group']" class="me-1" />
                    Grupos de Participação
                    <span class="badge ms-1" :class="($page.url.startsWith('/participante/grupos') || $page.url.startsWith('/dashboard/groups')) ? 'bg-white text-primary' : 'bg-success-subtle text-success'">
                        {{ groups.length }}
                    </span>
                </Link>
            </li>
            <li class="nav-item">
                <Link
                    class="nav-link"
                    :class="{ active: $page.url.startsWith('/participante/meus-hackathons') || $page.url.startsWith('/dashboard/my-hackathons') }"
                    :href="route('participant.my_hackathons')"
                >
                    <font-awesome-icon :icon="['fas', 'trophy']" class="me-1" />
                    Meus Hackathons
                    <span class="badge ms-1" :class="($page.url.startsWith('/participante/meus-hackathons') || $page.url.startsWith('/dashboard/my-hackathons')) ? 'bg-white text-primary' : 'bg-info-subtle text-info'">
                        {{ registrations.length }}
                    </span>
                </Link>
            </li>
        </ul>

        <!-- SEÇÃO 1: VISÃO GERAL -->
        <div v-if="activeSection === 'overview'">
            <!-- 2. Status da Participação Rápido (Se o competidor possui inscrição ou equipe) -->
            <div v-if="primaryTeam || activeRegistration" class="card shadow-sm border-0 mb-4 bg-light">
            <div class="card-body p-3">
                <div class="row g-3 text-center text-md-start align-items-center">
                    <div class="col-md-3 border-end-md">
                        <small class="text-muted d-block fw-semibold text-uppercase">Modalidade / Equipe</small>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <template v-if="primaryTeam">
                                <font-awesome-icon :icon="['fas', 'people-group']" class="text-primary" />
                                <strong class="text-truncate">{{ primaryTeam.name }}</strong>
                            </template>
                            <template v-else-if="activeHackathon?.registration_type === 'team_only'">
                                <span class="badge bg-warning text-dark">
                                    <font-awesome-icon :icon="['fas', 'clock']" class="me-1" />
                                    Aguardando Equipe
                                </span>
                            </template>
                            <template v-else>
                                <span class="badge bg-info text-dark">
                                    <font-awesome-icon :icon="['fas', 'user']" class="me-1" />
                                    Participação Individual
                                </span>
                            </template>
                        </div>
                    </div>

                    <div class="col-md-3 border-end-md">
                        <small class="text-muted d-block fw-semibold text-uppercase">Inscrição no Evento</small>
                        <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                            <span v-if="activeRegistration?.status === 'approved'" class="badge bg-success">
                                <font-awesome-icon :icon="['fas', 'check']" class="me-1" /> Aprovada
                            </span>
                            <span v-else-if="activeRegistration?.status === 'pending'" class="badge bg-warning text-dark">
                                Em Análise
                            </span>
                            <span v-else class="text-muted small">
                                Não inscrito em evento
                            </span>

                            <span v-if="isEligible" class="badge bg-success-subtle text-success border border-success-subtle small">
                                Elegível
                            </span>
                            <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle small">
                                Pendente de Equipe
                            </span>
                        </div>
                    </div>

                    <div class="col-md-3 border-end-md">
                        <small class="text-muted d-block fw-semibold text-uppercase">Projeto {{ primaryTeam ? 'da Equipe' : 'Individual' }}</small>
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

        <!-- 3. Seções Centrais: Minha Participação / Equipe & Etapa Atual -->
        <div class="row g-4">
            <!-- Coluna da Equipe / Participação Individual -->
            <div class="col-lg-6">
                <!-- Caso 1: Inscrito Individualmente (sem equipe) -->
                <div v-if="!primaryTeam && activeRegistration" class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'id-badge']" class="text-primary me-2" />
                            Minha Inscrição no Hackathon
                        </h5>
                        <Link v-if="activeHackathon" :href="route('hackathon.show', activeHackathon.slug)" class="btn btn-sm btn-outline-primary">
                            Ver Hackathon &rarr;
                        </Link>
                    </div>
                    <div class="card-body p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-primary-subtle text-primary">{{ activeHackathon?.title || 'Hackathon' }}</span>
                                <span class="badge bg-light text-dark border">{{ activeHackathon?.modality }}</span>
                            </div>

                            <!-- Caso Team Only: Precisa de equipe -->
                            <div v-if="activeHackathon?.registration_type === 'team_only'" class="alert alert-warning border-0 p-3 mb-3">
                                <h6 class="fw-bold mb-1">
                                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                                    Aguardando Formação de Equipe
                                </h6>
                                <p class="small mb-0">
                                    Sua inscrição está confirmada, mas este hackathon exige participação em equipe. Crie uma equipe ou solicite entrada em uma equipe existente para estar apto a competir.
                                </p>
                            </div>

                            <!-- Caso Individual: Participação Solo -->
                            <div v-else-if="activeHackathon?.registration_type === 'individual'" class="alert alert-info border-0 p-3 mb-3">
                                <h6 class="fw-bold mb-1">
                                    <font-awesome-icon :icon="['fas', 'user-check']" class="me-1" />
                                    Participação Individual Confirmada
                                </h6>
                                <p class="small mb-0">
                                    Você está competindo individualmente. Desenvolva seu projeto e envie a solução antes do prazo limite.
                                </p>
                            </div>

                            <!-- Caso Híbrido: Individual ou Equipe -->
                            <div v-else class="alert alert-success border-0 p-3 mb-3">
                                <h6 class="fw-bold mb-1">
                                    <font-awesome-icon :icon="['fas', 'circle-check']" class="me-1" />
                                    Inscrição Individual Ativa
                                </h6>
                                <p class="small mb-0">
                                    Você está apto a competir individualmente. Se preferir trabalhar em grupo, você pode criar uma equipe ou ingressar em uma existente a qualquer momento.
                                </p>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 pt-3 border-top mt-3">
                            <button
                                v-if="activeHackathon?.allow_team_creation && activeHackathon?.registration_type !== 'individual'"
                                class="btn btn-primary btn-sm fw-semibold"
                                @click="$emit('create-team')"
                            >
                                <font-awesome-icon :icon="['fas', 'plus']" class="me-1" />
                                + Criar Equipe
                            </button>
                            <Link v-if="activeHackathon" :href="route('hackathon.show', activeHackathon.slug)" class="btn btn-outline-primary btn-sm fw-semibold">
                                Ver Desafios & Equipes
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Caso 2: Não possui equipe nem inscrição ativa -->
                <div v-else-if="!primaryTeam && !activeRegistration" class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                        <div class="empty-state-icon text-muted mb-3 fs-1">
                            <font-awesome-icon :icon="['fas', 'users']" />
                        </div>
                        <h5 class="fw-bold mb-2">Você ainda não está inscrito em um Hackathon.</h5>
                        <p class="text-secondary small mb-4">
                            Encontre um Hackathon disponível, inscreva-se individualmente ou monte sua equipe com outros talentos para começar a competir.
                        </p>
                        <div class="d-flex justify-content-center gap-2">
                            <Link :href="route('participant.hackathons')" class="btn btn-outline-primary btn-sm fw-semibold">
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
                            <span v-else-if="!primaryTeam.leader_id" class="badge bg-warning text-dark">Líder Pendente</span>
                            <span v-else class="badge bg-light text-dark border">Integrante</span>
                        </div>

                        <div v-if="!primaryTeam.leader_id" class="alert alert-warning py-2 px-3 small rounded-3 mb-3 border-0">
                            <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                            A equipe ainda não possui líder definido. Acesse a página da equipe para eleger um dos integrantes.
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
                                <Link :href="route('participant.hackathons')" class="d-block mt-1 text-primary fw-semibold">
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
                            <Link :href="route('participant.hackathons')" class="btn btn-primary btn-sm fw-semibold">
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

        <!-- SEÇÃO 2: MINHAS EQUIPES (GUILDAS PERMANENTES) -->
        <div v-else-if="activeSection === 'teams'">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                    <div>
                        <h4 class="fw-bold mb-1 text-dark">Minhas Equipes Permanentes (Guildas)</h4>
                        <p class="text-muted small mb-0">
                            Equipes permanentes representam suas guildas independentes de hackathons. Você pode reunir membros, convidar participantes por e-mail e organizar equipes sem compromisso com eventos específicos.
                        </p>
                    </div>
                    <button class="btn btn-primary d-flex align-items-center gap-2" @click="$emit('create-team')">
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Nova Guilda</span>
                    </button>
                </div>

                <div v-if="teams.length === 0" class="text-center py-5 text-muted">
                    <font-awesome-icon :icon="['fas', 'users-rectangle']" class="fs-1 mb-3 opacity-50" />
                    <h5 class="fw-bold text-dark">Você ainda não faz parte de nenhuma equipe permanente.</h5>
                    <p class="small text-secondary mb-3">Crie sua guilda para convidar amigos e colegas de trabalho!</p>
                    <button class="btn btn-outline-primary btn-sm" @click="$emit('create-team')">
                        Criar Minha Primeira Guilda
                    </button>
                </div>

                <div v-else class="row g-3">
                    <div v-for="team in teams" :key="team.id" class="col-md-6 col-lg-4">
                        <div class="card h-100 border rounded-3 p-3 bg-light bg-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        {{ team.area_of_focus || 'Geral' }}
                                    </span>
                                    <span v-if="team.leader_id === currentUser?.id" class="badge bg-warning text-dark">
                                        <font-awesome-icon :icon="['fas', 'crown']" class="me-1" /> Líder
                                    </span>
                                    <span v-else class="badge bg-light text-secondary border">Membro</span>
                                </div>
                            </div>
                            <div class="pt-2 border-top mt-2">
                                <Link :href="route('team.show', team.id)" class="btn btn-sm btn-outline-primary w-100">
                                    Gerenciar Guilda
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: GRUPOS DE PARTICIPAÇÃO NOS HACKATHONS -->
        <div v-else-if="activeSection === 'groups'">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                    <div>
                        <h4 class="fw-bold mb-1 text-dark">Grupos de Participação nos Hackathons</h4>
                        <p class="text-muted small mb-0">
                            Grupos de participação representam as equipes competitivas formadas especificamente para cada hackathon. Somente participantes com inscrições válidas podem integrá-los.
                        </p>
                    </div>
                </div>

                <div v-if="groups.length === 0" class="text-center py-5 text-muted">
                    <font-awesome-icon :icon="['fas', 'people-group']" class="fs-1 mb-3 opacity-50" />
                    <h5 class="fw-bold text-dark">Você não está em nenhum grupo de participação ativo.</h5>
                    <p class="small text-secondary mb-3">
                        Ao se inscrever em um hackathon que exija ou permita equipes, forme um grupo diretamente ou a partir de uma de suas guildas!
                    </p>
                    <Link :href="route('participant.hackathons')" class="btn btn-outline-primary btn-sm">
                        Explorar Hackathons
                    </Link>
                </div>

                <div v-else class="row g-3">
                    <div v-for="group in groups" :key="group.id" class="col-md-6">
                        <div class="card h-100 border rounded-3 p-3 bg-light bg-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-dark text-white">
                                        {{ group.hackathon?.title || 'Hackathon' }}
                                    </span>
                                    <span v-if="group.leader_id === currentUser?.id" class="badge bg-warning text-dark">
                                        <font-awesome-icon :icon="['fas', 'crown']" class="me-1" /> Líder do Grupo
                                    </span>
                                    <span v-else class="badge bg-light text-secondary border">Integrante</span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">{{ group.name }}</h5>
                                <div v-if="group.origin_team" class="badge bg-info-subtle text-info border border-info-subtle mb-2">
                                    Originado da guilda: {{ group.origin_team.name }}
                                </div>
                                <p class="text-muted small mb-3">
                                    {{ group.description || 'Grupo competitivo formado para este Hackathon.' }}
                                </p>
                                <div class="border rounded-2 p-2 bg-white mb-2">
                                    <span class="text-muted small fw-semibold d-block mb-1">Integrantes Confirmados:</span>
                                    <ul class="list-unstyled mb-0 small">
                                        <li v-for="member in group.members" :key="member.id" class="d-flex justify-content-between py-1 border-bottom-subtle">
                                            <span>{{ member.name }}</span>
                                            <span v-if="member.id === group.leader_id" class="text-warning fw-bold">Líder</span>
                                            <span v-else class="text-muted">Membro</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="pt-2 border-top mt-2 d-flex gap-2">
                                <Link v-if="group.hackathon" :href="route('hackathon.show', group.hackathon.slug)" class="btn btn-sm btn-primary w-100">
                                    Página do Hackathon
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: MEUS HACKATHONS (INSCRIÇÕES) -->
        <div v-else-if="activeSection === 'registrations'">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                    <div>
                        <h4 class="fw-bold mb-1 text-dark">Meus Hackathons e Inscrições</h4>
                        <p class="text-muted small mb-0">
                            Acompanhe seu status de inscrição, modalidade de participação e elegibilidade em cada competição.
                        </p>
                    </div>
                    <Link :href="route('participant.hackathons')" class="btn btn-outline-primary btn-sm">
                        Buscar Novos Hackathons
                    </Link>
                </div>

                <div v-if="registrations.length === 0" class="text-center py-5 text-muted">
                    <font-awesome-icon :icon="['fas', 'trophy']" class="fs-1 mb-3 opacity-50" />
                    <h5 class="fw-bold text-dark">Você ainda não realizou nenhuma inscrição em hackathons.</h5>
                    <p class="small text-secondary mb-3">Confira o catálogo de eventos abertos e participe!</p>
                    <Link :href="route('participant.hackathons')" class="btn btn-primary btn-sm">
                        Explorar Hackathons Abertos
                    </Link>
                </div>

                <div v-else class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th scope="col" class="ps-3">Hackathon</th>
                                <th scope="col">Modalidade / Formação</th>
                                <th scope="col">Elegibilidade</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="reg in registrations" :key="reg.id">
                                <td class="ps-3">
                                    <strong class="text-dark d-block">{{ reg.hackathon?.title }}</strong>
                                    <small class="text-muted">{{ reg.hackathon?.organization?.name || 'Motirõ' }}</small>
                                </td>
                                <td>
                                    <div v-if="reg.group || reg.team">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <font-awesome-icon :icon="['fas', 'people-group']" class="me-1" />
                                            {{ (reg.group || reg.team).name }}
                                        </span>
                                    </div>
                                    <div v-else>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <font-awesome-icon :icon="['fas', 'user']" class="me-1" />
                                            Individual
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span v-if="reg.is_eligible" class="badge bg-success-subtle text-success border border-success-subtle">
                                        <font-awesome-icon :icon="['fas', 'check']" class="me-1" /> Apto
                                    </span>
                                    <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        <font-awesome-icon :icon="['fas', 'clock']" class="me-1" /> Pendente de Grupo
                                    </span>
                                </td>
                                <td>
                                    <span class="badge" :class="{
                                        'bg-success': reg.status === 'approved',
                                        'bg-warning text-dark': reg.status === 'pending',
                                        'bg-danger': reg.status === 'rejected',
                                        'bg-secondary': reg.status === 'cancelled'
                                    }">
                                        {{ reg.status === 'approved' ? 'Aprovada' : (reg.status === 'pending' ? 'Em Análise' : reg.status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-3">
                                    <Link v-if="reg.hackathon" :href="route('hackathon.show', reg.hackathon.slug)" class="btn btn-sm btn-outline-primary">
                                        Acessar &rarr;
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
