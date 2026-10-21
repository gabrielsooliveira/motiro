<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ModalDialog from "@/js/Components/Modals/ModalDialog.vue";
import ModalConfirmation from "@/js/Components/Modals/ModalConfirmation.vue";

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
    canManageStages: {
        type: Boolean,
        default: false,
    },
    canManageParticipants: {
        type: Boolean,
        default: false,
    },
    canUpdate: {
        type: Boolean,
        default: true,
    },
});

const showStatusModal = ref(false);
const selectedNewStatus = ref(props.hackathon.status);
const isUpdatingStatus = ref(false);

const statusOptions = [
    {
        value: "registration",
        label: "Inscrições Abertas",
        badgeClass: "bg-success text-white",
        btnClass: "btn-outline-success",
        icon: "door-open",
        description: "Visível no catálogo público e disponível para participantes e equipes se inscreverem.",
    },
    {
        value: "published",
        label: "Publicado (Em Breve)",
        badgeClass: "bg-info text-dark",
        btnClass: "btn-outline-info",
        icon: "globe",
        description: "Visível no catálogo para divulgação prévia do evento conforme as datas do cronograma.",
    },
    {
        value: "submission",
        label: "Submissão de Projetos",
        badgeClass: "bg-warning text-dark",
        btnClass: "btn-outline-warning",
        icon: "code",
        description: "Inscrições finalizadas. Os participantes desenvolvem e enviam seus projetos e documentação.",
    },
    {
        value: "voting",
        label: "Em Avaliação",
        badgeClass: "bg-primary text-white",
        btnClass: "btn-outline-primary",
        icon: "scale-balanced",
        description: "Submissões encerradas. Projetos distribuídos e sob avaliação da banca julgadora.",
    },
    {
        value: "finished",
        label: "Encerrado",
        badgeClass: "bg-secondary text-white",
        btnClass: "btn-outline-secondary",
        icon: "flag-checkered",
        description: "Hackathon encerrado e resultados finais publicados.",
    },
    {
        value: "draft",
        label: "Rascunho",
        badgeClass: "bg-dark text-white",
        btnClass: "btn-outline-dark",
        icon: "file-pen",
        description: "Oculto dos participantes e do catálogo público. Visível apenas para a equipe organizadora.",
    },
];

const currentStatusObj = computed(() => {
    return statusOptions.find(s => s.value === props.hackathon.status) || {
        value: props.hackathon.status,
        label: props.hackathon.status,
        badgeClass: "bg-secondary text-white",
        btnClass: "btn-outline-secondary",
        icon: "circle-info",
        description: "",
    };
});

const openStatusModal = () => {
    selectedNewStatus.value = props.hackathon.status;
    showStatusModal.value = true;
};

const confirmationState = ref({
    isVisible: false,
    message: "",
    subMessage: "",
    confirmText: "Confirmar",
    cancelText: "Cancelar",
    onConfirm: () => {},
});

const openConfirmation = ({ message, subMessage = "", confirmText = "Confirmar", cancelText = "Cancelar", onConfirm }) => {
    confirmationState.value = {
        isVisible: true,
        message,
        subMessage,
        confirmText,
        cancelText,
        onConfirm,
    };
};

const quickChangeStatus = (newStatus) => {
    if (newStatus === props.hackathon.status) return;
    const targetLabel = statusOptions.find(s => s.value === newStatus)?.label || newStatus;
    openConfirmation({
        message: "Alterar Status",
        subMessage: `Deseja alterar o status do hackathon para "${targetLabel}"?`,
        confirmText: "Alterar Status",
        onConfirm: () => {
            router.patch(route("hackathon.manage.status", props.hackathon.id), {
                status: newStatus,
            }, {
                preserveScroll: true,
            });
        },
    });
};

const submitChangeStatus = () => {
    if (selectedNewStatus.value === props.hackathon.status) {
        showStatusModal.value = false;
        return;
    }
    isUpdatingStatus.value = true;
    router.patch(route("hackathon.manage.status", props.hackathon.id), {
        status: selectedNewStatus.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showStatusModal.value = false;
            isUpdatingStatus.value = false;
        },
        onError: () => {
            isUpdatingStatus.value = false;
        },
    });
};

const activeTab = ref("registrations");

const getTeamProgressClass = (count, max) => {
    return 'progress-w-' + Math.min(100, Math.max(0, Math.round(((count || 0) / (max || 1)) * 100)));
};

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
    status: props.hackathon.status || "draft",
    registration_type: props.hackathon.registration_type || "individual_or_team",
    team_formation_mode: props.hackathon.team_formation_mode || "by_participants",
    team_leader_required: props.hackathon.team_leader_required ?? true,
    min_team_members: props.hackathon.min_team_members || 1,
    max_team_members: props.hackathon.max_team_members || 5,
    allow_team_creation: props.hackathon.allow_team_creation ?? true,
    allow_team_invites: props.hackathon.allow_team_invites ?? true,
    allow_team_requests: props.hackathon.allow_team_requests ?? true,
    allow_org_team_management: props.hackathon.allow_org_team_management ?? true,
    registration_start_date: props.hackathon.registration_start_date ? props.hackathon.registration_start_date.substring(0, 16) : "",
    registration_end_date: props.hackathon.registration_end_date ? props.hackathon.registration_end_date.substring(0, 16) : "",
    start_date: props.hackathon.start_date ? props.hackathon.start_date.substring(0, 16) : "",
    end_date: props.hackathon.end_date ? props.hackathon.end_date.substring(0, 16) : "",
    team_formation_deadline: props.hackathon.team_formation_deadline ? props.hackathon.team_formation_deadline.substring(0, 16) : "",
    team_management_deadline: props.hackathon.team_management_deadline ? props.hackathon.team_management_deadline.substring(0, 16) : "",
});

