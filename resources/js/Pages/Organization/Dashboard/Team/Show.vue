<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ModalDialog from "@/js/Components/Modals/ModalDialog.vue";
import ModalConfirmation from "@/js/Components/Modals/ModalConfirmation.vue";

const props = defineProps({
    team: {
        type: Object,
        required: true,
    },
    isLeader: {
        type: Boolean,
        default: false,
    },
    isCreator: {
        type: Boolean,
        default: false,
    },
    canManage: {
        type: Boolean,
        default: false,
    },
    hasLeader: {
        type: Boolean,
        default: false,
    },
    canElectLeader: {
        type: Boolean,
        default: false,
    },
    currentUserId: {
        type: String,
        default: "",
    },
});

const activeTab = ref("members");
const showElectLeaderModal = ref(false);

const teamRoute = (action, param = null) => {
    const teamAction = `participant.teams.${action}`;
    const guildAction = `participant.guilds.${action}`;
    const name = route().has(teamAction) ? teamAction : (route().has(guildAction) ? guildAction : teamAction);
    return param !== null ? route(name, param) : route(name);
};

const hackathon = computed(() => {
    return props.team.hackathon || (props.team.registrations && props.team.registrations[0]?.hackathon) || null;
});

const project = computed(() => {
    return props.team.projects && props.team.projects.length > 0 ? props.team.projects[0] : null;
});

// Formulário de Convite de Membro para a Equipe
const inviteForm = useForm({
    email_or_username: "",
});

const submitInvite = () => {
    inviteForm.post(teamRoute("invite", props.team.id), {
        onSuccess: () => inviteForm.reset(),
    });
};

// Formulário de Liderança
const leaderForm = useForm({
    leader_id: props.team.leader_id || "",
});

