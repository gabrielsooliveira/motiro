<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    teams: {
        type: Array,
        default: () => [],
    },
    groups: {
        type: Array,
        default: () => [],
    },
});

defineEmits(["open-create-modal"]);

const resolveHackathon = (team) => {
    return team.hackathon || (team.registrations && team.registrations[0]?.hackathon) || null;
};
</script>

<template>
    <div>
        <!-- Lista Vazia -->
        <div
            v-if="(!teams || teams.length === 0) && (!groups || groups.length === 0)"
            class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white"
        >
            <div class="py-4">
                <font-awesome-icon :icon="['fas', 'people-group']" class="fs-1 text-primary opacity-50 mb-3" />
                <h4 class="fw-bold text-dark mb-2">Você ainda não integra nenhuma equipe de hackathon</h4>
                <p class="text-secondary small mb-4 col-md-8 mx-auto">
                    As equipes são formadas especificamente para competir em hackathons. Você pode formar uma nova equipe para um evento em que está inscrito ou participar de uma equipe já existente.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button
                        type="button"
                        class="btn btn-primary rounded-pill px-4 py-2 shadow-sm"
                        @click="$emit('open-create-modal')"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" class="me-2" />
                        <span>Formar Nova Equipe</span>
                    </button>
                    <Link
                        :href="route('participant.hackathons')"
                        class="btn btn-outline-secondary rounded-pill px-4 py-2"
                    >
                        <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="me-2" />
                        <span>Explorar Hackathons</span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Grid de Equipes -->
        <div v-else class="row g-4">
            <!-- Equipes (Teams) -->
            <div
                v-for="team in teams"
                :key="'team-' + team.id"
                class="col-12 col-md-6 col-xl-4"
            >
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden d-flex flex-column justify-content-between">
                    <div class="card-body p-4">
                        <!-- Topo do Card -->
                        <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                            <span v-if="resolveHackathon(team)" class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small text-truncate">
                                <font-awesome-icon :icon="['fas', 'trophy']" class="me-1" />
                                {{ resolveHackathon(team)?.title }}
                            </span>
                            <span v-else class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">
                                Equipe Competitiva
                            </span>

                            <span v-if="team.leader_id" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                                <font-awesome-icon :icon="['fas', 'crown']" class="me-1" /> Com Líder
                            </span>
                        </div>

                        <!-- Nome e Área de Foco -->
                        <h5 class="fw-bold text-dark mb-1">{{ team.name }}</h5>
                        <p v-if="team.area_of_focus" class="text-muted small mb-3">
                            <font-awesome-icon :icon="['fas', 'bullseye']" class="me-1 text-primary" />
                            <strong>Trilha:</strong> {{ team.area_of_focus }}
                        </p>
                        <p v-else class="text-secondary small mb-3 text-truncate">
                            {{ team.description || 'Equipe formada para participação em desafios.' }}
                        </p>

                        <!-- Integrantes da Equipe -->
                        <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-1">
                                <div
                                    v-for="(member, idx) in (team.members || []).slice(0, 4)"
                                    :key="member.id"
                                    class="rounded-circle bg-secondary bg-opacity-10 text-secondary border d-flex align-items-center justify-content-center fw-bold small"
                                    style="width: 32px; height: 32px; margin-left: -6px; z-index: 1;"
                                    :title="member.name"
                                >
                                    {{ member.name?.charAt(0)?.toUpperCase() || 'U' }}
                                </div>
                                <span v-if="(team.members || []).length > 4" class="small text-muted ms-2">
                                    +{{ (team.members || []).length - 4 }}
                                </span>
                            </div>

                            <span class="badge bg-light text-secondary border small">
                                {{ team.members ? team.members.length : 0 }} participante(s)
                            </span>
                        </div>
                    </div>

                    <!-- Rodapé do Card -->
                    <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            Status: <strong class="text-success">Ativa</strong>
                        </small>
                        <Link
                            :href="route('participant.guilds.show', team.id)"
                            class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm"
                        >
                            Acessar Equipe 
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Grupos Competitivos (Hackathon Groups) -->
            <div
                v-for="group in groups"
                :key="'group-' + group.id"
                class="col-12 col-md-6 col-xl-4"
            >
                <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden d-flex flex-column justify-content-between">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                            <span v-if="group.hackathon" class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-1 small text-truncate">
                                <font-awesome-icon :icon="['fas', 'trophy']" class="me-1" />
                                {{ group.hackathon.title }}
                            </span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                Grupo de Competição
                            </span>
                        </div>

                        <h5 class="fw-bold text-dark mb-1">{{ group.name }}</h5>
                        <p v-if="group.origin_team" class="text-muted small mb-2">
                            <font-awesome-icon :icon="['fas', 'shield']" class="me-1 text-primary" />
                            Origem: <strong>{{ group.origin_team.name }}</strong>
                        </p>
                        <p v-if="group.area_of_focus" class="text-muted small mb-3">
                            <strong>Trilha:</strong> {{ group.area_of_focus }}
                        </p>
                        <p v-else class="text-secondary small mb-3">
                            {{ group.description || 'Grupo competitivo no evento.' }}
                        </p>

                        <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="small text-muted">
                                {{ group.members ? group.members.length : 0 }} integrante(s)
                            </span>
                            <span v-if="group.projects && group.projects.length > 0" class="badge bg-success text-white small">
                                Projeto Enviado
                            </span>
                            <span v-else class="badge bg-warning-subtle text-warning-emphasis small">
                                Sem Projeto
                            </span>
                        </div>
                    </div>

                    <div class="card-footer bg-light border-top p-3 text-end">
                        <Link
                            v-if="group.hackathon?.slug"
                            :href="route('hackathon.show', group.hackathon.slug)"
                            class="btn btn-outline-primary btn-sm rounded-pill px-3"
                        >
                            Ver no Hackathon 
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