const showSetLeaderModal = ref(false);
const selectedTeamForLeader = ref(null);
const setLeaderForm = useForm({
    leader_id: "",
});

const openSetLeaderModal = (team) => {
    selectedTeamForLeader.value = team;
    setLeaderForm.leader_id = team.leader_id || "";
    showSetLeaderModal.value = true;
};

const submitSetLeader = () => {
    if (!selectedTeamForLeader.value) return;
    setLeaderForm.post(route("organization.hackathon.participants.set_leader", [props.hackathon.id, selectedTeamForLeader.value.id]), {
        onSuccess: () => {
            showSetLeaderModal.value = false;
            setLeaderForm.reset();
        },
    });
};

const handleStatusUpdate = (registrationId, status) => {
    router.patch(route("registration.status.update", registrationId), {
        status: status,
    });
};

const handleDistributeJudging = () => {
    openConfirmation({
        message: "Distribuir Projetos",
        subMessage: "Deseja distribuir os projetos submetidos entre os juízes cadastrados?",
        confirmText: "Distribuir",
        onConfirm: () => {
            router.post(route("hackathon.manage.distribute", props.hackathon.id));
        },
    });
};

const handlePublishResults = () => {
    openConfirmation({
        message: "Finalizar Hackathon",
        subMessage: "Deseja publicar os resultados e finalizar este Hackathon?",
        confirmText: "Publicar e Finalizar",
        onConfirm: () => {
            router.post(route("hackathon.manage.publish", props.hackathon.id));
        },
    });
};

const submitAddMember = () => {
    memberForm.post(route("organization.hackathon.members.store", props.hackathon.id), {
        onSuccess: () => {
            showAddMemberModal.value = false;
            memberForm.reset();
        },
    });
};

const removeStaffMember = (memberId, name) => {
    openConfirmation({
        message: "Remover da Equipe",
        subMessage: `Remover ${name} da equipe responsável deste hackathon?`,
        confirmText: "Remover",
        onConfirm: () => {
            router.delete(route("organization.hackathon.members.destroy", [props.hackathon.id, memberId]));
        },
    });
};

const updateStaffRole = (memberId, newRole) => {
    router.put(route("organization.hackathon.members.update", [props.hackathon.id, memberId]), {
        role: newRole,
    });
};

const submitAddChallenge = () => {
    challengeForm.post(route("organization.hackathon.challenges.store", props.hackathon.id), {
        onSuccess: () => {
            showAddChallengeModal.value = false;
            challengeForm.reset();
        },
    });
};

const deleteChallenge = (challengeId, title) => {
    openConfirmation({
        message: "Remover Desafio",
        subMessage: `Remover o desafio '${title}'?`,
        confirmText: "Remover Desafio",
        onConfirm: () => {
            router.delete(route("organization.hackathon.challenges.destroy", [props.hackathon.id, challengeId]));
        },
    });
};

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
    openConfirmation({
        message: "Remover da Equipe",
        subMessage: `Remover o participante ${reg.user?.name || 'Inscrito'} da equipe ${reg.team?.name}?`,
        confirmText: "Remover",
        onConfirm: () => {
            router.delete(route("organization.hackathon.participants.remove_team", [props.hackathon.id, reg.id]));
        },
    });
};

const openCreateTeamOrgModal = () => {
    createTeamOrgForm.reset();
    createTeamOrgForm.member_registration_ids = [];
    createTeamOrgForm.leader_id = "";
    showCreateTeamOrgModal.value = true;
};

const selectedRegistrationsForNewTeam = computed(() => {
    return individualRegistrations.value.filter(r => createTeamOrgForm.member_registration_ids.includes(r.id));
});

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

const participantSearch = ref("");

const hasGroup = (reg) => {
    return Boolean(reg.hackathon_group_id || reg.team_id || reg.group || reg.team);
};

const individualRegistrations = computed(() => {
    return (props.hackathon.registrations || []).filter(r => !hasGroup(r));
});

const teamRegistrations = computed(() => {
    return (props.hackathon.registrations || []).filter(r => hasGroup(r));
});

const filteredRegistrations = computed(() => {
    let list = props.hackathon.registrations || [];
    if (registrationFilter.value === "individual") {
        list = list.filter(r => !hasGroup(r));
    } else if (registrationFilter.value === "team") {
        list = list.filter(r => hasGroup(r));
    }

    if (participantSearch.value.trim()) {
        const query = participantSearch.value.toLowerCase().trim();
        list = list.filter(r => {
            const name = r.user?.name?.toLowerCase() || "";
            const email = r.user?.email?.toLowerCase() || "";
            return name.includes(query) || email.includes(query);
        });
    }

    return list;
});

const hackathonTeams = computed(() => {
    if (props.hackathon.groups && props.hackathon.groups.length > 0) {
        return props.hackathon.groups;
    }
    return props.hackathon.teams || [];
});

const availableTeamsForAssignment = computed(() => {
    return hackathonTeams.value.filter(t => (t.members?.length || 0) < props.hackathon.max_team_members);
});