const submitElectLeader = () => {
    leaderForm.post(teamRoute("leader", props.team.id), {
        preserveScroll: true,
        onSuccess: () => {
            showElectLeaderModal.value = false;
        },
    });
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

const transferLeadershipTo = (memberId) => {
    openConfirmation({
        message: "Transferir Liderança",
        subMessage: "Deseja transferir a liderança da equipe para este integrante?",
        confirmText: "Transferir",
        onConfirm: () => {
            router.post(
                teamRoute("leader", props.team.id),
                { leader_id: memberId },
                { preserveScroll: true }
            );
        },
    });
};

// Ações de Membros
const handleRemoveMember = (member) => {
    openConfirmation({
        message: "Remover Integrante",
        subMessage: `Tem certeza de que deseja remover ${member.name} da equipe?`,
        confirmText: "Remover",
        onConfirm: () => {
            router.delete(teamRoute("members.remove", { id: props.team.id, userId: member.id }));
        },
    });
};

const handleLeaveTeam = () => {
    openConfirmation({
        message: "Sair da Equipe",
        subMessage: "Tem certeza de que deseja sair desta equipe?",
        confirmText: "Sair",
        onConfirm: () => {
            router.post(teamRoute("leave", props.team.id));
        },
    });
};

// Ações de Convites
const handleResendInvite = (inviteId) => {
    router.post(teamRoute("invite.resend", inviteId));
};

const handleCancelInvite = (inviteId) => {
    openConfirmation({
        message: "Cancelar Convite",
        subMessage: "Deseja cancelar este convite?",
        confirmText: "Cancelar Convite",
        onConfirm: () => {
            router.delete(teamRoute("invite.cancel", inviteId));
        },
    });
};

const formatDate = (isoString) => {
    if (!isoString) return "";
    const date = new Date(isoString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <Head>
        <title>Equipe de Hackathon | {{ team.name }}</title>
    </Head>

    <div class="py-3">
        <!-- Navegação de Retorno -->
        <div class="mb-3">
            <Link
                :href="teamRoute('index')"
                class="text-decoration-none text-muted small d-inline-flex align-items-center gap-2"
            >
                <font-awesome-icon :icon="['fas', 'arrow-left']" />
                <span>Voltar para Equipes de Hackathon</span>
            </Link>
        </div>

        <!-- Cabeçalho Principal da Equipe de Hackathon -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-4 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-2 guild-avatar">
                            {{ team.name?.charAt(0)?.toUpperCase() || 'E' }}
                        </div>

                        <div>
                            <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                <h1 class="h3 fw-bold text-dark mb-0">{{ team.name }}</h1>
                                <span v-if="hackathon" class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <font-awesome-icon :icon="['fas', 'trophy']" class="me-1" />
                                    {{ hackathon.title }}
                                </span>
                                <span v-if="isLeader" class="badge bg-warning text-dark">
                                    <font-awesome-icon :icon="['fas', 'crown']" class="me-1 small" /> Líder da Equipe
                                </span>
                                <span v-else-if="!team.leader_id" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                    Líder em aberto
                                </span>
                                <span v-else class="badge bg-light text-secondary border">
                                    Integrante
                                </span>
                            </div>

                            <p v-if="team.area_of_focus" class="text-muted small mb-0">
                                <strong>Área de Foco / Trilha:</strong> {{ team.area_of_focus }}
                            </p>
                        </div>
                    </div>

                    <div v-if="hackathon" class="text-md-end">
                        <Link
                            :href="route('hackathon.show', hackathon.slug)"
                            class="btn btn-outline-primary btn-sm rounded-pill px-3"
                        >
                            Ver Hackathon &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação por Abas Responsiva da Equipe -->
        <ul class="nav nav-pills gap-2 border-bottom pb-3 mb-4 flex-nowrap overflow-x-auto" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'members' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="activeTab = 'members'"
                >
                    <font-awesome-icon :icon="['fas', 'users']" />
                    <span>Integrantes da Equipe</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'members' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ team.members ? team.members.length : 0 }}
                    </span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'project' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="activeTab = 'project'"
                >
                    <font-awesome-icon :icon="['fas', 'code']" />
                    <span>Projeto no Hackathon</span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'invites' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="activeTab = 'invites'"
                >
                    <font-awesome-icon :icon="['fas', 'envelope']" />
                    <span>Convites</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'invites' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ team.invitations ? team.invitations.length : 0 }}
                    </span>
                </button>
            </li>
        </ul>

        <!-- Viewport do Conteúdo das Abas da Equipe -->
        <div class="tab-content-viewport pb-5">
            <!-- ABA 1: INTEGRANTES -->
            <div v-if="activeTab === 'members'">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                            <font-awesome-icon :icon="['fas', 'users']" class="text-primary" />
                            <span>Integrantes inscritos no Hackathon ({{ team.members ? team.members.length : 0 }})</span>
                        </h5>
                        <button
                            v-if="canManage"
                            type="button"
                            class="btn btn-outline-primary btn-sm rounded-pill px-3"
                            @click="activeTab = 'invites'"
                        >
                            <font-awesome-icon :icon="['fas', 'user-plus']" class="me-1" />
                            <span>Convidar Participante</span>
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="member in team.members"
                                :key="member.id"
                                class="list-group-item d-flex justify-content-between align-items-center p-3"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center fw-bold"
                                        style="width: 44px; height: 44px;"
                                    >
                                        {{ member.name?.charAt(0)?.toUpperCase() || 'U' }}
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <h6 class="mb-0 fw-bold text-dark">{{ member.name }}</h6>
                                            <span v-if="member.id === currentUserId" class="badge bg-light text-dark border small">Você</span>
                                        </div>
                                        <small class="text-muted">{{ member.email }}</small>
                                    </div>
                                </div>

                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span v-if="member.id === team.leader_id" class="badge bg-warning text-dark">
                                        <font-awesome-icon :icon="['fas', 'crown']" class="me-1 small" /> Líder
                                    </span>
                                    <span v-else class="badge bg-light text-secondary border">
                                        Integrante
                                    </span>

                                    <div v-if="canManage && member.id !== currentUserId" class="d-flex gap-1 ms-2">
                                        <button
                                            v-if="member.id !== team.leader_id"
                                            type="button"
                                            class="btn btn-outline-warning btn-sm py-1 px-2 small"
                                            title="Tornar Líder da Equipe"
                                            @click="transferLeadershipTo(member.id)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'crown']" class="me-1" />
                                            <span>Líder</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger btn-sm py-1 px-2 small"
                                            title="Remover da equipe"
                                            @click="handleRemoveMember(member)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'user-minus']" class="me-1" />
                                            <span>Remover</span>
                                        </button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ABA 2: PROJETO -->
            <div v-if="activeTab === 'project'">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-transparent py-3 border-bottom">
                        <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                            <font-awesome-icon :icon="['fas', 'code']" class="text-primary" />
                            <span>Projeto da Equipe para o Hackathon</span>
                        </h5>
                    </div>

                    <div class="card-body p-4">
                        <div v-if="!project" class="text-center py-5 text-muted">
                            <font-awesome-icon :icon="['fas', 'laptop-code']" class="fs-1 opacity-50 mb-3 d-block text-primary" />
                            <h6 class="fw-bold text-dark">Nenhum projeto submetido ainda</h6>
                            <p class="small text-secondary mb-3">
                                Quando a fase de submissão do hackathon estiver aberta, a equipe poderá enviar o repositório, link de demonstração e apresentação.
                            </p>
                        </div>
                        <div v-else>
                            <h5 class="fw-bold text-dark mb-2">{{ project.title }}</h5>
                            <p class="text-secondary mb-3">{{ project.description || 'Sem descrição cadastrada.' }}</p>

                            <div v-if="project.repository_url" class="mb-2">
                                <strong class="small text-dark">Repositório:</strong>
                                <a :href="project.repository_url" target="_blank" class="small ms-1 text-primary text-decoration-none">
                                    {{ project.repository_url }}
                                </a>
                            </div>

                            <div v-if="project.demo_url" class="mb-2">
                                <strong class="small text-dark">Demonstração:</strong>
                                <a :href="project.demo_url" target="_blank" class="small ms-1 text-primary text-decoration-none">
                                    {{ project.demo_url }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA 3: CONVITES -->
            <div v-if="activeTab === 'invites'">
                <div class="row g-4">
                    <div v-if="canManage || isLeader" class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                            <div class="card-header bg-transparent py-3 border-bottom">
                                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                                    <font-awesome-icon :icon="['fas', 'user-plus']" class="text-success" />
                                    <span>Convidar para a Equipe</span>
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <form @submit.prevent="submitInvite">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-dark">E-mail do Participante</label>
                                        <input
                                            v-model="inviteForm.email_or_username"
                                            type="email"
                                            class="form-control"
                                            placeholder="participante@email.com"
                                            required
                                        />
                                        <div class="form-text small mt-1">
                                            O participante deve estar inscrito no hackathon ou receberá o convite para integrar sua equipe.
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2" :disabled="inviteForm.processing">
                                        Enviar Convite
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div :class="(canManage || isLeader) ? 'col-lg-7' : 'col-12'">
                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 fw-bold text-dark">Convites da Equipe</h5>
                                <span class="badge bg-light text-secondary border">
                                    {{ team.invitations ? team.invitations.length : 0 }} convite(s)
                                </span>
                            </div>

                            <div class="card-body p-0">
                                <div v-if="!team.invitations || team.invitations.length === 0" class="text-muted text-center py-5 small">
                                    Nenhum convite emitido para esta equipe.
                                </div>
                                <ul v-else class="list-group list-group-flush">
                                    <li
                                        v-for="inv in team.invitations"
                                        :key="inv.id"
                                        class="list-group-item p-3 d-flex justify-content-between align-items-center"
                                    >
                                        <div>
                                            <strong class="text-dark small d-block">{{ inv.email || inv.invitee?.email }}</strong>
                                            <small class="text-muted">{{ formatDate(inv.created_at) }}</small>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge" :class="{
                                                'bg-warning text-dark': inv.status === 'pending',
                                                'bg-success': inv.status === 'accepted',
                                                'bg-secondary': inv.status === 'declined' || inv.status === 'cancelled'
                                            }">
                                                {{ inv.status === 'pending' ? 'Pendente' : (inv.status === 'accepted' ? 'Aceito' : 'Cancelado') }}
                                            </span>
                                            <div v-if="inv.status === 'pending' && canManage" class="d-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2 small" @click="handleResendInvite(inv.id)">Reenviar</button>
                                                <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 small" @click="handleCancelInvite(inv.id)">Cancelar</button>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Definição de Liderança -->
        <ModalDialog
            :is-visible="showElectLeaderModal"
            title="Transferir Liderança da Equipe"
            size="md"
            @close="showElectLeaderModal = false"
        >
            <form @submit.prevent="submitElectLeader">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Selecione o novo líder:</label>
                    <select v-model="leaderForm.leader_id" class="form-select" required>
                        <option value="" disabled>Selecione um integrante...</option>
                        <option v-for="member in team.members" :key="member.id" :value="member.id">
                            {{ member.name }} ({{ member.email }})
                        </option>
                    </select>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showElectLeaderModal = false">Cancelar</button>
                    <button type="submit" class="btn btn-warning" :disabled="leaderForm.processing">Confirmar Liderança</button>
                </div>
            </form>
        </ModalDialog>

        <!-- Modal de Confirmação Unificado -->
        <ModalConfirmation
            :is-visible="confirmationState.isVisible"
            :message="confirmationState.message"
            :sub-message="confirmationState.subMessage"
            :confirm-text="confirmationState.confirmText"
            :cancel-text="confirmationState.cancelText"
            @confirm="confirmationState.onConfirm(); confirmationState.isVisible = false"
            @close="confirmationState.isVisible = false"
        />
    </div>
</template>
