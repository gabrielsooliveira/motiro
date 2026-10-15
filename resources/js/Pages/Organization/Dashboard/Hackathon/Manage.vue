<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
    rankings: {
        type: Array,
        default: null,
    },
    canManageStaff: {
        type: Boolean,
        default: false,
    },
});

const activeTab = ref("registrations");

// Modals
const showAddMemberModal = ref(false);
const showAddChallengeModal = ref(false);
const showAssignModal = ref(false);
const showTransferModal = ref(false);
const showCreateTeamOrgModal = ref(false);
const selectedRegistration = ref(null);
const registrationFilter = ref("all"); // 'all', 'individual', 'team'

const memberForm = useForm({
    user_id: "",
    role: "collaborator",
});

const challengeForm = useForm({
    title: "",
    description: "",
    requirements: "",
    prize: "",
    order: 1,
});

const assignForm = useForm({
    registration_id: "",
    team_id: "",
});

const transferForm = useForm({
    registration_id: "",
    target_team_id: "",
});

const createTeamOrgForm = useForm({
    name: "",
    leader_id: "",
    description: "",
    area_of_focus: "",
    member_registration_ids: [],
});

const formatForm = useForm({
    registration_type: props.hackathon.registration_type || "individual_or_team",
    min_team_members: props.hackathon.min_team_members || 1,
    max_team_members: props.hackathon.max_team_members || 5,
    allow_team_creation: props.hackathon.allow_team_creation ?? true,
    allow_team_invites: props.hackathon.allow_team_invites ?? true,
    allow_team_requests: props.hackathon.allow_team_requests ?? true,
    allow_org_team_management: props.hackathon.allow_org_team_management ?? true,
    team_formation_deadline: props.hackathon.team_formation_deadline ? props.hackathon.team_formation_deadline.substring(0, 16) : "",
    team_management_deadline: props.hackathon.team_management_deadline ? props.hackathon.team_management_deadline.substring(0, 16) : "",
});

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

// Staff management
const submitAddMember = () => {
    memberForm.post(route("organization.hackathon.members.store", props.hackathon.id), {
        onSuccess: () => {
            showAddMemberModal.value = false;
            memberForm.reset();
        },
    });
};

const removeStaffMember = (memberId, name) => {
    if (confirm(`Remover ${name} da equipe responsável deste hackathon?`)) {
        router.delete(route("organization.hackathon.members.destroy", [props.hackathon.id, memberId]));
    }
};

const updateStaffRole = (memberId, newRole) => {
    router.put(route("organization.hackathon.members.update", [props.hackathon.id, memberId]), {
        role: newRole,
    });
};

// Challenge management
const submitAddChallenge = () => {
    challengeForm.post(route("organization.hackathon.challenges.store", props.hackathon.id), {
        onSuccess: () => {
            showAddChallengeModal.value = false;
            challengeForm.reset();
        },
    });
};

const deleteChallenge = (challengeId, title) => {
    if (confirm(`Remover o desafio '${title}'?`)) {
        router.delete(route("organization.hackathon.challenges.destroy", [props.hackathon.id, challengeId]));
    }
};

// Participant & Team Management (Prompt Master Section 8)
const openAssignModal = (reg) => {
    selectedRegistration.value = reg;
    assignForm.registration_id = reg.id;
    assignForm.team_id = availableTeamsForAssignment.value[0]?.id || "";
    showAssignModal.value = true;
};

const submitAssign = () => {
    assignForm.post(route("organization.hackathon.participants.assign", props.hackathon.id), {
        onSuccess: () => {
            showAssignModal.value = false;
            assignForm.reset();
        },
    });
};

const openTransferModal = (reg) => {
    selectedRegistration.value = reg;
    transferForm.registration_id = reg.id;
    const candidates = (props.hackathon.teams || []).filter(t => t.id !== reg.team_id && (t.members?.length || 0) < props.hackathon.max_team_members);
    transferForm.target_team_id = candidates[0]?.id || "";
    showTransferModal.value = true;
};

const submitTransfer = () => {
    transferForm.post(route("organization.hackathon.participants.transfer", props.hackathon.id), {
        onSuccess: () => {
            showTransferModal.value = false;
            transferForm.reset();
        },
    });
};

const handleRemoveFromTeam = (reg) => {
    if (confirm(`Remover o participante ${reg.user?.name || 'Inscrito'} da equipe ${reg.team?.name}?`)) {
        router.delete(route("organization.hackathon.participants.remove_team", [props.hackathon.id, reg.id]));
    }
};

const openCreateTeamOrgModal = () => {
    createTeamOrgForm.reset();
    createTeamOrgForm.leader_id = individualRegistrations.value[0]?.user_id || "";
    showCreateTeamOrgModal.value = true;
};

const submitCreateTeamOrg = () => {
    createTeamOrgForm.post(route("organization.hackathon.participants.create_team", props.hackathon.id), {
        onSuccess: () => {
            showCreateTeamOrgModal.value = false;
            createTeamOrgForm.reset();
        },
    });
};