const incompleteTeamsCount = computed(() => {
    return hackathonTeams.value.filter(t => (t.members?.length || 0) < props.hackathon.min_team_members).length;
});

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

const showAddStageForm = ref(false);
const editingStageId = ref(null);
const stageForm = useForm({
    name: "",
    description: "",
    start_date: "",
    end_date: "",
    order: 1,
});

const submitStage = () => {
    if (editingStageId.value) {
        stageForm.put(route("hackathon.manage.stage.update", [props.hackathon.id, editingStageId.value]), {
            onSuccess: () => {
                editingStageId.value = null;
                showAddStageForm.value = false;
                stageForm.reset();
            },
        });
    } else {
        stageForm.post(route("hackathon.manage.stage.store", props.hackathon.id), {
            onSuccess: () => {
                showAddStageForm.value = false;
                stageForm.reset();
            },
        });
    }
};

const editStage = (stage) => {
    editingStageId.value = stage.id;
    stageForm.name = stage.name;
    stageForm.description = stage.description || "";
    stageForm.start_date = stage.start_date ? stage.start_date.substring(0, 16) : "";
    stageForm.end_date = stage.end_date ? stage.end_date.substring(0, 16) : "";
    stageForm.order = stage.order || 1;
    showAddStageForm.value = true;
};

const cancelStageForm = () => {
    editingStageId.value = null;
    showAddStageForm.value = false;
    stageForm.reset();
};

const deleteStage = (stageId) => {
    openConfirmation({
        message: "Remover Etapa",
        subMessage: "Deseja realmente remover esta etapa do cronograma?",
        confirmText: "Remover",
        onConfirm: () => {
            router.delete(route("hackathon.manage.stage.destroy", [props.hackathon.id, stageId]));
        },
    });
};

const showAddCriterionForm = ref(false);
const editingCriterionId = ref(null);
const criterionForm = useForm({
    name: "",
    description: "",
    weight: 10,
    min_score: 0,
    max_score: 10,
    order: 1,
});

const totalCriteriaWeight = computed(() => {
    const list = props.hackathon.evaluation_criteria || [];
    return list.reduce((acc, c) => acc + (c.weight > 1 ? c.weight : c.weight * 100), 0);
});

const submitCriterion = () => {
    if (editingCriterionId.value) {
        criterionForm.put(route("hackathon.manage.criterion.update", [props.hackathon.id, editingCriterionId.value]), {
            onSuccess: () => {
                editingCriterionId.value = null;
                showAddCriterionForm.value = false;
                criterionForm.reset();
            },
        });
    } else {
        criterionForm.post(route("hackathon.manage.criterion.store", props.hackathon.id), {
            onSuccess: () => {
                showAddCriterionForm.value = false;
                criterionForm.reset();
            },
        });
    }
};

const editCriterion = (crit) => {
    editingCriterionId.value = crit.id;
    criterionForm.name = crit.name;
    criterionForm.description = crit.description || "";
    criterionForm.weight = crit.weight > 1 ? crit.weight : (crit.weight * 100);
    criterionForm.min_score = crit.min_score || 0;
    criterionForm.max_score = crit.max_score || 10;
    criterionForm.order = crit.order || 1;
    showAddCriterionForm.value = true;
};

const cancelCriterionForm = () => {
    editingCriterionId.value = null;
    showAddCriterionForm.value = false;
    criterionForm.reset();
};

const deleteCriterion = (critId) => {
    openConfirmation({
        message: "Remover Critério",
        subMessage: "Deseja realmente remover este critério de entrega?",
        confirmText: "Remover",
        onConfirm: () => {
            router.delete(route("hackathon.manage.criterion.destroy", [props.hackathon.id, critId]));
        },
    });
};
</script>

