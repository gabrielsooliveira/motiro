<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    guilds: {
        type: Array,
        default: () => [],
    },
    teams: {
        type: Array,
        default: () => [],
    },
    currentUser: {
        type: Object,
        default: () => ({}),
    },
});

const rawGuilds = computed(() => {
    return props.guilds && props.guilds.length > 0 ? props.guilds : props.teams;
});

const searchQuery = ref("");
const selectedTeamId = ref("all");

const getGuildShowUrl = (guildId) => {
    return route().has("participant.guilds.show")
        ? route("participant.guilds.show", guildId)
        : route("participant.teams.show", guildId);
};

const getInitials = (name) => {
    if (!name) return "U";
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
};

const formatDate = (dateString) => {
    if (!dateString) return "Recente";
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

// Extrai e consolida membros de todas as guildas do usuário
const consolidatedMembers = computed(() => {
    const map = new Map();

    rawGuilds.value.forEach((team) => {
        if (!team.members || !Array.isArray(team.members)) return;

        team.members.forEach((member) => {
            const key = member.id;
            if (!map.has(key)) {
                map.set(key, {
                    id: member.id,
                    name: member.name,
                    email: member.email,
                    username: member.username,
                    isCurrentUser: member.id === props.currentUser?.id,
                    guilds: [],
                });
            }

            const isLeader = team.leader_id === member.id;
            const isCreator = team.creator_id === member.id;
            const role = isCreator ? "Criador" : (isLeader ? "Líder" : "Membro");

            map.get(key).guilds.push({
                teamId: team.id,
                teamName: team.name,
                role: role,
                joinedAt: member.pivot?.joined_at,
            });
        });
    });

    return Array.from(map.values());
});

const filteredMembers = computed(() => {
    let list = consolidatedMembers.value;

    // Filtro por guilda específica
    if (selectedTeamId.value !== "all") {
        const tId = selectedTeamId.value;
        list = list.filter((m) => m.guilds.some((g) => String(g.teamId) === String(tId)));
    }

    // Busca textual
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (m) =>
                m.name?.toLowerCase().includes(q) ||
                m.email?.toLowerCase().includes(q) ||
                m.username?.toLowerCase().includes(q)
        );
    }

    return list;
});
</script>

<template>
    <div>
        <!-- Barra de Controle: Busca e Filtro por Guilda -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <font-awesome-icon :icon="['fas', 'magnifying-glass']" />
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control border-start-0 bg-light"
                            placeholder="Buscar membro por nome ou e-mail..."
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="btn btn-light border border-start-0 text-muted"
                            @click="searchQuery = ''"
                        >
                            <font-awesome-icon :icon="['fas', 'xmark']" />
                        </button>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <select v-model="selectedTeamId" class="form-select bg-light border">
                        <option value="all">Todas as Guildas ({{ rawGuilds.length }})</option>
                        <option v-for="team in rawGuilds" :key="team.id" :value="team.id">
                            {{ team.name }} ({{ team.members?.length || 0 }} membros)
                        </option>
                    </select>
                </div>

                <div class="col-12 col-lg-3 text-lg-end text-muted small">
                    <strong>{{ filteredMembers.length }}</strong> integrante(s) encontrado(s)
                </div>
            </div>
        </div>

        <!-- Estado Vazio -->
        <div v-if="filteredMembers.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-3">
            <div class="mb-3 text-muted">
                <div class="rounded-circle bg-light d-inline-flex p-4 text-muted bg-opacity-25">
                    <font-awesome-icon :icon="['fas', 'users-slash']" class="fs-1 text-muted" />
                </div>
            </div>
            <h4 class="fw-bold text-dark mb-2">Nenhum membro encontrado</h4>
            <p class="text-muted small mx-auto mb-3 col-md-7">
                {{ searchQuery || selectedTeamId !== 'all' ? 'Tente ajustar os filtros ou os termos pesquisados.' : 'Suas guildas ainda não possuem outros integrantes além de você. Convide colegas pela tela de gerenciamento de guilda.' }}
            </p>
            <button
                v-if="searchQuery || selectedTeamId !== 'all'"
                type="button"
                class="btn btn-outline-secondary btn-sm"
                @click="searchQuery = ''; selectedTeamId = 'all'"
            >
                Limpar Filtros
            </button>
        </div>

        <!-- Grid Responsivo de Membros -->
        <div v-else class="row g-3">
            <div
                v-for="member in filteredMembers"
                :key="member.id"
                class="col-12 col-md-6 col-lg-4"
            >
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-column justify-content-between">
                    <div>
                        <!-- Cabeçalho do Membro com Avatar e Status -->
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div
                                class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-5 text-white"
                                :class="member.isCurrentUser ? 'bg-primary' : 'bg-secondary'"
                                style="width: 52px; height: 52px;"
                            >
                                {{ getInitials(member.name) }}
                            </div>
                            <div class="overflow-hidden flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <h5 class="h6 fw-bold text-dark mb-0 text-truncate" :title="member.name">
                                        {{ member.name }}
                                    </h5>
                                    <span v-if="member.isCurrentUser" class="badge bg-primary text-white small">Você</span>
                                </div>
                                <span class="text-muted small text-truncate d-block" :title="member.email">
                                    {{ member.email }}
                                </span>
                            </div>
                        </div>

                        <!-- Guildas Compartilhadas e Papéis -->
                        <div class="border rounded-3 p-3 bg-light bg-opacity-50 mb-3">
                            <span class="small fw-bold text-dark d-block mb-2">
                                <font-awesome-icon :icon="['fas', 'shield-halved']" class="me-1 text-muted" /> Guildas em comum ({{ member.guilds.length }}):
                            </span>
                            <div class="d-flex flex-column gap-2">
                                <div
                                    v-for="(g, idx) in member.guilds"
                                    :key="idx"
                                    class="d-flex justify-content-between align-items-center small p-2 bg-white rounded-2 border"
                                >
                                    <span class="fw-semibold text-truncate me-2" style="max-width: 140px;" :title="g.teamName">
                                        {{ g.teamName }}
                                    </span>
                                    <span
                                        class="badge"
                                        :class="{
                                            'bg-dark text-white': g.role === 'Criador',
                                            'bg-warning text-dark': g.role === 'Líder',
                                            'bg-light text-secondary border': g.role === 'Membro',
                                        }"
                                    >
                                        {{ g.role }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rodapé com Link Rápido para uma das guildas -->
                    <div class="pt-2 border-top mt-auto d-flex justify-content-between align-items-center small text-muted">
                        <span>Ingressou em {{ formatDate(member.guilds[0]?.joinedAt) }}</span>
                        <Link
                            :href="getGuildShowUrl(member.guilds[0]?.teamId)"
                            class="text-primary text-decoration-none fw-semibold"
                        >
                            Ver Guilda &rarr;
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
