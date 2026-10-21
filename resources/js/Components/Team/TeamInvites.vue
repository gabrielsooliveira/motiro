<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
    invitations: {
        type: Array,
        default: () => [],
    },
});

const handleAccept = (id) => {
    const targetRoute = route().has("participant.teams.invite.accept")
        ? route("participant.teams.invite.accept", id)
        : (route().has("participant.guilds.invite.accept") ? route("participant.guilds.invite.accept", id) : null);
    if (targetRoute) {
        router.post(targetRoute);
    }
};

const handleDecline = (id) => {
    const targetRoute = route().has("participant.teams.invite.decline")
        ? route("participant.teams.invite.decline", id)
        : (route().has("participant.guilds.invite.decline") ? route("participant.guilds.invite.decline", id) : null);
    if (targetRoute) {
        router.post(targetRoute);
    }
};

const formatDate = (isoString) => {
    if (!isoString) return "";
    const date = new Date(isoString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
};
</script>

<template>
    <div>
        <div v-if="!invitations || invitations.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <font-awesome-icon :icon="['fas', 'envelope-open']" class="fs-1 text-muted opacity-50 mb-3" />
            <h5 class="fw-bold text-dark">Nenhum convite pendente</h5>
            <p class="text-secondary small mb-0">Você não possui convites pendentes para equipes no momento.</p>
        </div>

        <div v-else class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold d-flex align-items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'envelope']" class="text-primary" />
                    <span>Convites Recebidos ({{ invitations.length }})</span>
                </h5>
            </div>

            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li
                        v-for="inv in invitations"
                        :key="inv.id"
                        class="list-group-item p-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3"
                    >
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="mb-0 fw-bold text-dark">{{ inv.team?.name || 'Equipe' }}</h6>
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">Pendente</span>
                            </div>
                            <p class="text-secondary small mb-1">
                                Convidado por: <strong>{{ inv.inviter?.name || 'Líder da Equipe' }}</strong>
                            </p>
                            <small v-if="inv.expires_at" class="text-muted">
                                Expira em: {{ formatDate(inv.expires_at) }}
                            </small>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <button
                                type="button"
                                class="btn btn-success btn-sm rounded-pill px-3 shadow-sm"
                                @click="handleAccept(inv.id)"
                            >
                                <font-awesome-icon :icon="['fas', 'check']" class="me-1" />
                                <span>Aceitar Convite</span>
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline-danger btn-sm rounded-pill px-3"
                                @click="handleDecline(inv.id)"
                            >
                                <font-awesome-icon :icon="['fas', 'xmark']" class="me-1" />
                                <span>Recusar</span>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