const submitUpdateFormat = () => {
    formatForm.put(route("hackathon.manage.update", props.hackathon.id), {
        preserveScroll: true,
    });
};

// Computed Stats & Filtering
const individualRegistrations = computed(() => {
    return (props.hackathon.registrations || []).filter(r => !r.team_id);
});

const teamRegistrations = computed(() => {
    return (props.hackathon.registrations || []).filter(r => !!r.team_id);
});

const filteredRegistrations = computed(() => {
    const list = props.hackathon.registrations || [];
    if (registrationFilter.value === "individual") {
        return list.filter(r => !r.team_id);
    }
    if (registrationFilter.value === "team") {
        return list.filter(r => !!r.team_id);
    }
    return list;
});

const hackathonTeams = computed(() => {
    return props.hackathon.teams || [];
});

const availableTeamsForAssignment = computed(() => {
    return hackathonTeams.value.filter(t => (t.members?.length || 0) < props.hackathon.max_team_members);
});

const incompleteTeamsCount = computed(() => {
    return hackathonTeams.value.filter(t => (t.members?.length || 0) < props.hackathon.min_team_members).length;
});

// Organization members available to be added as staff
const availableOrgUsers = computed(() => {
    const orgUsers = props.hackathon.organization?.users || [];
    const currentMemberIds = (props.hackathon.members || []).map(m => m.user_id);
    return orgUsers.filter(u => !currentMemberIds.includes(u.id));
});

const getStaffRoleBadge = (role) => {
    switch (role) {
        case "admin": return { label: "Administrador", class: "bg-primary text-white" };
        case "manager": return { label: "Gestor", class: "bg-success text-white" };
        case "collaborator": return { label: "Colaborador", class: "bg-info text-dark" };
        default: return { label: role, class: "bg-secondary text-white" };
    }
};

const getModalityLabel = (type) => {
    if (type === "individual") return "Inscrições Individuais";
    if (type === "team_only") return "Obrigatoriamente por Equipe";
    return "Individual ou por Equipe";
};
</script>

