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

const emit = defineEmits(["open-create-modal"]);

const rawGuilds = computed(() => {
    return props.guilds && props.guilds.length > 0 ? props.guilds : props.teams;
});

const searchQuery = ref("");
const roleFilter = ref("all"); // 'all' | 'leader' | 'creator' | 'member'
const sortBy = ref("recent"); // 'recent' | 'name' | 'members'
const viewMode = ref("grid"); // 'grid' | 'list'

const getInitials = (name) => {
    if (!name) return "G";
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const getGuildShowUrl = (guildId) => {
    return route().has("participant.guilds.show")
        ? route("participant.guilds.show", guildId)
        : route("participant.teams.show", guildId);
};

// Contagens para os filtros
const counts = computed(() => {
    const list = rawGuilds.value;
    const userId = props.currentUser?.id;
    return {
        all: list.length,
        leader: list.filter((t) => t.leader_id === userId).length,
        creator: list.filter((t) => t.creator_id === userId).length,
        member: list.filter((t) => t.leader_id !== userId && t.creator_id !== userId).length,
    };
});

// Filtragem e ordenação
const processedTeams = computed(() => {
    let list = [...rawGuilds.value];
    const userId = props.currentUser?.id;

    // Filtro por papel
    if (roleFilter.value === "leader") {
        list = list.filter((t) => t.leader_id === userId);
    } else if (roleFilter.value === "creator") {
        list = list.filter((t) => t.creator_id === userId);
    } else if (roleFilter.value === "member") {
        list = list.filter((t) => t.leader_id !== userId && t.creator_id !== userId);
    }

    // Filtro de busca textual
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter(
            (t) =>
                t.name?.toLowerCase().includes(q) ||
                t.description?.toLowerCase().includes(q)
        );
    }

    // Ordenação
    if (sortBy.value === "name") {
        list.sort((a, b) => (a.name || "").localeCompare(b.name || ""));
    } else if (sortBy.value === "members") {
        list.sort((a, b) => (b.members?.length || 0) - (a.members?.length || 0));
    } else {
        // recent
        list.sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
    }

    return list;
});
</script>

