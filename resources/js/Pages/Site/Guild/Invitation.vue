<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import ModalConfirmation from "@/js/Components/Modals/ModalConfirmation.vue";

const props = defineProps({
    invitation: {
        type: Object,
        default: null,
    },
    guild: {
        type: Object,
        default: null,
    },
    inviter: {
        type: Object,
        default: null,
    },
    isExpired: {
        type: Boolean,
        default: false,
    },
    isPending: {
        type: Boolean,
        default: false,
    },
    userEmailMatches: {
        type: Boolean,
        default: false,
    },
    currentUser: {
        type: Object,
        default: null,
    },
    error: {
        type: String,
        default: null,
    },
    status: {
        type: String,
        default: null,
    },
});

const isProcessing = ref(false);

const handleAccept = () => {
    if (!props.invitation?.token) return;
    isProcessing.value = true;
    router.post(route("guild.invitations.accept", props.invitation.token), {}, {
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

const showDeclineConfirm = ref(false);

const handleDecline = () => {
    if (!props.invitation?.token) return;
    showDeclineConfirm.value = true;
};

const executeDecline = () => {
    if (!props.invitation?.token) return;
    showDeclineConfirm.value = false;
    isProcessing.value = true;
    router.post(route("guild.invitations.decline", props.invitation.token), {}, {
        onFinish: () => {
            isProcessing.value = false;
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
        <title>Convite para Guild | Motirõ</title>
    </Head>

    <div class="container py-5 my-md-4">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <!-- Estado: Não Encontrado ou Link Inválido -->
                <div v-if="!invitation || error || status === 'not_found'" class="card border-0 shadow rounded-4 p-4 text-center bg-white">
                    <div class="mb-3 text-warning">
                        <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="fs-1" />
                    </div>
                    <h3 class="fw-bold text-dark mb-2">Convite Inválido ou Inexistente</h3>
                    <p class="text-muted small mb-4">
                        {{ error || 'O link do convite informado não é válido, foi revogado ou não foi localizado em nossa base de dados.' }}
                    </p>
                    <div>
                        <Link :href="route('home')" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <font-awesome-icon :icon="['fas', 'house']" />
                            <span>Página Inicial</span>
                        </Link>
                    </div>
                </div>

                <!-- Estado: Convite Expirado -->
                <div v-else-if="isExpired" class="card border-0 shadow rounded-4 p-4 text-center bg-white">
                    <div class="mb-3 text-danger">
                        <font-awesome-icon :icon="['fas', 'clock']" class="fs-1" />
                    </div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle mx-auto px-3 py-2 rounded-pill small mb-3">
                        Convite Expirado
                    </span>
                    <h3 class="fw-bold text-dark mb-2">Prazo de Resposta Esgotado</h3>
                    <p class="text-muted small mb-4">
                        O prazo para responder ao convite da guild <strong>{{ guild?.name }}</strong> expirou em {{ formatDate(invitation.expires_at) }}. Solicite ao líder da guild o reenvio de um novo convite.
                    </p>
                    <div class="d-flex justify-content-center gap-2">
                        <Link :href="route('participant.dashboard')" class="btn btn-outline-secondary">
                            Meu Painel
                        </Link>
                    </div>
                </div>

                <!-- Estado: Já Processado (Aceito / Recusado / Cancelado) -->
                <div v-else-if="!isPending" class="card border-0 shadow rounded-4 p-4 text-center bg-white">
                    <div class="mb-3" :class="invitation.status === 'accepted' ? 'text-success' : 'text-secondary'">
                        <font-awesome-icon :icon="invitation.status === 'accepted' ? ['fas', 'circle-check'] : ['fas', 'circle-xmark']" class="fs-1" />
                    </div>
                    <h3 class="fw-bold text-dark mb-2">
                        {{ invitation.status === 'accepted' ? 'Convite Já Aceito' : 'Convite Não Disponível' }}
                    </h3>
                    <p class="text-muted small mb-4">
                        {{ invitation.status === 'accepted'
                            ? 'Você já aceitou este convite e faz parte desta guild.'
                            : 'Este convite já foi recusado ou cancelado pelo responsável.' }}
                    </p>
                    <div>
                        <Link :href="route('participant.guilds.index')" class="btn btn-primary">
                            Ir para Minhas Guilds
                        </Link>
                    </div>
                </div>

                <!-- Estado: Convite Pendente e Ativo -->
                <div v-else class="card border-0 shadow rounded-4 overflow-hidden bg-white">
                    <div class="bg-primary text-white p-4 text-center">
                        <div class="badge bg-white bg-opacity-25 text-white mb-2 px-3 py-1 rounded-pill">
                            <font-awesome-icon :icon="['fas', 'users-rectangle']" class="me-1" />
                            Convite para Guild
                        </div>
                        <h2 class="h3 fw-bold mb-1">Você foi convidado!</h2>
                        <p class="small text-white-50 mb-0">
                            Participe da guild permanente no Motirõ
                        </p>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <!-- Card da Guild -->
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4 mb-4 border">
                            <div v-if="guild?.image_url" class="rounded-3 overflow-hidden border bg-white flex-shrink-0" style="width: 64px; height: 64px;">
                                <img :src="guild.image_url" :alt="guild.name" class="w-100 h-100 object-fit-cover" />
                            </div>
                            <div v-else class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-4" style="width: 64px; height: 64px;">
                                {{ guild?.name?.charAt(0)?.toUpperCase() || 'G' }}
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h4 class="h5 fw-bold text-dark mb-0 text-truncate">{{ guild?.name }}</h4>
                                </div>
                                <div class="small text-muted d-flex align-items-center gap-3">
                                    <span>
                                        <font-awesome-icon :icon="['fas', 'users']" class="me-1" />
                                        {{ guild?.members_count || 1 }} integrante(s)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div class="mb-4">
                            <h6 class="fw-bold text-dark small text-uppercase mb-2">Sobre a guild</h6>
                            <p class="text-secondary small mb-0">
                                {{ guild?.description || 'Esta guild reúne participantes para desenvolvimento contínuo de projetos e formação de times em hackathons.' }}
                            </p>
                        </div>

                        <!-- Detalhes do Convidante e Validade -->
                        <div class="border-top border-bottom py-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-muted">Convidado por:</span>
                                <strong class="text-dark">{{ inviter?.name || 'Líder da Guild' }}</strong>
                            </div>
                            <div v-if="invitation.expires_at" class="d-flex justify-content-between align-items-center small">
                                <span class="text-muted">Expira em:</span>
                                <span class="text-dark fw-semibold">{{ formatDate(invitation.expires_at) }}</span>
                            </div>
                        </div>

                        <!-- Usuário NÃO Autenticado -->
                        <div v-if="!currentUser" class="text-center">
                            <div class="alert alert-info py-2 small mb-3 text-start">
                                <font-awesome-icon :icon="['fas', 'circle-info']" class="me-1" />
                                Faça login ou crie sua conta no Motirõ com o e-mail <strong>{{ invitation.email }}</strong> para responder a este convite.
                            </div>
                            <div class="d-grid gap-2">
                                <Link
                                    :href="route('participant.login')"
                                    class="btn btn-primary py-2 fw-semibold"
                                >
                                    Entrar na Minha Conta
                                </Link>
                                <Link
                                    :href="route('participant.register')"
                                    class="btn btn-outline-secondary py-2"
                                >
                                    Criar Nova Conta no Motirõ
                                </Link>
                            </div>
                        </div>

                        <!-- Usuário Autenticado -->
                        <div v-else>
                            <!-- Alerta se o e-mail não bater -->
                            <div v-if="!userEmailMatches" class="alert alert-warning py-2 small mb-3">
                                <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                                Você está conectado como <strong>{{ currentUser.email }}</strong>, mas este convite foi enviado para <strong>{{ invitation.email }}</strong>.
                            </div>

                            <div class="d-flex flex-column gap-2">
                                <button
                                    type="button"
                                    class="btn btn-success py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm"
                                    :disabled="isProcessing"
                                    @click="handleAccept"
                                >
                                    <font-awesome-icon :icon="['fas', 'check']" />
                                    <span>Aceitar Convite e Entrar na Guild</span>
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger py-2"
                                    :disabled="isProcessing"
                                    @click="handleDecline"
                                >
                                    Recusar Convite
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ModalConfirmation
            :is-visible="showDeclineConfirm"
            message="Recusar Convite da Guild"
            sub-message="Tem certeza de que deseja recusar este convite? Esta ação cancelará o acesso."
            confirm-text="Sim, Recusar"
            cancel-text="Voltar"
            @confirm="executeDecline"
            @close="showDeclineConfirm = false"
        />
    </div>
</template>