<template>
    <Head>
        <title>Gerenciar | {{ hackathon.title }}</title>
    </Head>

    <div class="py-2">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <Link :href="route('organization.dashboard')" class="text-decoration-none text-muted small d-inline-block mb-1">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                        Voltar para o Painel da Organização
                    </Link>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h2 fw-bold text-dark mb-0">{{ hackathon.title }}</h1>
                        <span class="badge bg-light text-secondary border text-uppercase small">{{ hackathon.modality }}</span>
                        <!-- Status Badge / Selector Button -->
                        <button
                            v-if="canUpdate"
                            type="button"
                            class="badge border-0 px-3 py-2 cursor-pointer shadow-sm d-inline-flex align-items-center gap-1"
                            :class="currentStatusObj.badgeClass"
                            @click="openStatusModal"
                            title="Clique para alterar o status do hackathon"
                        >
                            <font-awesome-icon :icon="['fas', currentStatusObj.icon]" class="me-1" />
                            <span>{{ currentStatusObj.label }}</span>
                            <font-awesome-icon :icon="['fas', 'pen-to-square']" class="ms-1 small opacity-75" />
                        </button>
                        <span v-else class="badge px-3 py-2 small" :class="currentStatusObj.badgeClass">
                            {{ currentStatusObj.label }}
                        </span>
                        <span v-if="hackathon.organization" class="badge bg-secondary-subtle text-secondary small">
                            {{ hackathon.organization.name }}
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button
                        v-if="canUpdate"
                        type="button"
                        class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 shadow-sm"
                        @click="openStatusModal"
                    >
                        <font-awesome-icon :icon="['fas', 'sliders']" />
                        <span>Alterar Status</span>
                    </button>
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

            <!-- Banner de Alerta para Hackathon em Rascunho -->
            <div
                v-if="hackathon.status === 'draft'"
                class="alert alert-warning border-warning-subtle shadow-sm rounded-4 p-3 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3"
            >
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-25 p-3 rounded-circle text-warning d-flex align-items-center justify-content-center">
                        <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="fs-4 text-warning" />
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 text-dark">Hackathon em modo Rascunho</h6>
                        <p class="mb-0 text-muted small">
                            Este hackathon está oculto e <strong>não aparece no catálogo de hackathons abertos</strong>. Altere o status para "Inscrições Abertas" para que os participantes possam encontrá-lo e se inscrever.
                        </p>
                    </div>
                </div>
                <div v-if="canUpdate" class="d-flex gap-2 align-self-md-center">
                    <button
                        type="button"
                        class="btn btn-success btn-sm d-flex align-items-center gap-2 text-nowrap px-3 py-2 fw-semibold shadow-sm"
                        @click="quickChangeStatus('registration')"
                    >
                        <font-awesome-icon :icon="['fas', 'door-open']" />
                        <span>Publicar e Abrir Inscrições</span>
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 text-nowrap px-3 py-2"
                        @click="openStatusModal"
                    >
                        <font-awesome-icon :icon="['fas', 'sliders']" />
                        <span>Outros Status</span>
                    </button>
                </div>
            </div>

            
            <ul class="nav nav-tabs mb-4 flex-nowrap">
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
                    <button class="nav-link" :class="{ active: activeTab === 'format' }" @click="activeTab = 'format'">
                        <font-awesome-icon :icon="['fas', 'sliders']" class="me-1" />
                        Formato de Participação
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

            
            <div v-if="activeTab === 'registrations'" class="d-flex flex-column gap-4">
                
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

                
                <div class="card border-0 shadow-sm rounded-3 bg-white">
                    <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold text-dark small">Filtrar:</span>
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
                                        Sem Grupo ({{ individualRegistrations.length }})
                                    </button>
                                    <button
                                        type="button"
                                        class="btn"
                                        :class="registrationFilter === 'team' ? 'btn-primary' : 'btn-outline-secondary'"
                                        @click="registrationFilter = 'team'"
                                    >
                                        Em Grupo ({{ teamRegistrations.length }})
                                    </button>
                                </div>
                            </div>

                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0">
                                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="text-muted" />
                                </span>
                                <input
                                    type="text"
                                    class="form-control border-start-0"
                                    placeholder="Buscar por nome ou e-mail..."
                                    v-model="participantSearch"
                                />
                            </div>
                        </div>
                        <div v-if="hackathon.registration_type !== 'individual' && hackathon.allow_org_team_management">
                            <button
                                type="button"
                                class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                                @click="openCreateTeamOrgModal"
                            >
                                <font-awesome-icon :icon="['fas', 'plus']" />
                                <span>Formar Grupo (Organização)</span>
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
                                            <div v-if="reg.group || reg.team">
                                                <span class="badge bg-light text-dark border me-1">
                                                    <font-awesome-icon :icon="['fas', 'people-group']" class="me-1 text-primary" />
                                                    {{ (reg.group || reg.team).name }}
                                                </span>
                                                <span v-if="(reg.group || reg.team).leader_id === reg.user_id" class="badge bg-primary-subtle text-primary small">Líder</span>
                                                <div class="text-muted small mt-1">
                                                    {{ (reg.group || reg.team).members?.length || 1 }} / {{ hackathon.max_team_members }} integrantes
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
                                        <th scope="col" class="text-end pe-3">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="team in hackathonTeams" :key="team.id">
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark">{{ team.name }}</span>
                                            <div v-if="team.area_of_focus" class="text-muted small">{{ team.area_of_focus }}</div>
                                        </td>
                                        <td>
                                            <div v-if="team.leader" class="d-flex align-items-center gap-1">
                                                <font-awesome-icon :icon="['fas', 'crown']" class="text-warning small" />
                                                <span class="small fw-semibold">{{ team.leader.name }}</span>
                                            </div>
                                            <span v-else class="badge bg-warning-subtle text-warning">Líder pendente</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ team.members?.length || 0 }}</span>
                                            <span class="text-muted"> / {{ hackathon.max_team_members }}</span>
                                            <div class="progress mt-1 team-progress-bar">
                                                <div
                                                    class="progress-bar"
                                                    :class="[
                                                        (team.members?.length || 0) < hackathon.min_team_members ? 'bg-warning' : 'bg-success',
                                                        getTeamProgressClass(team.members?.length, hackathon.max_team_members)
                                                    ]"
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
                                        <td class="text-end pe-3">
                                            <button
                                                v-if="hackathon.allow_org_team_management"
                                                type="button"
                                                class="btn btn-sm btn-outline-primary"
                                                @click="openSetLeaderModal(team)"
                                            >
                                                {{ team.leader_id ? 'Alterar Líder' : 'Definir Líder' }}
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            
            <div v-if="activeTab === 'format'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3">
                    <h2 class="h6 fw-bold mb-0 text-dark">Configurações de Formato de Participação & Equipes</h2>
                    <span class="text-muted small">Defina a modalidade de participação deste hackathon, limites de tamanho de equipes e permissões operacionais.</span>
                </div>
                <div class="card-body p-4">
                    <form @submit.prevent="submitUpdateFormat">
                        
                        <!-- Status Geral do Hackathon -->
                        <div class="mb-4 pb-4 border-bottom">
                            <label class="form-label fw-bold text-dark">Status Geral do Evento *</label>
                            <p class="small text-muted mb-3">
                                Define a visibilidade do hackathon no catálogo e a fase atual do seu ciclo de vida.
                            </p>
                            <div class="row g-3">
                                <div v-for="opt in statusOptions" :key="opt.value" class="col-md-4">
                                    <div
                                        class="card h-100 p-3 border-2 cursor-pointer"
                                        :class="formatForm.status === opt.value ? 'border-primary bg-primary-subtle bg-opacity-25' : 'border-light-subtle'"
                                        @click="formatForm.status = opt.value"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="format_status"
                                                :value="opt.value"
                                                v-model="formatForm.status"
                                                :id="'format_status_' + opt.value"
                                            />
                                            <label class="form-check-label fw-bold text-dark cursor-pointer" :for="'format_status_' + opt.value">
                                                <span class="badge me-1" :class="opt.badgeClass">{{ opt.label }}</span>
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">
                                            {{ opt.description }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

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

                        
                        <div v-if="formatForm.registration_type !== 'individual'" class="border-top pt-4 mt-4">
                            <h3 class="h6 fw-bold text-dark mb-3">Modo de Formação de Equipes</h3>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div
                                        class="card h-100 p-3 border-2 cursor-pointer"
                                        :class="formatForm.team_formation_mode === 'by_participants' ? 'border-primary bg-primary-subtle bg-opacity-25' : 'border-light-subtle'"
                                        @click="formatForm.team_formation_mode = 'by_participants'"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="team_formation_mode"
                                                value="by_participants"
                                                v-model="formatForm.team_formation_mode"
                                                id="form_mode_participants"
                                            />
                                            <label class="form-check-label fw-bold text-dark" for="form_mode_participants">
                                                Pelos Participantes
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">
                                            Os participantes formam suas próprias equipes e escolhem o líder livremente.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div
                                        class="card h-100 p-3 border-2 cursor-pointer"
                                        :class="formatForm.team_formation_mode === 'by_organization' ? 'border-primary bg-primary-subtle bg-opacity-25' : 'border-light-subtle'"
                                        @click="formatForm.team_formation_mode = 'by_organization'"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="team_formation_mode"
                                                value="by_organization"
                                                v-model="formatForm.team_formation_mode"
                                                id="form_mode_org"
                                            />
                                            <label class="form-check-label fw-bold text-dark" for="form_mode_org">
                                                Pela Organização
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">
                                            A organização agrupa e define quais participantes compõem cada equipe.
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div
                                        class="card h-100 p-3 border-2 cursor-pointer"
                                        :class="formatForm.team_formation_mode === 'both' ? 'border-primary bg-primary-subtle bg-opacity-25' : 'border-light-subtle'"
                                        @click="formatForm.team_formation_mode = 'both'"
                                    >
                                        <div class="form-check">
                                            <input
                                                class="form-check-input"
                                                type="radio"
                                                name="team_formation_mode"
                                                value="both"
                                                v-model="formatForm.team_formation_mode"
                                                id="form_mode_both"
                                            />
                                            <label class="form-check-label fw-bold text-dark" for="form_mode_both">
                                                Modo Misto (Ambos)
                                            </label>
                                        </div>
                                        <p class="small text-muted mb-0 mt-2">
                                            Participantes criam equipes e a organização também pode montar e ajustar grupos.
                                        </p>
                                    </div>
                                </div>
                            </div>

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

                            <h3 class="h6 fw-bold text-dark mb-3">Permissões de Formação & Liderança</h3>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-check form-switch p-3 border rounded-3 bg-light">
                                        <input
                                            class="form-check-input ms-0 me-2"
                                            type="checkbox"
                                            id="team_leader_required"
                                            v-model="formatForm.team_leader_required"
                                        />
                                        <label class="form-check-label fw-semibold" for="team_leader_required">
                                            Exigir Líder para Inscrição da Equipe
                                        </label>
                                        <div class="text-muted small mt-1">Se ativo, a equipe só conclui inscrição no evento após ter um líder formalmente eleito.</div>
                                    </div>
                                </div>
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

                            <h3 class="h6 fw-bold text-dark mb-3">Datas e Prazos do Evento</h3>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Início das Inscrições</label>
                                    <input
                                        v-model="formatForm.registration_start_date"
                                        type="datetime-local"
                                        class="form-control"
                                    />
                                    <div class="form-text small">Data e hora a partir da qual os participantes podem se inscrever.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Encerramento das Inscrições</label>
                                    <input
                                        v-model="formatForm.registration_end_date"
                                        type="datetime-local"
                                        class="form-control"
                                    />
                                    <div class="form-text small">Data e hora limite para novas inscrições no evento.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Início Geral do Hackathon</label>
                                    <input
                                        v-model="formatForm.start_date"
                                        type="datetime-local"
                                        class="form-control"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Término Geral do Hackathon</label>
                                    <input
                                        v-model="formatForm.end_date"
                                        type="datetime-local"
                                        class="form-control"
                                    />
                                </div>
                            </div>

                            <h3 class="h6 fw-bold text-dark mb-3">Prazos de Gestão de Equipes</h3>

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

            
            <div v-if="activeTab === 'stages'" class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-4 pb-3 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Cronograma e Prazos das Etapas</h5>
                            <p class="text-muted small mb-0">Defina os prazos limites de início e encerramento para cada fase do hackathon.</p>
                        </div>
                        <button
                            v-if="!showAddStageForm"
                            type="button"
                            class="btn btn-primary btn-sm d-flex align-items-center gap-2"
                            @click="showAddStageForm = true"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" />
                            <span>Adicionar Etapa / Prazo</span>
                        </button>
                    </div>

                    
                    <div v-if="showAddStageForm" class="card border border-primary-subtle rounded-3 p-4 mb-4 bg-light bg-opacity-50">
                        <h6 class="fw-bold text-dark mb-3">
                            {{ editingStageId ? 'Editar Prazos da Etapa' : 'Cadastrar Nova Etapa com Prazos' }}
                        </h6>
                        <form @submit.prevent="submitStage">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Nome da Etapa *</label>
                                    <input v-model="stageForm.name" type="text" class="form-control" placeholder="ex: Desenvolvimento & Mentorias" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Prazo de Início *</label>
                                    <input v-model="stageForm.start_date" type="datetime-local" class="form-control" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold small">Prazo Limite / Término *</label>
                                    <input v-model="stageForm.end_date" type="datetime-local" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Descrição e Instruções aos Participantes</label>
                                <textarea v-model="stageForm.description" class="form-control" rows="2" placeholder="Orientações do que deve ser feito nesta etapa..."></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" @click="cancelStageForm">Cancelar</button>
                                <button type="submit" class="btn btn-primary btn-sm" :disabled="stageForm.processing">
                                    {{ stageForm.processing ? 'Salvando...' : 'Salvar Etapa' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    
                    <div v-if="hackathon.stages?.length > 0" class="d-flex flex-column gap-3">
                        <div
                            v-for="(stage, idx) in hackathon.stages"
                            :key="stage.id"
                            class="border rounded-3 p-3 bg-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3"
                        >
                            <div class="d-flex align-items-start gap-3">
                                <div class="badge bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center stage-order-badge">
                                    {{ idx + 1 }}
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1 text-dark">{{ stage.name }}</h6>
                                    <div class="text-muted small">
                                        <font-awesome-icon :icon="['fas', 'calendar-days']" class="me-1 text-primary" />
                                        {{ new Date(stage.start_date).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) }}
                                        &rarr;
                                        {{ new Date(stage.end_date).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) }}
                                    </div>
                                    <p v-if="stage.description" class="text-secondary small mb-0 mt-1">
                                        {{ stage.description }}
                                    </p>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    @click="editStage(stage)"
                                    title="Editar prazos"
                                >
                                    <font-awesome-icon :icon="['fas', 'pen-to-square']" class="me-1" />
                                    <span>Editar</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    @click="deleteStage(stage.id)"
                                    title="Remover etapa"
                                >
                                    <font-awesome-icon :icon="['fas', 'trash-can']" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-muted">
                        Nenhuma etapa cadastrada para este hackathon.
                    </div>
                </div>
            </div>

            
            <div v-if="activeTab === 'criteria'" class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Pesos de Cada Entrega & Critérios de Avaliação</h5>
                            <p class="text-muted small mb-0">Defina o peso percentual de cada entrega para o cálculo da nota final dos juízes.</p>
                        </div>
                        <button
                            v-if="!showAddCriterionForm"
                            type="button"
                            class="btn btn-primary btn-sm d-flex align-items-center gap-2"
                            @click="showAddCriterionForm = true"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" />
                            <span>Adicionar Critério / Entrega</span>
                        </button>
                    </div>

                    
                    <div class="p-3 rounded-3 mb-4 border" :class="Math.abs(totalCriteriaWeight - 100) < 0.1 ? 'bg-success-subtle border-success-subtle text-success-emphasis' : 'bg-warning-subtle border-warning-subtle text-warning-emphasis'">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold small">
                                <font-awesome-icon :icon="Math.abs(totalCriteriaWeight - 100) < 0.1 ? ['fas', 'circle-check'] : ['fas', 'triangle-exclamation']" class="me-1" />
                                Soma Total dos Pesos das Entregas
                            </span>
                            <span class="fw-bold">{{ totalCriteriaWeight.toFixed(1) }}% de 100%</span>
                        </div>
                        <div class="progress progress-xs">
                            <div
                                class="progress-bar"
                                :class="[
                                    Math.abs(totalCriteriaWeight - 100) < 0.1 ? 'bg-success' : 'bg-warning',
                                    'progress-w-' + Math.min(100, Math.max(0, Math.round(totalCriteriaWeight)))
                                ]"
                            ></div>
                        </div>
                    </div>

                    
                    <div v-if="showAddCriterionForm" class="card border border-primary-subtle rounded-3 p-4 mb-4 bg-light bg-opacity-50">
                        <h6 class="fw-bold text-dark mb-3">
                            {{ editingCriterionId ? 'Editar Critério / Peso da Entrega' : 'Cadastrar Novo Critério / Peso' }}
                        </h6>
                        <form @submit.prevent="submitCriterion">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small">Nome do Critério / Entrega *</label>
                                    <input v-model="criterionForm.name" type="text" class="form-control" placeholder="ex: Inovação e Viabilidade Técnica" required />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold small">Peso (%) *</label>
                                    <div class="input-group">
                                        <input v-model.number="criterionForm.weight" type="number" step="0.5" min="0" max="100" class="form-control" required />
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold small">Nota Mínima</label>
                                    <input v-model.number="criterionForm.min_score" type="number" step="0.5" class="form-control" />
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold small">Nota Máxima</label>
                                    <input v-model.number="criterionForm.max_score" type="number" step="0.5" class="form-control" />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Descrição & Orientações para a Banca de Juízes</label>
                                <textarea v-model="criterionForm.description" class="form-control" rows="2" placeholder="O que o juiz deve analisar nesta entrega..."></textarea>
                            </div>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-secondary btn-sm" @click="cancelCriterionForm">Cancelar</button>
                                <button type="submit" class="btn btn-primary btn-sm" :disabled="criterionForm.processing">
                                    {{ criterionForm.processing ? 'Salvando...' : 'Salvar Critério' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    
                    <div v-if="hackathon.evaluation_criteria?.length > 0" class="row g-3">
                        <div v-for="crit in hackathon.evaluation_criteria" :key="crit.id" class="col-md-6">
                            <div class="card border rounded-3 p-3 bg-white h-100 shadow-sm d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="fw-bold mb-0 text-dark">{{ crit.name }}</h6>
                                        <span class="badge bg-primary text-white fw-semibold">
                                            Peso: {{ (crit.weight > 1 ? crit.weight : crit.weight * 100).toFixed(0) }}%
                                        </span>
                                    </div>
                                    <small class="text-muted d-block mb-2">Notas permitidas: {{ crit.min_score }} até {{ crit.max_score }}</small>
                                    <p class="text-secondary small mb-3">{{ crit.description || 'Critério oficial de avaliação do hackathon.' }}</p>
                                </div>
                                <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        @click="editCriterion(crit)"
                                        title="Editar critério"
                                    >
                                        <font-awesome-icon :icon="['fas', 'pen-to-square']" class="me-1" />
                                        <span>Editar</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        @click="deleteCriterion(crit.id)"
                                        title="Remover critério"
                                    >
                                        <font-awesome-icon :icon="['fas', 'trash-can']" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-muted">
                        Nenhum critério ou peso de entrega cadastrado.
                    </div>
                </div>
            </div>

            
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

        <!-- Modal para Alteração de Status do Hackathon -->
        <ModalDialog
            :is-visible="showStatusModal"
            title="Alterar Status do Hackathon"
            size="lg"
            @close="showStatusModal = false"
        >
            <form @submit.prevent="submitChangeStatus">
                <div class="mb-3 text-muted small">
                    Hackathon: <strong class="text-dark">{{ hackathon.title }}</strong>
                </div>
                <div class="alert alert-info border-info-subtle bg-info-subtle bg-opacity-25 rounded-3 mb-4 small">
                    <font-awesome-icon :icon="['fas', 'circle-info']" class="me-1 text-primary" />
                    <strong>Dica:</strong> Para que o hackathon apareça listado no <strong>catálogo de hackathons abertos</strong> dos participantes, selecione <strong>Inscrições Abertas</strong>.
                </div>

                <label class="form-label fw-semibold text-dark mb-2">Selecione o novo status:</label>
                <div class="row g-3 mb-4">
                    <div v-for="opt in statusOptions" :key="opt.value" class="col-md-6">
                        <div
                            class="card h-100 p-3 border-2 cursor-pointer transition-all"
                            :class="selectedNewStatus === opt.value ? 'border-primary bg-primary-subtle bg-opacity-25 shadow-sm' : 'border-light-subtle'"
                            @click="selectedNewStatus = opt.value"
                        >
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="form-check mb-0">
                                    <input
                                        class="form-check-input"
                                        type="radio"
                                        name="modal_status"
                                        :value="opt.value"
                                        v-model="selectedNewStatus"
                                        :id="'modal_status_' + opt.value"
                                    />
                                    <label class="form-check-label fw-bold text-dark cursor-pointer ms-1" :for="'modal_status_' + opt.value">
                                        {{ opt.label }}
                                    </label>
                                </div>
                                <span class="badge" :class="opt.badgeClass">{{ opt.value }}</span>
                            </div>
                            <p class="small text-muted mb-0 mt-1">
                                {{ opt.description }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showStatusModal = false">Cancelar</button>
                    <button
                        type="submit"
                        class="btn btn-primary d-flex align-items-center gap-2"
                        :disabled="isUpdatingStatus || selectedNewStatus === hackathon.status"
                    >
                        <font-awesome-icon v-if="isUpdatingStatus" :icon="['fas', 'spinner']" class="fa-spin" />
                        <font-awesome-icon v-else :icon="['fas', 'check']" />
                        <span>{{ isUpdatingStatus ? 'Atualizando...' : 'Confirmar Alteração de Status' }}</span>
                    </button>
                </div>
            </form>
        </ModalDialog>
        
        <!-- Modal: Adicionar Responsável ao Hackathon -->
        <ModalDialog
            :is-visible="showAddMemberModal"
            title="Adicionar Responsável ao Hackathon"
            size="md"
            @close="showAddMemberModal = false"
        >
            <form @submit.prevent="submitAddMember">
                <div class="mb-3 text-muted small">
                    Hackathon: <strong class="text-dark">{{ hackathon.title }}</strong>
                </div>
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

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
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
        </ModalDialog>
        
        <!-- Modal: Novo Desafio do Hackathon -->
        <ModalDialog
            :is-visible="showAddChallengeModal"
            title="Novo Desafio do Hackathon"
            size="md"
            @close="showAddChallengeModal = false"
        >
            <form @submit.prevent="submitAddChallenge">
                <div class="mb-3 text-muted small">
                    Hackathon: <strong class="text-dark">{{ hackathon.title }}</strong>
                </div>
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

                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label fw-semibold">Premiação (opcional)</label>
                        <input v-model="challengeForm.prize" type="text" class="form-control" placeholder="ex: R$ 5.000 + Incubação" />
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Ordem</label>
                        <input v-model="challengeForm.order" type="number" class="form-control" min="1" />
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showAddChallengeModal = false">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="challengeForm.processing">
                        Salvar Desafio
                    </button>
                </div>
            </form>
        </ModalDialog>
        
        <!-- Modal: Alocar Participante em Equipe -->
        <ModalDialog
            :is-visible="showAssignModal"
            title="Alocar Participante em Equipe"
            size="md"
            @close="showAssignModal = false"
        >
            <form @submit.prevent="submitAssign">
                <div class="mb-3 text-muted small">
                    Participante: <strong class="text-dark">{{ selectedRegistration?.user?.name }}</strong> ({{ selectedRegistration?.user?.email }})
                </div>
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
                <div class="alert alert-info py-2 px-3 small mb-3">
                    <font-awesome-icon :icon="['fas', 'info-circle']" class="me-1" />
                    O participante será vinculado imediatamente e sua inscrição se tornará elegível para o hackathon.
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
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
        </ModalDialog>
        
        <!-- Modal: Transferir de Equipe -->
        <ModalDialog
            :is-visible="showTransferModal"
            title="Transferir de Equipe"
            size="md"
            @close="showTransferModal = false"
        >
            <form @submit.prevent="submitTransfer">
                <div class="mb-3 text-muted small">
                    Participante: <strong class="text-dark">{{ selectedRegistration?.user?.name }}</strong> (Equipe Atual: {{ selectedRegistration?.team?.name }})
                </div>
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
                <div class="alert alert-warning py-2 px-3 small mb-3">
                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                    O participante será removido da equipe atual e adicionado à nova equipe selecionada.
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
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
        </ModalDialog>

        <!-- Modal: Criar Equipe pela Organização -->
        <ModalDialog
            :is-visible="showCreateTeamOrgModal"
            title="Criar Equipe (Organização)"
            size="lg"
            @close="showCreateTeamOrgModal = false"
        >
            <form @submit.prevent="submitCreateTeamOrg">
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
                    <label class="form-label fw-semibold">Descrição do Projeto / Equipe</label>
                    <textarea
                        v-model="createTeamOrgForm.description"
                        class="form-control"
                        rows="2"
                        placeholder="Breve descrição da proposta da equipe..."
                    ></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Selecionar Participantes Inscritos *</label>
                    <div v-if="individualRegistrations.length === 0" class="alert alert-secondary small py-2">
                        Não há participantes inscritos sem equipe disponíveis no momento.
                    </div>
                    <div v-else class="border rounded-3 p-3 bg-light scrollable-box">
                        <div
                            v-for="reg in individualRegistrations"
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
                        Mínimo: {{ hackathon.min_team_members || 1 }} e Máximo: {{ hackathon.max_team_members }} participantes.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Designar Líder (Opcional)</label>
                    <select v-model="createTeamOrgForm.leader_id" class="form-select">
                        <option value="">(Deixar sem líder inicial — integrantes elegerão)</option>
                        <option
                            v-for="reg in selectedRegistrationsForNewTeam"
                            :key="reg.user_id"
                            :value="reg.user_id"
                        >
                            {{ reg.user?.name }} ({{ reg.user?.email }})
                        </option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showCreateTeamOrgModal = false">Cancelar</button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="createTeamOrgForm.processing || !createTeamOrgForm.name || createTeamOrgForm.member_registration_ids.length === 0"
                    >
                        <span v-if="createTeamOrgForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                        Criar Equipe
                    </button>
                </div>
            </form>
        </ModalDialog>

        <!-- Modal: Definir / Alterar Líder pela Organização -->
        <ModalDialog
            :is-visible="showSetLeaderModal"
            title="Definir Líder da Equipe"
            size="md"
            @close="showSetLeaderModal = false"
        >
            <form @submit.prevent="submitSetLeader">
                <div class="mb-3 text-muted small">
                    Equipe: <strong class="text-dark">{{ selectedTeamForLeader?.name }}</strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Selecione o Líder</label>
                    <select v-model="setLeaderForm.leader_id" class="form-select">
                        <option value="">(Deixar em aberto / sem líder)</option>
                        <option
                            v-for="member in (selectedTeamForLeader?.members || [])"
                            :key="member.id"
                            :value="member.id"
                        >
                            {{ member.name }} ({{ member.email }}) {{ member.id === selectedTeamForLeader?.leader_id ? '— [Atual Líder]' : '' }}
                        </option>
                    </select>
                    <div class="form-text small">
                        Apenas integrantes que pertençam a esta equipe podem ser definidos como líder.
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showSetLeaderModal = false">Cancelar</button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="setLeaderForm.processing"
                    >
                        <span v-if="setLeaderForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                        Salvar Liderança
                    </button>
                </div>
            </form>
        </ModalDialog>

        <!-- Confirmação Unificada de Ações -->
        <ModalConfirmation
            :is-visible="confirmationState.isVisible"
            :message="confirmationState.message"
            :sub-message="confirmationState.subMessage"
            :confirm-text="confirmationState.confirmText"
            :cancel-text="confirmationState.cancelText"
            @confirm="confirmationState.onConfirm(); confirmationState.isVisible = false"
            @close="confirmationState.isVisible = false"
        />
</template>
