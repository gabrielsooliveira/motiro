<script setup>
import { computed } from "vue";

const props = defineProps({
    invitations: {
        type: Array,
        default: () => [],
    },
    currentUser: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["accept", "decline"]);

const getInitials = (name) => {
    if (!name) return "G";
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
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

const isExpiringSoon = (expiresAt) => {
    if (!expiresAt) return false;
    const diffHours = (new Date(expiresAt).getTime() - Date.now()) / (1000 * 60 * 60);
    return diffHours > 0 && diffHours < 48;
};
</script>

<template>
    <div>
        <!-- Banner Informativo quando há convites -->
        <div v-if="invitations.length > 0" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-primary text-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white text-primary p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 50px; height: 50px;">
                        <font-awesome-icon :icon="['fas', 'envelope-open-text']" class="fs-4" />
                    </div>
                    <div>
                        <h4 class="h5 fw-bold mb-1">
                            Você tem {{ invitations.length }} convite(s) de guild pendente(s)
                        </h4>
                        <p class="mb-0 small text-white-50">
                            Ao aceitar um convite, você passa a integrar a guild e pode colaborar em projetos e novos hackathons.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado Vazio quando NÃO há convites -->
        <div v-if="invitations.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-3">
            <div class="mb-3 text-muted">
                <div class="rounded-circle bg-light d-inline-flex p-4 text-muted bg-opacity-25">
                    <font-awesome-icon :icon="['fas', 'envelope-open']" class="fs-1 text-muted" />
                </div>
            </div>
            <h4 class="fw-bold text-dark mb-2">Nenhum convite pendente</h4>
            <p class="text-muted small mx-auto mb-3 col-md-7">
                Você não possui nenhum convite em aberto no momento. Quando líderes ou colegas adicionarem seu e-mail para uma guild, as notificações e solicitações aparecerão aqui.
            </p>
            <div class="alert alert-light border d-inline-block small py-2 px-3 text-secondary mb-0">
                <font-awesome-icon :icon="['fas', 'lightbulb']" class="text-warning me-1" />
                <strong>Dica:</strong> Compartilhe seu e-mail cadastrado <code>{{ currentUser?.email || 'seu e-mail' }}</code> com seus colegas para receber convites diretos.
            </div>
        </div>

        <!-- Grid de Convites Recebidos -->
        <div v-else class="row g-3">
            <div
                v-for="invite in invitations"
                :key="invite.id"
                class="col-12 col-lg-6"
            >
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 d-flex flex-column justify-content-between overflow-hidden">
                    <div class="p-4">
                        <!-- Topo com Avatar e Dados da Guild -->
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div
                                v-if="invite.team?.logo || invite.team?.image_url"
                                class="rounded-3 overflow-hidden border bg-light flex-shrink-0"
                                style="width: 56px; height: 56px;"
                            >
                                <img
                                    :src="invite.team.logo || invite.team.image_url"
                                    :alt="invite.team.name"
                                    class="w-100 h-100 object-fit-cover"
                                />
                            </div>
                            <div
                                v-else
                                class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-5"
                                style="width: 56px; height: 56px;"
                            >
                                {{ getInitials(invite.team?.name) }}
                            </div>

                            <div class="overflow-hidden flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <h4 class="h6 fw-bold text-dark mb-0 text-truncate">
                                        {{ invite.team ? invite.team.name : 'Guild' }}
                                    </h4>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                                        Aguardando sua resposta
                                    </span>
                                </div>
                                <span class="text-muted small d-block mt-1">
                                    Convidado por: <strong>{{ invite.inviter ? invite.inviter.name : 'Líder da guild' }}</strong>
                                    <span v-if="invite.inviter?.email" class="text-muted">({{ invite.inviter.email }})</span>
                                </span>
                            </div>
                        </div>

                        <!-- Descrição da Guild convidante -->
                        <p v-if="invite.team?.description" class="text-secondary small mb-3">
                            {{ invite.team.description }}
                        </p>

                        <!-- Informações de Data e Validade -->
                        <div class="p-3 bg-light rounded-3 d-flex flex-column gap-1 small">
                            <div class="d-flex justify-content-between text-muted">
                                <span><font-awesome-icon :icon="['fas', 'calendar-plus']" class="me-1" /> Enviado em:</span>
                                <span>{{ formatDate(invite.created_at) }}</span>
                            </div>
                            <div v-if="invite.expires_at" class="d-flex justify-content-between" :class="isExpiringSoon(invite.expires_at) ? 'text-danger fw-bold' : 'text-muted'">
                                <span><font-awesome-icon :icon="['fas', 'clock']" class="me-1" /> Válido até:</span>
                                <span>{{ formatDate(invite.expires_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Rodapé com Botões de Ação -->
                    <div class="px-4 py-3 bg-light bg-opacity-50 border-top mt-auto d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-danger btn-sm px-3 rounded-pill d-inline-flex align-items-center gap-2"
                            @click="emit('decline', invite.id)"
                        >
                            <font-awesome-icon :icon="['fas', 'xmark']" />
                            <span>Recusar Convite</span>
                        </button>

                        <button
                            type="button"
                            class="btn btn-success btn-sm px-4 rounded-pill d-inline-flex align-items-center gap-2 shadow-sm text-white"
                            @click="emit('accept', invite.id)"
                        >
                            <font-awesome-icon :icon="['fas', 'check']" />
                            <span>Aceitar Convite</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
