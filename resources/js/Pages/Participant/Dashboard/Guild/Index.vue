<script setup>
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import GuildsList from "@/js/Pages/Participant/Dashboard/Guild/Partials/GuildsList.vue";
import GuildInvites from "@/js/Pages/Participant/Dashboard/Guild/Partials/GuildInvites.vue";
import GuildMembers from "@/js/Pages/Participant/Dashboard/Guild/Partials/GuildMembers.vue";
import CreateGuildModal from "@/js/Components/Team/CreateGuildModal.vue";

const props = defineProps({
    guilds: {
        type: Array,
        default: () => [],
    },
    teams: {
        type: Array,
        default: () => [],
    },
    invitations: {
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

const effectiveGuilds = computed(() => {
    return props.guilds && props.guilds.length > 0 ? props.guilds : props.teams;
});

// Inicialização com suporte a query parameter (?tab=invites)
const searchParams = typeof window !== "undefined" ? new URLSearchParams(window.location.search) : null;
const queryTab = searchParams?.get("tab");
const validTabs = ["guilds", "invites", "members"];
const activeTab = ref(validTabs.includes(queryTab) ? queryTab : "guilds");

const showCreateGuildModal = ref(false);
const showInfoCard = ref(true);

const handleAcceptInvite = (id) => {
    const targetRoute = route().has("participant.guilds.invite.accept")
        ? route("participant.guilds.invite.accept", id)
        : route("participant.teams.invite.accept", id);
    router.post(targetRoute);
};

const handleDeclineInvite = (id) => {
    const targetRoute = route().has("participant.guilds.invite.decline")
        ? route("participant.guilds.invite.decline", id)
        : route("participant.teams.invite.decline", id);
    router.post(targetRoute);
};

// Métricas dos Cards do Topo
const leaderCount = computed(() => {
    const userId = currentUser.value?.id;
    return effectiveGuilds.value.filter((g) => g.leader_id === userId).length;
});

const totalMembersCount = computed(() => {
    const memberIds = new Set();
    effectiveGuilds.value.forEach((guild) => {
        if (guild.members && Array.isArray(guild.members)) {
            guild.members.forEach((m) => memberIds.add(m.id));
        }
    });
    return memberIds.size;
});

const selectTab = (tab) => {
    activeTab.value = tab;
    if (typeof window !== "undefined" && window.history?.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.set("tab", tab);
        window.history.replaceState({}, "", url.toString());
    }
};
</script>

<template>
    <Head>
        <title>Minhas Guilds</title>
    </Head>

    <div class="py-2">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h1 class="h2 fw-bold text-dark mb-0">Minhas Guilds</h1>
                </div>
                <p class="text-muted small mb-0">Organize suas guilds de longo prazo no Motirõ, convide membros por e-mail e dispute hackathons com seus parceiros.</p>
            </div>

            <div class="d-flex gap-2 align-items-center flex-wrap">
                <Link :href="route('participant.dashboard')" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 rounded-pill">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" />
                    <span>Voltar ao Painel</span>
                </Link>

                <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm px-4 rounded-pill" @click="showCreateGuildModal = true">
                    <font-awesome-icon :icon="['fas', 'plus']" />
                    <span class="fw-semibold">Nova Guild</span>
                </button>
            </div>
        </div>

        <div v-if="showInfoCard" class="alert alert-info border-0 shadow-sm d-flex align-items-start justify-content-between gap-3 p-3 mb-4 rounded-4">
            <div class="d-flex align-items-start gap-3">
                <font-awesome-icon :icon="['fas', 'circle-info']" class="text-info fs-5 mt-1 flex-shrink-0" />
                <div class="small">
                    <strong>Como funcionam as Guilds no Motirõ?</strong>
                    <p class="mb-0 mt-1 text-secondary">
                        Uma guild representa sua equipe estável fora de eventos específicos. Criar ou entrar em uma guild <strong>não inscreve você compulsoriamente em nenhum hackathon</strong>. Quando você for disputar um evento específico, formará ou vinculará um <em>Grupo de Participação</em> exclusivo daquele hackathon.
                    </p>
                </div>
            </div>
            <button type="button" class="btn-close flex-shrink-0" aria-label="Dispensar aviso" @click="showInfoCard = false"></button>
        </div>

        <div class="border-bottom pb-2 mb-4">
            <ul class="nav nav-pills gap-2 flex-nowrap overflow-x-auto pb-1" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        type="button"
                        class="nav-link d-inline-flex align-items-center gap-2 text-nowrap py-2 px-3 fw-semibold border rounded-pill"
                        :class="activeTab === 'guilds' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'"
                        role="tab"
                        @click="selectTab('guilds')"
                    >
                        <font-awesome-icon :icon="['fas', 'shield-halved']" />
                        <span>Minhas Guilds</span>
                        <span
                            class="badge rounded-pill ms-1"
                            :class="activeTab === 'guilds' ? 'bg-white text-primary' : 'bg-secondary text-white'"
                        >
                            {{ effectiveGuilds.length }}
                        </span>
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link d-inline-flex align-items-center gap-2 text-nowrap py-2 px-3 fw-semibold border rounded-pill" :class="activeTab === 'invites' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'" role="tab" @click="selectTab('invites')">
                        <font-awesome-icon :icon="['fas', 'envelope-open-text']" />
                        <span>Convites Recebidos</span>
                        <span v-if="invitations.length > 0" class="badge rounded-pill ms-1 bg-danger text-white">{{ invitations.length }}</span>
                        <span v-else class="badge rounded-pill ms-1" :class="activeTab === 'invites' ? 'bg-white text-primary' : 'bg-light text-secondary border'">
                            0
                        </span>
                    </button>
                </li>

                <li class="nav-item" role="presentation">
                    <button type="button" class="nav-link d-inline-flex align-items-center gap-2 text-nowrap py-2 px-3 fw-semibold border rounded-pill" :class="activeTab === 'members' ? 'active bg-primary text-white border-primary shadow-sm' : 'bg-white text-secondary border-light'" role="tab" @click="selectTab('members')">
                        <font-awesome-icon :icon="['fas', 'users']" />
                        <span>Membros Conectados</span>
                        <span class="badge rounded-pill ms-1" :class="activeTab === 'members' ? 'bg-white text-primary' : 'bg-light text-secondary border'">
                            {{ totalMembersCount }}
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content-viewport pb-5">
            <GuildsList
                v-if="activeTab === 'guilds'"
                :guilds="effectiveGuilds"
                :teams="effectiveGuilds"
                :current-user="currentUser"
                @open-create-modal="showCreateGuildModal = true"
            />

            <GuildInvites
                v-if="activeTab === 'invites'"
                :invitations="invitations"
                :current-user="currentUser"
                @accept="handleAcceptInvite"
                @decline="handleDeclineInvite"
            />

            <GuildMembers
                v-if="activeTab === 'members'"
                :guilds="effectiveGuilds"
                :teams="effectiveGuilds"
                :current-user="currentUser"
            />
        </div>

        <!-- Modal de Criação de Guild Dedicado -->
        <CreateGuildModal
            :show="showCreateGuildModal"
            @close="showCreateGuildModal = false"
            @submitted="showCreateGuildModal = false"
        />
    </div>
</template>
