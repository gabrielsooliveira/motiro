<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

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

const page = usePage();
const currentUserId = computed(() => page.props.user?.id);

const allSquads = computed(() => {
    const list = [];
    (props.teams || []).forEach((t) => {
        list.push({
            id: t.id,
            name: t.name,
            type: "Equipe",
            hackathonTitle: t.hackathon?.title || (t.registrations && t.registrations[0]?.hackathon?.title) || "Competição Geral",
            leaderId: t.leader_id,
            members: t.members || [],
        });
    });
    (props.groups || []).forEach((g) => {
        list.push({
            id: g.id,
            name: g.name,
            type: "Grupo de Hackathon",
            hackathonTitle: g.hackathon?.title || "Hackathon",
            leaderId: g.leader_id,
            members: g.members || [],
        });
    });
    return list;
});
</script>

<template>
    <div>
        <div v-if="allSquads.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <font-awesome-icon :icon="['fas', 'users-slash']" class="fs-1 text-muted opacity-50 mb-3" />
            <h5 class="fw-bold text-dark">Nenhum integrante para exibir</h5>
            <p class="text-secondary small mb-0">Você ainda não possui colegas vinculados a equipes ativas.</p>
        </div>

        <div v-else class="d-flex flex-column gap-4">
            <div
                v-for="squad in allSquads"
                :key="squad.id"
                class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden"
            >
                <!-- Cabeçalho do Squad -->
                <div class="card-header bg-transparent py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <font-awesome-icon :icon="['fas', 'people-group']" class="text-primary" />
                            <span>{{ squad.name }}</span>
                            <span class="badge bg-light text-secondary border small">{{ squad.type }}</span>
                        </h6>
                        <small class="text-muted">{{ squad.hackathonTitle }}</small>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        {{ squad.members.length }} membro(s)
                    </span>
                </div>

                <!-- Lista de Integrantes -->
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li
                            v-for="member in squad.members"
                            :key="member.id"
                            class="list-group-item p-3 d-flex justify-content-between align-items-center"
                        >
                            <div class="d-flex align-items-center gap-3">
                                <div
                                    class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center fw-bold"
                                    style="width: 40px; height: 40px;"
                                >
                                    {{ member.name?.charAt(0)?.toUpperCase() || 'U' }}
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <strong class="text-dark">{{ member.name }}</strong>
                                        <span v-if="member.id === currentUserId" class="badge bg-light text-secondary border small">Você</span>
                                    </div>
                                    <small class="text-muted">{{ member.email }}</small>
                                </div>
                            </div>

                            <div>
                                <span v-if="member.id === squad.leaderId" class="badge bg-warning text-dark">
                                    <font-awesome-icon :icon="['fas', 'crown']" class="me-1 small" /> Líder
                                </span>
                                <span v-else class="badge bg-light text-secondary border">
                                    Integrante
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