<template>
    <div>
        <!-- Barra Superior de Controles: Busca, Filtros, Ordenação e Alternância de Visualização -->
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
            <div class="row g-3 align-items-center">
                <!-- Campo de Busca -->
                <div class="col-12 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <font-awesome-icon :icon="['fas', 'magnifying-glass']" />
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="form-control border-start-0 bg-light"
                            placeholder="Buscar guilda..."
                        />
                        <button
                            v-if="searchQuery"
                            type="button"
                            class="btn btn-light border border-start-0 text-muted"
                            title="Limpar busca"
                            @click="searchQuery = ''"
                        >
                            <font-awesome-icon :icon="['fas', 'xmark']" />
                        </button>
                    </div>
                </div>

                <div class="col-12 col-lg-6 d-flex justify-content-between justify-content-lg-end align-items-center gap-2">
                    <select v-model="sortBy" class="form-select form-select-sm w-auto bg-light border">
                        <option value="recent">Mais Recentes</option>
                        <option value="name">Nome (A - Z)</option>
                        <option value="members">Mais Integrantes</option>
                    </select>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Modo de visualização">
                        <button
                            type="button"
                            class="btn"
                            :class="viewMode === 'grid' ? 'btn-secondary text-white' : 'btn-outline-secondary'"
                            title="Visualização em Grade"
                            @click="viewMode = 'grid'"
                        >
                            <font-awesome-icon :icon="['fas', 'table-cells-large']" />
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="viewMode === 'list' ? 'btn-secondary text-white' : 'btn-outline-secondary'"
                            title="Visualização em Lista"
                            @click="viewMode = 'list'"
                        >
                            <font-awesome-icon :icon="['fas', 'list']" />
                        </button>
                    </div>

                    <button
                        type="button"
                        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 shadow-sm d-lg-none"
                        @click="emit('open-create-modal')"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Nova</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Estado Vazio Geral ou de Filtro -->
        <div
            v-if="processedTeams.length === 0"
            class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-3"
        >
            <div class="mb-3 text-muted">
                <div class="rounded-circle bg-light d-inline-flex p-4 text-primary bg-opacity-10">
                    <font-awesome-icon :icon="['fas', 'shield-halved']" class="fs-1 text-primary" />
                </div>
            </div>

            <div v-if="searchQuery || roleFilter !== 'all'">
                <h4 class="fw-bold text-dark mb-2">Nenhuma guilda encontrada para estes filtros</h4>
                <p class="text-muted small mx-auto mb-4 col-md-7">
                    Não encontramos guildas correspondentes à busca ou ao filtro selecionado. Tente alterar os termos ou limpar os filtros.
                </p>
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm"
                    @click="searchQuery = ''; roleFilter = 'all'"
                >
                    <font-awesome-icon :icon="['fas', 'rotate-left']" class="me-1" />
                    Limpar Filtros de Busca
                </button>
            </div>

            <div v-else>
                <h4 class="fw-bold text-dark mb-2">Você ainda não faz parte de nenhuma guilda</h4>
                <p class="text-muted small mx-auto mb-4 col-md-7">
                    As guildas são equipes permanentes no Motirõ. Crie uma guilda para reunir parceiros de projetos, convidar amigos por e-mail e formar squads para futuros hackathons.
                </p>
                <button
                    type="button"
                    class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 shadow-sm"
                    @click="emit('open-create-modal')"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                    <span>Criar Minha Primeira Guilda</span>
                </button>
            </div>
        </div>

        <!-- MODO 1: Visualização em Grade (Cards) -->
        <div v-else-if="viewMode === 'grid'" class="row g-3">
            <div
                v-for="team in processedTeams"
                :key="team.id"
                class="col-12 col-md-6 col-lg-4"
            >
                <div class="card h-100 border-0 shadow-sm rounded-4 bg-white d-flex flex-column justify-content-between overflow-hidden">
                    <div class="p-4 pb-0">
                        <!-- Topo do Card com Avatar e Identificação -->
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div
                                v-if="team.logo || team.image_url"
                                class="rounded-3 overflow-hidden border bg-light flex-shrink-0"
                                style="width: 56px; height: 56px;"
                            >
                                <img
                                    :src="team.logo || team.image_url"
                                    :alt="team.name"
                                    class="w-100 h-100 object-fit-cover"
                                />
                            </div>
                            <div
                                v-else
                                class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold fs-5"
                                style="width: 56px; height: 56px;"
                            >
                                {{ getInitials(team.name) }}
                            </div>

                            <div class="overflow-hidden flex-grow-1">
                                <h3 class="h6 fw-bold text-dark mb-1 text-truncate" :title="team.name">
                                    {{ team.name }}
                                </h3>
                                
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                   <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <span v-if="team.creator_id === currentUser?.id" class="badge bg-dark text-white">
                                        <font-awesome-icon :icon="['fas', 'shield']" class="me-1 small" /> Criador
                                    </span>
                                    <span v-if="team.leader_id === currentUser?.id" class="badge bg-warning text-dark">
                                        <font-awesome-icon :icon="['fas', 'crown']" class="me-1 small" /> Líder
                                    </span>
                                    <span v-else-if="!team.leader_id" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        Liderança aberta
                                    </span>
                                    <span v-else class="badge bg-light text-secondary border">
                                        Integrante
                                    </span>
                                </div>
                                    <span class="badge bg-white text-dark border">
                                        {{ team.members?.length || 1 }} membro(s)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rodapé com Ação Principal -->
                    <div class="px-4 py-3 bg-light bg-opacity-50 border-top mt-auto d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            {{ formatDate(team.created_at) }}
                        </span>
                        <Link
                            :href="getGuildShowUrl(team.id)"
                            class="btn btn-outline-primary btn-sm px-3 rounded-pill d-inline-flex align-items-center gap-2"
                        >
                            <span>Gerenciar Guilda</span>
                            <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODO 2: Visualização em Lista / Linhas Compactas -->
        <div v-else class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold">Guilda</th>
                            <th class="py-3 text-secondary text-uppercase small fw-bold">Seu Papel</th>
                            <th class="py-3 text-secondary text-uppercase small fw-bold">Integrantes</th>
                            <th class="py-3 text-secondary text-uppercase small fw-bold">Criada em</th>
                            <th class="pe-4 py-3 text-end text-secondary text-uppercase small fw-bold">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="team in processedTeams" :key="team.id">
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        v-if="team.logo || team.image_url"
                                        class="rounded-3 overflow-hidden border bg-light flex-shrink-0"
                                        style="width: 44px; height: 44px;"
                                    >
                                        <img
                                            :src="team.logo || team.image_url"
                                            :alt="team.name"
                                            class="w-100 h-100 object-fit-cover"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0 fw-bold"
                                        style="width: 44px; height: 44px;"
                                    >
                                        {{ getInitials(team.name) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ team.name }}</div>
                                        <span class="text-muted small text-truncate d-block" style="max-width: 320px;">
                                            {{ team.description || 'Sem descrição' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3">
                                <span v-if="team.creator_id === currentUser?.id" class="badge bg-dark text-white me-1">
                                    <font-awesome-icon :icon="['fas', 'shield']" class="me-1 small" /> Criador
                                </span>
                                <span v-if="team.leader_id === currentUser?.id" class="badge bg-warning text-dark">
                                    <font-awesome-icon :icon="['fas', 'crown']" class="me-1 small" /> Líder
                                </span>
                                <span v-else-if="!team.leader_id" class="badge bg-warning-subtle text-warning-emphasis border">
                                    Liderança aberta
                                </span>
                                <span v-else class="badge bg-light text-secondary border">
                                    Integrante
                                </span>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border">
                                    <font-awesome-icon :icon="['fas', 'users']" class="me-1 text-muted" />
                                    {{ team.members?.length || 1 }} membro(s)
                                </span>
                            </td>
                            <td class="py-3 text-muted small">
                                {{ formatDate(team.created_at) }}
                            </td>
                            <td class="pe-4 py-3 text-end">
                                <Link
                                    :href="getGuildShowUrl(team.id)"
                                    class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                >
                                    Gerenciar &rarr;
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
