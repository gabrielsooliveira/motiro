<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import TeamActiveList from "@/js/Components/Team/TeamActiveList.vue";
import TeamMembers from "@/js/Components/Team/TeamMembers.vue";
import TeamInvites from "@/js/Components/Team/TeamInvites.vue";
import TeamHackathons from "@/js/Components/Team/TeamHackathons.vue";
import CreateGuildModal from "@/js/Components/Team/CreateGuildModal.vue";

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
    guilds: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const currentUser = computed(() => props.user || page.props.user);

// Inicialização com suporte a query parameter (?tab=...)
const searchParams = typeof window !== "undefined" ? new URLSearchParams(window.location.search) : null;
const queryTab = searchParams?.get("tab");
const validTabs = ["teams", "members", "invites", "hackathons"];
const activeTab = ref(validTabs.includes(queryTab) ? queryTab : "teams");

const showCreateModal = ref(false);

const selectTab = (tab) => {
    activeTab.value = tab;
    if (typeof window !== "undefined" && window.history?.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.set("tab", tab);
        window.history.replaceState({}, "", url.toString());
    }
};

// Métricas de Equipes
const totalSquadsCount = computed(() => {
    return (props.teams?.length || 0) + (props.groups?.length || 0);
});

const leaderCount = computed(() => {
    const userId = currentUser.value?.id;
    let count = 0;
    (props.teams || []).forEach((t) => {
        if (t.leader_id === userId) count++;
    });
    (props.groups || []).forEach((g) => {
        if (g.leader_id === userId) count++;
    });
    return count;
});

const totalMembersCount = computed(() => {
    const memberIds = new Set();
    (props.teams || []).forEach((team) => {
        if (team.members && Array.isArray(team.members)) {
            team.members.forEach((m) => memberIds.add(m.id));
        }
    });
    (props.groups || []).forEach((group) => {
        if (group.members && Array.isArray(group.members)) {
            group.members.forEach((m) => memberIds.add(m.id));
        }
    });
    return memberIds.size;
});
</script>

<template>
    <Head>
        <title>Equipes de Hackathon | Motirõ</title>
    </Head>

    <div class="py-2">
        <!-- Cabeçalho Principal -->
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h2 fw-bold text-dark mb-0">Equipes de Hackathon</h1>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small">
                        Competição em Eventos
                    </span>
                </div>
                <p class="text-muted small mb-0">
                    Acompanhe suas equipes de disputa em hackathons, gerencie integrantes e submeta projetos para avaliação dos jurados.
                </p>
            </div>

            <div class="d-flex gap-2">
                <Link
                    :href="route('participant.guilds.index')"
                    class="btn btn-outline-secondary rounded-pill px-3 d-inline-flex align-items-center gap-2 shadow-sm"
                >
                    <font-awesome-icon :icon="['fas', 'shield-halved']" />
                    <span>Ver Minhas Guildas</span>
                </Link>
                <button
                    type="button"
                    class="btn btn-primary rounded-pill px-4 d-inline-flex align-items-center gap-2 shadow-sm"
                    @click="activeTab = 'hackathons'"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                    <span>Formar Equipe</span>
                </button>
            </div>
        </div>

        <!-- Cards de Métricas de Equipes -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center">
                            <font-awesome-icon :icon="['fas', 'people-group']" class="fs-4" />
                        </div>
                        <div>
                            <span class="text-muted small d-block">Equipes Ativas</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ totalSquadsCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3 d-flex align-items-center justify-content-center">
                            <font-awesome-icon :icon="['fas', 'crown']" class="fs-4" />
                        </div>
                        <div>
                            <span class="text-muted small d-block">Você Lidera</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ leaderCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center">
                            <font-awesome-icon :icon="['fas', 'users']" class="fs-4" />
                        </div>
                        <div>
                            <span class="text-muted small d-block">Colegas de Squad</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ totalMembersCount }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3 d-flex align-items-center justify-content-center">
                            <font-awesome-icon :icon="['fas', 'envelope']" class="fs-4" />
                        </div>
                        <div>
                            <span class="text-muted small d-block">Convites de Equipes</span>
                            <h3 class="fw-bold mb-0 text-dark">{{ invitations ? invitations.length : 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegação por Abas Responsiva de Equipes -->
        <ul class="nav nav-pills gap-2 border-bottom pb-3 mb-4 flex-nowrap overflow-x-auto" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'teams' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="selectTab('teams')"
                >
                    <font-awesome-icon :icon="['fas', 'people-group']" />
                    <span>Minhas Equipes</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'teams' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ totalSquadsCount }}
                    </span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'members' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="selectTab('members')"
                >
                    <font-awesome-icon :icon="['fas', 'users']" />
                    <span>Integrantes</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'members' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ totalMembersCount }}
                    </span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'invites' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="selectTab('invites')"
                >
                    <font-awesome-icon :icon="['fas', 'envelope']" />
                    <span>Convites</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'invites' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ invitations ? invitations.length : 0 }}
                    </span>
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    type="button"
                    class="nav-link d-inline-flex align-items-center gap-2 text-nowrap rounded-pill py-2 px-3 fw-semibold border"
                    :class="activeTab === 'hackathons' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                    @click="selectTab('hackathons')"
                >
                    <font-awesome-icon :icon="['fas', 'trophy']" />
                    <span>Hackathons & Formação</span>
                    <span
                        class="badge rounded-pill ms-1"
                        :class="activeTab === 'hackathons' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                    >
                        {{ registrations ? registrations.length : 0 }}
                    </span>
                </button>
            </li>
        </ul>

        <!-- Viewport do Conteúdo das Abas de Equipe -->
        <div class="tab-content-viewport pb-5">
            <TeamActiveList
                v-if="activeTab === 'teams'"
                :teams="teams"
                :groups="groups"
                @open-create-modal="activeTab = 'hackathons'"
            />

            <TeamMembers
                v-if="activeTab === 'members'"
                :teams="teams"
                :groups="groups"
            />

            <TeamInvites
                v-if="activeTab === 'invites'"
                :invitations="invitations"
            />

            <TeamHackathons
                v-if="activeTab === 'hackathons'"
                :registrations="registrations"
                :guilds="guilds"
            />
        </div>
    </div>
</template>