<template>
    <Head>
        <title>Gerenciar | {{ hackathon.title }}</title>
    </Head>

    <div class="py-2">
            <!-- Hackathon Header (Section 11) -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <Link :href="route('organization.dashboard')" class="text-decoration-none text-muted small d-inline-block mb-1">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                        Voltar para o Painel da Organização
                    </Link>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h2 fw-bold text-dark mb-0">{{ hackathon.title }}</h1>
                        <span class="badge bg-light text-secondary border text-uppercase small">{{ hackathon.modality }}</span>
                        <span class="badge bg-primary text-uppercase small">{{ hackathon.status }}</span>
                        <span v-if="hackathon.organization" class="badge bg-secondary-subtle text-secondary small">
                            {{ hackathon.organization.name }}
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-info d-flex align-items-center gap-1" @click="handleDistributeJudging">
                        <font-awesome-icon :icon="['fas', 'share-nodes']" />
                        <span>Distribuir para Juízes</span>
                    </button>
                    <button class="btn btn-sm btn-success d-flex align-items-center gap-1" @click="handlePublishResults">
                        <font-awesome-icon :icon="['fas', 'award']" />
                        <span>Publicar Resultados</span>
                    </button>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto">
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'registrations' }" @click="activeTab = 'registrations'">
                        Inscrições ({{ hackathon.registrations?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'projects' }" @click="activeTab = 'projects'">
                        Projetos ({{ hackathon.projects?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'staff' }" @click="activeTab = 'staff'">
                        <font-awesome-icon :icon="['fas', 'user-shield']" class="me-1" />
                        Equipe do Hackathon ({{ hackathon.members?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'registrations' }" @click="activeTab = 'registrations'">
                        <font-awesome-icon :icon="['fas', 'users-gear']" class="me-1" />
                        Inscrições & Equipes ({{ hackathon.registrations?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'format' }" @click="activeTab = 'format'">
                        <font-awesome-icon :icon="['fas', 'sliders']" class="me-1" />
                        Formato de Participação
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'projects' }" @click="activeTab = 'projects'">
                        Projetos ({{ hackathon.projects?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'staff' }" @click="activeTab = 'staff'">
                        <font-awesome-icon :icon="['fas', 'user-shield']" class="me-1" />
                        Equipe do Hackathon ({{ hackathon.members?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'challenges' }" @click="activeTab = 'challenges'">
                        <font-awesome-icon :icon="['fas', 'bullseye']" class="me-1" />
                        Desafios ({{ hackathon.challenges?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'stages' }" @click="activeTab = 'stages'">
                        Etapas ({{ hackathon.stages?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'criteria' }" @click="activeTab = 'criteria'">
                        Critérios ({{ hackathon.evaluation_criteria?.length || 0 }})
                    </button>
                </li>
                <li v-if="rankings" class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'rankings' }" @click="activeTab = 'rankings'">
                        Ranking Oficial
                    </button>
                </li>
            </ul>

            <!-- Tab 1: Registrations & Teams Management (Prompt Master Section 8) -->
            <div v-if="activeTab === 'registrations'" class="d-flex flex-column gap-4">
                <!-- KPI Indicators -->
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <span class="text-muted small text-uppercase fw-semibold">Total de Inscritos</span>
                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                <span class="fs-2 fw-bold text-dark">{{ hackathon.registrations?.length || 0 }}</span>
                                <span class="badge bg-primary-subtle text-primary">Participantes</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <span class="text-muted small text-uppercase fw-semibold">Inscritos Sem Equipe</span>
                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                <span class="fs-2 fw-bold text-warning">{{ individualRegistrations.length }}</span>
                                <span class="badge bg-warning-subtle text-warning">Individuais / Pendentes</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <span class="text-muted small text-uppercase fw-semibold">Equipes Formadas</span>
                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                <span class="fs-2 fw-bold text-success">{{ hackathonTeams.length }}</span>
                                <span class="badge bg-success-subtle text-success">Equipes</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-3 p-3 bg-white h-100">
                            <span class="text-muted small text-uppercase fw-semibold">Equipes Incompletas</span>
                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                <span class="fs-2 fw-bold text-danger">{{ incompleteTeamsCount }}</span>
                                <span class="badge bg-danger-subtle text-danger">&lt; {{ hackathon.min_team_members || 1 }} membros</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participants Table Card -->
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold text-dark">Filtrar Inscritos:</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button
                                    type="button"
                                    class="btn"
                                    :class="registrationFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click="registrationFilter = 'all'"
                                >
                                    Todos ({{ hackathon.registrations?.length || 0 }})
                                </button>
                                <button
                                    type="button"
                                    class="btn"
                                    :class="registrationFilter === 'individual' ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click="registrationFilter = 'individual'"
                                >
                                    Sem Equipe ({{ individualRegistrations.length }})
                                </button>
                                <button
                                    type="button"
                                    class="btn"
                                    :class="registrationFilter === 'team' ? 'btn-primary' : 'btn-outline-secondary'"
                                    @click="registrationFilter = 'team'"
                                >
                                    Com Equipe ({{ teamRegistrations.length }})
                                </button>
                            </div>
                        </div>
                        <div v-if="hackathon.registration_type !== 'individual' && hackathon.allow_org_team_management">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                                @click="openCreateTeamOrgModal"
                            >
                                <font-awesome-icon :icon="['fas', 'plus']" />
                                <span>Criar Equipe (Organização)</span>
                            </button>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div v-if="filteredRegistrations.length === 0" class="text-center py-5 text-muted">
                            <font-awesome-icon :icon="['fas', 'users-slash']" class="fs-2 mb-2 opacity-50" />
                            <p class="mb-0">Nenhuma inscrição encontrada para o filtro selecionado.</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th scope="col" class="ps-3">Participante</th>
                                        <th scope="col">Modalidade / Equipe</th>
                                        <th scope="col">Elegibilidade</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-end pe-3">Ações Administrativas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="reg in filteredRegistrations" :key="reg.id">
                                        <td class="ps-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold avatar-initials">
                                                    {{ reg.user?.name?.charAt(0).toUpperCase() || 'P' }}
                                                </div>
                                                <div>
                                                    <span class="fw-bold text-dark d-block">{{ reg.user?.name }}</span>
                                                    <span class="text-muted small">{{ reg.user?.email }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div v-if="reg.team">
                                                <span class="badge bg-light text-dark border me-1">
                                                    <font-awesome-icon :icon="['fas', 'people-group']" class="me-1 text-primary" />
                                                    {{ reg.team.name }}
                                                </span>
                                                <span v-if="reg.team.leader_id === reg.user_id" class="badge bg-primary-subtle text-primary small">Líder</span>
                                                <div class="text-muted small mt-1">
                                                    {{ reg.team.members?.length || 1 }} / {{ hackathon.max_team_members }} integrantes
                                                </div>
                                            </div>
                                            <div v-else>
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    <font-awesome-icon :icon="['fas', 'user']" class="me-1" />
                                                    Participação Individual
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="reg.is_eligible" class="badge bg-success-subtle text-success border border-success-subtle">
                                                <font-awesome-icon :icon="['fas', 'circle-check']" class="me-1" />
                                                Elegível para competir
                                            </span>
                                            <span v-else class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                <font-awesome-icon :icon="['fas', 'clock']" class="me-1" />
                                                Aguardando equipe
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge" :class="{
                                                'bg-success': reg.status === 'approved',
                                                'bg-warning text-dark': reg.status === 'pending',
                                                'bg-danger': reg.status === 'rejected',
                                                'bg-dark': reg.status === 'disqualified',
                                            }">{{ reg.status }}</span>
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex justify-content-end align-items-center gap-1 flex-wrap">
                                                <!-- Team Actions (if org management allowed and hackathon allows teams) -->
                                                <template v-if="hackathon.registration_type !== 'individual' && hackathon.allow_org_team_management">
                                                    <button
                                                        v-if="!reg.team && availableTeamsForAssignment.length > 0"
                                                        class="btn btn-sm btn-outline-primary"
                                                        title="Alocar em Equipe"
                                                        @click="openAssignModal(reg)"
                                                    >
                                                        <font-awesome-icon :icon="['fas', 'user-plus']" class="me-1" />
                                                        Alocar em Equipe
                                                    </button>
                                                    <button
                                                        v-if="reg.team"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        title="Transferir de Equipe"
                                                        @click="openTransferModal(reg)"
                                                    >
                                                        <font-awesome-icon :icon="['fas', 'arrow-right-arrow-left']" class="me-1" />
                                                        Transferir
                                                    </button>
                                                    <button
                                                        v-if="reg.team"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Remover da Equipe"
                                                        @click="handleRemoveFromTeam(reg)"
                                                    >
                                                        <font-awesome-icon :icon="['fas', 'user-minus']" />
                                                    </button>
                                                </template>

                                                <!-- Status update dropdown / buttons -->
                                                <button v-if="reg.status !== 'approved'" class="btn btn-sm btn-success" @click="handleStatusUpdate(reg.id, 'approved')">
                                                    Aprovar
                                                </button>
                                                <button v-if="reg.status !== 'rejected'" class="btn btn-sm btn-outline-danger" @click="handleStatusUpdate(reg.id, 'rejected')">
                                                    Rejeitar
                                                </button>
                                                <button v-if="reg.status !== 'disqualified'" class="btn btn-sm btn-dark" @click="handleStatusUpdate(reg.id, 'disqualified')">
                                                    Desclassificar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Teams Overview Section -->
                <div v-if="hackathon.registration_type !== 'individual'" class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h6 fw-bold mb-0 text-dark">Equipes Formadas no Hackathon</h2>
                            <span class="text-muted small">Regras: Mínimo {{ hackathon.min_team_members || 1 }} e Máximo {{ hackathon.max_team_members || 5 }} integrantes</span>
                        </div>
                        <span class="badge bg-primary">{{ hackathonTeams.length }} equipes</span>
                    </div>
                    <div class="card-body p-0">
                        <div v-if="hackathonTeams.length === 0" class="text-center py-5 text-muted">
                            <font-awesome-icon :icon="['fas', 'people-group']" class="fs-2 mb-2 opacity-50" />
                            <p class="mb-0">Nenhuma equipe criada até o momento.</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th scope="col" class="ps-3">Equipe</th>
                                        <th scope="col">Líder</th>
                                        <th scope="col">Integrantes / Vagas</th>
                                        <th scope="col">Situação</th>
                                        <th scope="col">Membros Atuais</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="team in hackathonTeams" :key="team.id">
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark">{{ team.name }}</span>
                                            <div v-if="team.area_of_focus" class="text-muted small">{{ team.area_of_focus }}</div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-1">
                                                <font-awesome-icon :icon="['fas', 'crown']" class="text-warning small" />
                                                <span class="small fw-semibold">{{ team.leader?.name || '-' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ team.members?.length || 0 }}</span>
                                            <span class="text-muted"> / {{ hackathon.max_team_members }}</span>
                                            <div class="progress mt-1 team-progress-bar">
                                                <div
                                                    class="progress-bar"
                                                    :class="(team.members?.length || 0) < hackathon.min_team_members ? 'bg-warning' : 'bg-success'"
                                                    :style="{ width: `${Math.min(100, (((team.members?.length || 0) / hackathon.max_team_members) * 100))}%` }"
                                                ></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span v-if="(team.members?.length || 0) < hackathon.min_team_members" class="badge bg-warning-subtle text-warning">
                                                Incompleta (&lt; {{ hackathon.min_team_members }})
                                            </span>
                                            <span v-else-if="(team.members?.length || 0) >= hackathon.max_team_members" class="badge bg-secondary-subtle text-secondary">
                                                Lotada
                                            </span>
                                            <span v-else class="badge bg-success-subtle text-success">
                                                Ativa (Vagas abertas)
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <span
                                                    v-for="member in team.members"
                                                    :key="member.id"
                                                    class="badge bg-light text-dark border small"
                                                >
                                                    {{ member.user?.name || member.name }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Formato de Participação (Prompt Master Section 5) -->
            <div v-if="activeTab === 'format'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3">
                    <h2 class="h6 fw-bold mb-0 text-dark">Configurações de Formato de Participação & Equipes</h2>
                    <span class="text-muted small">Defina a modalidade de participação deste hackathon, limites de tamanho de equipes e permissões operacionais.</span>
                </div>
                <div class="card-body p-4">
                    <form @submit.prevent="submitUpdateFormat">
                        <!-- Modality -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Modalidade de Participação *</label>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div
                                        class="card h-100 p-3 border-2 cursor-pointer"
                                        :class="formatForm.registration_type === 'individual' ? 'border-primary bg-primary-subtle bg-opacity-25' : 'border-light-subtle'"
                                        @click="formatForm.registration_type = 'individual'"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="registration_type"
                                                value="individual"
                                                v-model="formatForm.registration_type"
                                                id="mod_individual"
                                            />
                                            <label class="form-check-label fw-bold text-dark" for="mod_individual">
                                                Apenas Individual
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">
                                            Os participantes se inscrevem e submetem soluções individualmente. Não há formação de equipes.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div
                                        class="card h-100 p-3 border-2 cursor-pointer"
                                        :class="formatForm.registration_type === 'team_only' ? 'border-primary bg-primary-subtle bg-opacity-25' : 'border-light-subtle'"
                                        @click="formatForm.registration_type = 'team_only'"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="registration_type"
                                                value="team_only"
                                                v-model="formatForm.registration_type"
                                                id="mod_team_only"
                                            />
                                            <label class="form-check-label fw-bold text-dark" for="mod_team_only">
                                                Obrigatoriamente por Equipe
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">
                                            A participação requer uma equipe formada. Inscritos avulsos aguardam formação ou alocação antes de ficarem aptos a competir.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div
                                        class="card h-100 p-3 border-2 cursor-pointer"
                                        :class="formatForm.registration_type === 'individual_or_team' ? 'border-primary bg-primary-subtle bg-opacity-25' : 'border-light-subtle'"
                                        @click="formatForm.registration_type = 'individual_or_team'"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="registration_type"
                                                value="individual_or_team"
                                                v-model="formatForm.registration_type"
                                                id="mod_both"
                                            />
                                            <label class="form-check-label fw-bold text-dark" for="mod_both">
                                                Individual ou por Equipe
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">
                                            Flexível. O participante escolhe se compete individualmente ou em equipe. Ambos os tipos são elegíveis.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Team Limits & Permissions (only if teams are supported) -->
                        <div v-if="formatForm.registration_type !== 'individual'" class="border-top pt-4 mt-4">
                            <h3 class="h6 fw-bold text-dark mb-3">Parâmetros de Equipe</h3>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Número Mínimo de Integrantes *</label>
                                    <input
                                        v-model.number="formatForm.min_team_members"
                                        type="number"
                                        min="1"
                                        max="50"
                                        class="form-control"
                                        required
                                    />
                                    <div class="form-text small">Equipes com menos membros serão sinalizadas como incompletas.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Número Máximo de Integrantes *</label>
                                    <input
                                        v-model.number="formatForm.max_team_members"
                                        type="number"
                                        min="1"
                                        max="50"
                                        class="form-control"
                                        required
                                    />
                                    <div class="form-text small">Limite máximo de participantes permitidos em uma mesma equipe.</div>
                                </div>
                            </div>

                            <h3 class="h6 fw-bold text-dark mb-3">Permissões de Formação</h3>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-3 border rounded-3 bg-light">
                                        <input
                                            class="form-check-input ms-0 me-2"
                                            type="checkbox"
                                            id="allow_team_creation"
                                            v-model="formatForm.allow_team_creation"
                                        />
                                        <label class="form-check-label fw-semibold" for="allow_team_creation">
                                            Criação de equipes pelos participantes
                                        </label>
                                        <div class="text-muted small mt-1">Permite que qualquer competidor crie sua própria equipe e se torne líder.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-3 border rounded-3 bg-light">
                                        <input
                                            class="form-check-input ms-0 me-2"
                                            type="checkbox"
                                            id="allow_team_invites"
                                            v-model="formatForm.allow_team_invites"
                                        />
                                        <label class="form-check-label fw-semibold" for="allow_team_invites">
                                            Envio de convites entre participantes
                                        </label>
                                        <div class="text-muted small mt-1">Permite que o líder convide outros inscritos para sua equipe via e-mail.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-3 border rounded-3 bg-light">
                                        <input
                                            class="form-check-input ms-0 me-2"
                                            type="checkbox"
                                            id="allow_team_requests"
                                            v-model="formatForm.allow_team_requests"
                                        />
                                        <label class="form-check-label fw-semibold" for="allow_team_requests">
                                            Solicitações de entrada em equipes
                                        </label>
                                        <div class="text-muted small mt-1">Permite que participantes peçam para ingressar em equipes já existentes.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-3 border rounded-3 bg-light">
                                        <input
                                            class="form-check-input ms-0 me-2"
                                            type="checkbox"
                                            id="allow_org_team_management"
                                            v-model="formatForm.allow_org_team_management"
                                        />
                                        <label class="form-check-label fw-semibold" for="allow_org_team_management">
                                            Gerenciamento de equipes pela organização
                                        </label>
                                        <div class="text-muted small mt-1">Permite à organização alocar participantes, transferir e criar equipes administrativamente.</div>
                                    </div>
                                </div>
                            </div>

                            <h3 class="h6 fw-bold text-dark mb-3">Prazos Limite</h3>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Prazo Limite para Participantes (Formação/Alteração)</label>
                                    <input
                                        v-model="formatForm.team_formation_deadline"
                                        type="datetime-local"
                                        class="form-control"
                                    />
                                    <div class="form-text small">Após esta data, os participantes não poderão mais criar ou alterar equipes.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Prazo Limite para a Organização (Alocações Administrativas)</label>
                                    <input
                                        v-model="formatForm.team_management_deadline"
                                        type="datetime-local"
                                        class="form-control"
                                    />
                                    <div class="form-text small">Após esta data, o painel da organização bloqueia novas alterações manuais de equipes.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <button
                                type="submit"
                                class="btn btn-primary px-4"
                                :disabled="formatForm.processing"
                            >
                                <span v-if="formatForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                                Salvar Configurações de Formato
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Projects -->
            <div v-if="activeTab === 'projects'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body">
                    <div v-if="!hackathon.projects || hackathon.projects.length === 0" class="text-center py-5 text-muted">
                        <font-awesome-icon :icon="['fas', 'file-arrow-up']" class="fs-2 mb-2 opacity-50" />
                        <p class="mb-0">Nenhum projeto submetido até o momento.</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
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

            <!-- Tab 3: Equipe do Hackathon (Sections 9 & 10) -->
            <div v-if="activeTab === 'staff'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h6 fw-bold mb-0 text-dark">Equipe Responsável pelo Hackathon</h2>
                        <span class="text-muted small">Usuários da instituição autorizados a administrar este hackathon específico</span>
                    </div>
                    <button
                        v-if="canManageStaff"
                        type="button"
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                        @click="showAddMemberModal = true"
                    >
                        <font-awesome-icon :icon="['fas', 'user-plus']" />
                        <span>Adicionar Responsável</span>
                    </button>
                </div>

                <div class="card-body p-0">
                    <div v-if="!hackathon.members || hackathon.members.length === 0" class="text-center py-5 text-muted">
                        <font-awesome-icon :icon="['fas', 'user-shield']" class="fs-2 mb-2 opacity-50" />
                        <p class="mb-0">Nenhum responsável designado especificamente para este hackathon.</p>
                        <small class="text-muted">Apenas administradores gerais da instituição têm acesso no momento.</small>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th scope="col" class="ps-3">Usuário</th>
                                    <th scope="col">Função no Hackathon</th>
                                    <th scope="col">Designado Em</th>
                                    <th v-if="canManageStaff" scope="col" class="text-end pe-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="staff in hackathon.members" :key="staff.id">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold avatar-initials">
                                                {{ staff.user?.name?.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block">{{ staff.user?.name }}</span>
                                                <span class="text-muted small">{{ staff.user?.email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill" :class="getStaffRoleBadge(staff.role).class">
                                                {{ getStaffRoleBadge(staff.role).label }}
                                            </span>
                                            <select
                                                v-if="canManageStaff"
                                                class="form-select form-select-sm status-select"
                                                :value="staff.role"
                                                @change="updateStaffRole(staff.id, $event.target.value)"
                                            >
                                                <option value="admin">Administrador</option>
                                                <option value="manager">Gestor</option>
                                                <option value="collaborator">Colaborador</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-muted small">
                                        {{ staff.created_at ? new Date(staff.created_at).toLocaleDateString('pt-BR') : '-' }}
                                    </td>
                                    <td v-if="canManageStaff" class="text-end pe-3">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="removeStaffMember(staff.id, staff.user?.name)"
                                            title="Remover deste hackathon"
                                        >
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Desafios (Challenges) -->
            <div v-if="activeTab === 'challenges'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h6 fw-bold mb-0 text-dark">Desafios do Hackathon</h2>
                        <span class="text-muted small">Temas, problemas e trilhas propostos para resolução pelas equipes</span>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                        @click="showAddChallengeModal = true"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Novo Desafio</span>
                    </button>
                </div>

                <div class="card-body">
                    <div v-if="!hackathon.challenges || hackathon.challenges.length === 0" class="text-center py-5 text-muted">
                        <font-awesome-icon :icon="['fas', 'bullseye']" class="fs-2 mb-2 opacity-50" />
                        <p class="mb-0">Nenhum desafio cadastrado ainda.</p>
                        <small class="text-muted">Adicione os desafios para orientar os participantes sobre os temas a desenvolver.</small>
                    </div>

                    <div v-else class="row g-3">
                        <div v-for="ch in hackathon.challenges" :key="ch.id" class="col-md-6">
                            <div class="card border rounded-3 p-3 h-100 bg-light bg-opacity-25">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary-subtle text-primary">Desafio #{{ ch.order }}</span>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger border-0 p-1"
                                        @click="deleteChallenge(ch.id, ch.title)"
                                    >
                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                    </button>
                                </div>
                                <h5 class="fw-bold text-dark mb-2">{{ ch.title }}</h5>
                                <p v-if="ch.description" class="text-muted small mb-2">{{ ch.description }}</p>
                                <div v-if="ch.requirements" class="mb-2">
                                    <span class="small fw-semibold text-secondary d-block">Requisitos:</span>
                                    <p class="text-muted small mb-0">{{ ch.requirements }}</p>
                                </div>
                                <div v-if="ch.prize" class="mt-auto pt-2 border-top">
                                    <span class="small fw-bold text-success">
                                        <font-awesome-icon :icon="['fas', 'gift']" class="me-1" />
                                        Premiação: {{ ch.prize }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Stages -->
            <div v-if="activeTab === 'stages'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-4">
                    <div v-for="stage in hackathon.stages" :key="stage.id" class="timeline-stage-item mb-3 pb-3 border-bottom">
                        <h6 class="fw-bold mb-1">{{ stage.name }}</h6>
                        <small class="text-muted d-block mb-1">
                            {{ new Date(stage.start_date).toLocaleDateString('pt-BR') }} até {{ new Date(stage.end_date).toLocaleDateString('pt-BR') }}
                        </small>
                        <p class="mb-0 text-secondary">{{ stage.description || 'Sem descrição adicional.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab 6: Evaluation Criteria -->
            <div v-if="activeTab === 'criteria'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div v-for="crit in hackathon.evaluation_criteria" :key="crit.id" class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light bg-opacity-25 h-100">
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

            <!-- Tab 7: Official Rankings -->
            <div v-if="activeTab === 'rankings' && rankings" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
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

        <!-- Modal Add Staff Member (Sections 9 & 10) -->
        <div v-if="showAddMemberModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitAddMember">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Adicionar Responsável ao Hackathon</h5>
                                <span class="small text-muted">{{ hackathon.title }}</span>
                            </div>
                            <button type="button" class="btn-close" @click="showAddMemberModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Usuário da Instituição *</label>
                                <select v-model="memberForm.user_id" class="form-select" required>
                                    <option value="" disabled>Selecione um usuário da instituição</option>
                                    <option v-for="user in availableOrgUsers" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <div v-if="availableOrgUsers.length === 0" class="form-text text-warning small mt-1">
                                    Todos os membros da instituição já estão vinculados ou nenhum está cadastrado.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Função neste Hackathon *</label>
                                <select v-model="memberForm.role" class="form-select" required>
                                    <option value="collaborator">Colaborador (Visualiza e apoia o hackathon)</option>
                                    <option value="manager">Gestor (Gerencia etapas, inscrições e submissões)</option>
                                    <option value="admin">Administrador (Controle total deste hackathon)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showAddMemberModal = false">Cancelar</button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="memberForm.processing || !memberForm.user_id"
                            >
                                Adicionar à Equipe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Add Challenge -->
        <div v-if="showAddChallengeModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitAddChallenge">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Novo Desafio do Hackathon</h5>
                                <span class="small text-muted">{{ hackathon.title }}</span>
                            </div>
                            <button type="button" class="btn-close" @click="showAddChallengeModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Título do Desafio *</label>
                                <input v-model="challengeForm.title" type="text" class="form-control" placeholder="ex: Solução para Eficiência Energética Urbana" required />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descrição do Problema</label>
                                <textarea v-model="challengeForm.description" class="form-control" rows="3" placeholder="Contextualize a dor a ser resolvida..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Requisitos / Regras Específicas</label>
                                <textarea v-model="challengeForm.requirements" class="form-control" rows="2" placeholder="Tecnologias obrigatórias, formato de protótipo, etc."></textarea>
                            </div>

                            <div class="row g-2">
                                <div class="col-8">
                                    <label class="form-label fw-semibold">Premiação (opcional)</label>
                                    <input v-model="challengeForm.prize" type="text" class="form-control" placeholder="ex: R$ 5.000 + Incubação" />
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Ordem</label>
                                    <input v-model="challengeForm.order" type="number" class="form-control" min="1" />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showAddChallengeModal = false">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="challengeForm.processing">
                                Salvar Desafio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal 1: Alocar Participante em Equipe (Prompt Master Section 8) -->
        <div v-if="showAssignModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitAssign">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Alocar Participante em Equipe</h5>
                                <span class="small text-muted">{{ selectedRegistration?.user?.name }} ({{ selectedRegistration?.user?.email }})</span>
                            </div>
                            <button type="button" class="btn-close" @click="showAssignModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Selecione a Equipe de Destino *</label>
                                <select v-model="assignForm.team_id" class="form-select" required>
                                    <option value="" disabled>Escolha uma equipe com vagas disponíveis</option>
                                    <option v-for="team in availableTeamsForAssignment" :key="team.id" :value="team.id">
                                        {{ team.name }} ({{ team.members?.length || 0 }}/{{ hackathon.max_team_members }} integrantes) - Líder: {{ team.leader?.name || '-' }}
                                    </option>
                                </select>
                                <div v-if="availableTeamsForAssignment.length === 0" class="form-text text-danger small mt-1">
                                    Não há equipes com vagas abertas no momento. Crie uma nova equipe para alocar o participante.
                                </div>
                            </div>
                            <div class="alert alert-info py-2 px-3 small mb-0">
                                <font-awesome-icon :icon="['fas', 'info-circle']" class="me-1" />
                                O participante será vinculado imediatamente e sua inscrição se tornará elegível para o hackathon.
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showAssignModal = false">Cancelar</button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="assignForm.processing || !assignForm.team_id"
                            >
                                <span v-if="assignForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                                Confirmar Alocação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal 2: Transferir Participante entre Equipes -->
        <div v-if="showTransferModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitTransfer">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Transferir de Equipe</h5>
                                <span class="small text-muted">{{ selectedRegistration?.user?.name }} (Equipe Atual: {{ selectedRegistration?.team?.name }})</span>
                            </div>
                            <button type="button" class="btn-close" @click="showTransferModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nova Equipe *</label>
                                <select v-model="transferForm.target_team_id" class="form-select" required>
                                    <option value="" disabled>Escolha a nova equipe de destino</option>
                                    <option
                                        v-for="team in (hackathon.teams || []).filter(t => t.id !== selectedRegistration?.team_id && (t.members?.length || 0) < hackathon.max_team_members)"
                                        :key="team.id"
                                        :value="team.id"
                                    >
                                        {{ team.name }} ({{ team.members?.length || 0 }}/{{ hackathon.max_team_members }} membros)
                                    </option>
                                </select>
                            </div>
                            <div class="alert alert-warning py-2 px-3 small mb-0">
                                <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                                O participante será removido da equipe atual e adicionado à nova equipe selecionada.
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showTransferModal = false">Cancelar</button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="transferForm.processing || !transferForm.target_team_id"
                            >
                                <span v-if="transferForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                                Confirmar Transferência
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal 3: Criar Equipe Administrativamente (Organização) -->
        <div v-if="showCreateTeamOrgModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitCreateTeamOrg">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Criar Equipe (Organização)</h5>
                                <span class="small text-muted">Criação administrativa de equipe para participantes do hackathon</span>
                            </div>
                            <button type="button" class="btn-close" @click="showCreateTeamOrgModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label class="form-label fw-semibold">Nome da Equipe *</label>
                                    <input
                                        v-model="createTeamOrgForm.name"
                                        type="text"
                                        class="form-control"
                                        placeholder="ex: Equipe Inovação Alpha"
                                        required
                                    />
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">Área de Foco</label>
                                    <input
                                        v-model="createTeamOrgForm.area_of_focus"
                                        type="text"
                                        class="form-control"
                                        placeholder="ex: Inteligência Artificial"
                                    />
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Líder da Equipe *</label>
                                <select v-model="createTeamOrgForm.leader_id" class="form-select" required>
                                    <option value="" disabled>Selecione um participante inscrito</option>
                                    <option
                                        v-for="reg in (individualRegistrations.length > 0 ? individualRegistrations : hackathon.registrations)"
                                        :key="reg.user_id"
                                        :value="reg.user_id"
                                    >
                                        {{ reg.user?.name }} ({{ reg.user?.email }}) - {{ reg.team_id ? 'Já possui equipe' : 'Sem equipe' }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descrição do Projeto / Equipe</label>
                                <textarea
                                    v-model="createTeamOrgForm.description"
                                    class="form-control"
                                    rows="2"
                                    placeholder="Breve descrição da proposta da equipe..."
                                ></textarea>
                            </div>

                            <div v-if="individualRegistrations.filter(r => r.user_id !== createTeamOrgForm.leader_id).length > 0" class="mb-3">
                                <label class="form-label fw-semibold">Adicionar Outros Inscritos Sem Equipe (Opcional)</label>
                                <div class="border rounded-3 p-3 bg-light scrollable-box">
                                    <div
                                        v-for="reg in individualRegistrations.filter(r => r.user_id !== createTeamOrgForm.leader_id)"
                                        :key="reg.id"
                                        class="form-check mb-2"
                                    >
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :value="reg.id"
                                            :id="'reg_cb_' + reg.id"
                                            v-model="createTeamOrgForm.member_registration_ids"
                                        />
                                        <label class="form-check-label small" :for="'reg_cb_' + reg.id">
                                            <strong>{{ reg.user?.name }}</strong> ({{ reg.user?.email }})
                                        </label>
                                    </div>
                                </div>
                                <div class="form-text small">
                                    Limite máximo de {{ hackathon.max_team_members }} membros por equipe (incluindo o líder).
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showCreateTeamOrgModal = false">Cancelar</button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="createTeamOrgForm.processing || !createTeamOrgForm.name || !createTeamOrgForm.leader_id"
                            >
                                <span v-if="createTeamOrgForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                                Criar Equipe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>
