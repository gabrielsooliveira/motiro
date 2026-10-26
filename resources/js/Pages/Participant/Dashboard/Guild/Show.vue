<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import ModalDialog from "@/js/Components/Modals/ModalDialog.vue";
import ModalConfirmation from "@/js/Components/Modals/ModalConfirmation.vue";

const props = defineProps({
    team: {
        type: Object,
        required: true,
    },
    guild: {
        type: Object,
        default: () => ({}),
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

const currentGuild = props.guild && Object.keys(props.guild).length > 0 ? props.guild : props.team;

const activeTab = ref("members");
const showEditModal = ref(false);
const showElectLeaderModal = ref(false);
const imagePreview = ref(null);

const guildRoute = (action, param = null) => {
    const guildAction = `participant.guilds.${action}`;
    const teamAction = `participant.teams.${action}`;
    const name = route().has(guildAction) ? guildAction : teamAction;
    return param !== null ? route(name, param) : route(name);
};

// Formulário de Convite por E-mail
const inviteForm = useForm({
    email_or_username: "",
});

const submitInvite = () => {
    inviteForm.post(guildRoute("invite", currentGuild.id), {
        onSuccess: () => inviteForm.reset(),
    });
};

// Formulário de Edição da Guild
const editForm = useForm({
    name: currentGuild.name,
    description: currentGuild.description || "",
    image: null,
    remove_image: false,
});

const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (file) {
        editForm.image = file;
        editForm.remove_image = false;
        const reader = new FileReader();
        reader.onload = (event) => {
            imagePreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const markRemoveImage = () => {
    editForm.image = null;
    editForm.remove_image = true;
    imagePreview.value = null;
};

const submitEdit = () => {
    editForm.post(guildRoute("update", currentGuild.id), {
        forceFormData: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

// Formulário de Liderança
const leaderForm = useForm({
    leader_id: currentGuild.leader_id || "",
});

const submitElectLeader = () => {
    leaderForm.post(guildRoute("leader", currentGuild.id), {
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
        subMessage: "Deseja transferir a liderança da guild para este integrante?",
        confirmText: "Transferir",
        onConfirm: () => {
            router.post(
                guildRoute("leader", currentGuild.id),
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
        subMessage: `Tem certeza de que deseja remover ${member.name} da guild?`,
        confirmText: "Remover",
        onConfirm: () => {
            router.delete(guildRoute("members.remove", { id: currentGuild.id, userId: member.id }));
        },
    });
};

const handleLeaveGuild = () => {
    openConfirmation({
        message: "Sair da Guild",
        subMessage: "Tem certeza de que deseja sair desta guild?",
        confirmText: "Sair",
        onConfirm: () => {
            router.post(guildRoute("leave", currentGuild.id));
        },
    });
};

const handleDeleteGuild = () => {
    openConfirmation({
        message: "Excluir Guild",
        subMessage: "ATENÇÃO: Deseja realmente excluir esta guild? Esta ação é irreversível.",
        confirmText: "Sim, Excluir",
        onConfirm: () => {
            router.delete(guildRoute("destroy", currentGuild.id));
        },
    });
};

// Ações de Convites
const handleResendInvite = (inviteId) => {
    router.post(guildRoute("invite.resend", inviteId));
};

const handleCancelInvite = (inviteId) => {
    openConfirmation({
        message: "Cancelar Convite",
        subMessage: "Deseja realmente cancelar este convite?",
        confirmText: "Cancelar Convite",
        onConfirm: () => {
            router.delete(guildRoute("invite.cancel", inviteId));
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
        <title>Guild | {{ currentGuild.name }}</title>
    </Head>

    <div class="py-3">
        <!-- Navegação de Retorno -->
        <div class="mb-3">
            <Link
                :href="guildRoute('index')"
                class="text-decoration-none text-muted small d-inline-flex align-items-center gap-2"
            >
                <font-awesome-icon :icon="['fas', 'arrow-left']" />
                <span>Voltar para Minhas Guilds</span>
            </Link>
        </div>

        <!-- Cabeçalho Limpo e Minimalista da Guild -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <!-- Imagem / Logo da Guild -->
                    <div
                        v-if="currentGuild.logo || currentGuild.image_url"
                        class="rounded-4 overflow-hidden border bg-light flex-shrink-0 guild-avatar"
                    >
                        <img :src="currentGuild.logo || currentGuild.image_url" :alt="currentGuild.name" class="w-100 h-100 object-fit-cover" />
                    </div>
                    <div
                        v-else
                        class="rounded-4 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-2 guild-avatar"
                    >
                        {{ currentGuild.name?.charAt(0)?.toUpperCase() || 'G' }}
                    </div>

                    <div>
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <h1 class="h3 fw-bold text-dark mb-0">{{ currentGuild.name }}</h1>
                            <span v-if="isCreator" class="badge bg-dark text-white">
                                <font-awesome-icon :icon="['fas', 'shield']" class="me-1 small" /> Criador
                            </span>
                            <span v-if="isLeader" class="badge bg-warning text-dark">
                                <font-awesome-icon :icon="['fas', 'crown']" class="me-1 small" /> Líder
                            </span>
                            <span v-else-if="!currentGuild.leader_id" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                Líder em aberto
                            </span>
                            <span v-else class="badge bg-light text-secondary border">
                                Integrante
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação por Abas Responsiva da Guild -->
        <ul class="nav nav-pills gap-2 border-bottom pb-3 mb-4 flex-nowrap overflow-x-auto" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'members' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="activeTab = 'members'"
                >
                    <font-awesome-icon :icon="['fas', 'users']" />
                    <span>Integrantes</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'members' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ currentGuild.members ? currentGuild.members.length : 0 }}
                    </span>
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
                        {{ currentGuild.invitations ? currentGuild.invitations.length : 0 }}
                    </span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'hackathons' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="activeTab = 'hackathons'"
                >
                    <font-awesome-icon :icon="['fas', 'trophy']" />
                    <span>Hackathons</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'hackathons' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ currentGuild.registrations ? currentGuild.registrations.length : 0 }}
                    </span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'overview' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="activeTab = 'overview'"
                >
                    <font-awesome-icon :icon="['fas', 'circle-info']" />
                    <span>Informações da Guild</span>
                </button>
            </li>
        </ul>

        <!-- Viewport do Conteúdo das Abas da Guild -->
        <div class="tab-content-viewport pb-5">
            <!-- ABA 1: INTEGRANTES -->
            <div v-if="activeTab === 'members'">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                                <font-awesome-icon :icon="['fas', 'users']" class="text-primary" />
                                <span>Membros da guild ({{ currentGuild.members ? currentGuild.members.length : 0 }})</span>
                            </h5>
                        </div>
                        <button
                            v-if="canManage"
                            type="button"
                            class="btn btn-outline-primary btn-sm rounded-pill px-3"
                            @click="activeTab = 'invites'"
                        >
                            <font-awesome-icon :icon="['fas', 'user-plus']" class="me-1" />
                            <span>Convidar Membro</span>
                        </button>
                    </div>

                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li
                                v-for="member in currentGuild.members"
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
                                    <span v-if="currentGuild.creator_id === member.id" class="badge bg-dark text-white">
                                        <font-awesome-icon :icon="['fas', 'shield']" class="me-1 small" /> Criador
                                    </span>
                                    <span v-if="member.id === currentGuild.leader_id" class="badge bg-warning text-dark">
                                        <font-awesome-icon :icon="['fas', 'crown']" class="me-1 small" /> Líder
                                    </span>
                                    <span v-else class="badge bg-light text-secondary border">
                                        Integrante
                                    </span>

                                    <div v-if="canManage && member.id !== currentUserId" class="d-flex gap-1 ms-2">
                                        <button
                                            v-if="member.id !== currentGuild.leader_id"
                                            type="button"
                                            class="btn btn-outline-warning btn-sm py-1 px-2 small"
                                            title="Tornar Líder da Guild"
                                            @click="transferLeadershipTo(member.id)"
                                        >
                                            <font-awesome-icon :icon="['fas', 'crown']" class="me-1" />
                                            <span>Tornar Líder</span>
                                        </button>
                                        <button
                                            v-if="currentGuild.creator_id !== member.id"
                                            type="button"
                                            class="btn btn-outline-danger btn-sm py-1 px-2 small"
                                            title="Remover integrante da guild"
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

            <!-- ABA 2: CONVITES -->
            <div v-if="activeTab === 'invites'">
                <div class="row g-4">
                    <!-- Formulário de Envio de Convite -->
                    <div v-if="canManage || isLeader" class="col-lg-5">
                        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                            <div class="card-header bg-transparent py-3 border-bottom">
                                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                                    <font-awesome-icon :icon="['fas', 'user-plus']" class="text-success" />
                                    <span>Convidar por E-mail</span>
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <form @submit.prevent="submitInvite">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-dark">E-mail do Participante</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted">
                                                <font-awesome-icon :icon="['fas', 'envelope']" />
                                            </span>
                                            <input
                                                v-model="inviteForm.email_or_username"
                                                type="email"
                                                class="form-control border-start-0 bg-light"
                                                placeholder="participante@email.com"
                                                required
                                            />
                                        </div>
                                        <div class="form-text small mt-1">
                                            O participante receberá um e-mail com link exclusivo para aceitar o convite para esta guild.
                                        </div>
                                        <div v-if="inviteForm.errors.email_or_username" class="text-danger small mt-1">
                                            {{ inviteForm.errors.email_or_username }}
                                        </div>
                                    </div>
                                    <button
                                        type="submit"
                                        class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2 rounded-pill py-2 shadow-sm"
                                        :disabled="inviteForm.processing"
                                    >
                                        <font-awesome-icon :icon="['fas', 'paper-plane']" />
                                        <span>{{ inviteForm.processing ? 'Enviando...' : 'Enviar Convite por E-mail' }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Convites Enviados -->
                    <div :class="(canManage || isLeader) ? 'col-lg-7' : 'col-12'">
                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                                    <font-awesome-icon :icon="['fas', 'clock-rotate-left']" class="text-muted" />
                                    <span>Histórico de Convites</span>
                                </h5>
                                <span class="badge bg-light text-secondary border">
                                    {{ currentGuild.invitations ? currentGuild.invitations.length : 0 }} registro(s)
                                </span>
                            </div>

                            <div class="card-body p-0">
                                <div v-if="!currentGuild.invitations || currentGuild.invitations.length === 0" class="text-muted text-center py-5 small">
                                    <font-awesome-icon :icon="['fas', 'envelope-open']" class="fs-2 text-muted opacity-50 mb-2 d-block" />
                                    Nenhum convite emitido para esta guild até o momento.
                                </div>

                                <ul v-else class="list-group list-group-flush">
                                    <li
                                        v-for="inv in currentGuild.invitations"
                                        :key="inv.id"
                                        class="list-group-item p-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2"
                                    >
                                        <div>
                                            <strong class="text-dark small d-block">{{ inv.email || inv.invitee?.email }}</strong>
                                            <span v-if="inv.expires_at" class="text-muted small">
                                                Expira em: {{ formatDate(inv.expires_at) }}
                                            </span>
                                            <span v-else class="text-muted small">
                                                Enviado em: {{ formatDate(inv.created_at) }}
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge" :class="{
                                                'bg-warning text-dark': inv.status === 'pending',
                                                'bg-success': inv.status === 'accepted',
                                                'bg-secondary': inv.status === 'declined' || inv.status === 'cancelled'
                                            }">
                                                {{ inv.status === 'pending' ? 'Pendente' : (inv.status === 'accepted' ? 'Aceito' : 'Recusado/Cancelado') }}
                                            </span>

                                            <div v-if="inv.status === 'pending' && canManage" class="d-flex gap-1 ms-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm py-1 px-2 small"
                                                    title="Reenviar e-mail de convite"
                                                    @click="handleResendInvite(inv.id)"
                                                >
                                                    Reenviar
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-danger btn-sm py-1 px-2 small"
                                                    title="Cancelar convite"
                                                    @click="handleCancelInvite(inv.id)"
                                                >
                                                    Cancelar
                                                </button>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA 3: HACKATHONS -->
            <div v-if="activeTab === 'hackathons'">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                                <font-awesome-icon :icon="['fas', 'trophy']" class="text-warning" />
                                <span>Participações em Hackathons ({{ currentGuild.registrations ? currentGuild.registrations.length : 0 }})</span>
                            </h5>
                            <span class="text-muted small">Eventos competitivos em que membros ou equipes desta guild participaram</span>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div v-if="!currentGuild.registrations || currentGuild.registrations.length === 0" class="text-center py-5 text-muted">
                            <font-awesome-icon :icon="['fas', 'trophy']" class="fs-1 text-warning opacity-50 mb-3 d-block" />
                            <h6 class="fw-bold text-dark">Nenhuma participação registrada</h6>
                            <p class="small text-secondary mb-3">
                                Esta guild ainda não possui registros de participações em hackathons. Os membros podem formar equipes para disputar desafios na plataforma.
                            </p>
                            <Link :href="route('participant.hackathon.index')" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                Explorar Hackathons
                            </Link>
                        </div>

                        <div v-else class="row g-3">
                            <div v-for="reg in currentGuild.registrations" :key="reg.id" class="col-12 col-md-6">
                                <div class="card border rounded-3 p-3 bg-light bg-opacity-25 h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-light text-secondary border small">
                                                {{ reg.hackathon?.organization?.name || 'Evento' }}
                                            </span>
                                            <span class="badge" :class="{
                                                'bg-success': reg.status === 'approved',
                                                'bg-warning text-dark': reg.status === 'pending',
                                                'bg-secondary': reg.status === 'cancelled'
                                            }">
                                                {{ reg.status === 'approved' ? 'Aprovado' : reg.status }}
                                            </span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-1">{{ reg.hackathon?.title }}</h6>
                                    </div>
                                    <div class="mt-3 text-end">
                                        <Link
                                            v-if="reg.hackathon?.slug"
                                            :href="route('hackathon.show', reg.hackathon.slug)"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                        >
                                            Ver Evento
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA 4: INFORMAÇÕES & GOVERNANÇA -->
            <div v-if="activeTab === 'overview'">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                                    <font-awesome-icon :icon="['fas', 'circle-info']" class="text-primary" />
                                    <span>Sobre a Guild</span>
                                </h5>
                                <button
                                    v-if="canManage"
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm rounded-pill px-3"
                                    @click="showEditModal = true"
                                >
                                    <font-awesome-icon :icon="['fas', 'pen-to-square']" class="me-1" />
                                    <span>Editar Dados</span>
                                </button>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold text-uppercase">Nome da Guild</label>
                                    <div class="fs-5 fw-bold text-dark">{{ currentGuild.name }}</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label text-muted small fw-semibold text-uppercase">Descrição / Manifesto</label>
                                    <p class="text-secondary mb-0">
                                        {{ currentGuild.description || 'Nenhuma descrição informada para esta guild.' }}
                                    </p>
                                </div>
                                <div class="row g-3 pt-3 border-top">
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small fw-semibold text-uppercase">Criador Original</label>
                                        <div class="fw-semibold text-dark">{{ currentGuild.creator?.name || 'Não identificado' }}</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small fw-semibold text-uppercase">Líder Atual</label>
                                        <div class="fw-semibold text-dark">{{ currentGuild.leader?.name || 'Liderança em aberto' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                            <div class="card-header bg-transparent py-3 border-bottom">
                                <h6 class="card-title mb-0 fw-bold text-dark">Ações da Guild</h6>
                            </div>
                            <div class="card-body p-4 d-flex flex-column gap-2">
                                <button
                                    v-if="canManage"
                                    type="button"
                                    class="btn btn-outline-warning w-100 d-inline-flex align-items-center justify-content-center gap-2 rounded-pill py-2"
                                    @click="showElectLeaderModal = true"
                                >
                                    <font-awesome-icon :icon="['fas', 'crown']" />
                                    <span>Transferir Liderança</span>
                                </button>

                                <button
                                    v-if="!isCreator"
                                    type="button"
                                    class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-2 rounded-pill py-2"
                                    @click="handleLeaveGuild"
                                >
                                    <font-awesome-icon :icon="['fas', 'right-from-bracket']" />
                                    <span>Sair da Guild</span>
                                </button>

                                <button
                                    v-if="isCreator"
                                    type="button"
                                    class="btn btn-outline-danger w-100 d-inline-flex align-items-center justify-content-center gap-2 rounded-pill py-2"
                                    @click="handleDeleteGuild"
                                >
                                    <font-awesome-icon :icon="['fas', 'trash']" />
                                    <span>Excluir Guild</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Edição da Guild -->
        <ModalDialog
            :is-visible="showEditModal"
            title="Editar Dados da Guild"
            size="md"
            @close="showEditModal = false"
        >
            <form @submit.prevent="submitEdit">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Nome da Guild</label>
                    <input v-model="editForm.name" type="text" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Descrição</label>
                    <textarea v-model="editForm.description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Logo / Imagem da Guild</label>
                    <input type="file" class="form-control" accept="image/*" @change="handleImageUpload" />
                    <div v-if="imagePreview" class="mt-2 text-center">
                        <img :src="imagePreview" alt="Prévia" class="rounded-3 border object-fit-cover" style="width: 80px; height: 80px;" />
                    </div>
                    <div v-if="currentGuild.logo && !editForm.remove_image" class="mt-2">
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 small" @click="markRemoveImage">
                            Remover Imagem Atual
                        </button>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showEditModal = false">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="editForm.processing">Salvar Alterações</button>
                </div>
            </form>
        </ModalDialog>

        <!-- Modal de Definição / Transferência de Liderança -->
        <ModalDialog
            :is-visible="showElectLeaderModal"
            title="Transferir Liderança"
            size="md"
            @close="showElectLeaderModal = false"
        >
            <form @submit.prevent="submitElectLeader">
                <p class="text-muted small mb-3">
                    Selecione um dos integrantes da guild para assumir o papel de líder:
                </p>
                <div class="mb-3">
                    <select v-model="leaderForm.leader_id" class="form-select" required>
                        <option value="" disabled>Selecione um integrante...</option>
                        <option v-for="member in currentGuild.members" :key="member.id" :value="member.id">
                            {{ member.name }} ({{ member.email }}) {{ member.id === currentGuild.leader_id ? '— [Atual Líder]' : '' }}
                        </option>
                    </select>
                </div>
                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showElectLeaderModal = false">Cancelar</button>
                    <button type="submit" class="btn btn-warning" :disabled="leaderForm.processing || !leaderForm.leader_id">
                        Confirmar Liderança
                    </button>
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
