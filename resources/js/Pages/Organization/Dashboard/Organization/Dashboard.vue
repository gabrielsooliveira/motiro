<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import CardSimple from "@/js/Components/Cards/CardSimple.vue";

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    kpis: {
        type: Object,
        required: true,
    },
    upcoming_events: {
        type: Array,
        default: () => [],
    },
    recent_hackathons: {
        type: Array,
        default: () => [],
    },
    is_org_admin: {
        type: Boolean,
        default: false,
    },
});

const activeFilter = ref("all");

const filteredHackathons = computed(() => {
    if (activeFilter.value === "all") return props.recent_hackathons;
    if (activeFilter.value === "active") {
        return props.recent_hackathons.filter(h => ["registration", "submission", "voting"].includes(h.status));
    }
    if (activeFilter.value === "draft") {
        return props.recent_hackathons.filter(h => h.status === "draft");
    }
    if (activeFilter.value === "finished") {
        return props.recent_hackathons.filter(h => h.status === "finished");
    }
    return props.recent_hackathons;
});

const getStatusBadge = (status) => {
    switch (status) {
        case "registration":
            return { text: "Inscrições Abertas", class: "bg-success text-white" };
        case "submission":
            return { text: "Submissões Abertas", class: "bg-warning text-dark" };
        case "voting":
            return { text: "Em Avaliação", class: "bg-info text-dark" };
        case "finished":
            return { text: "Encerrado", class: "bg-secondary text-white" };
        case "draft":
        default:
            return { text: "Rascunho", class: "bg-light text-muted border" };
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return "A definir";
    const date = new Date(dateStr);
    return date.toLocaleDateString("pt-BR", { day: "2-digit", month: "short", year: "numeric" });
};
</script>

<template>
    <Head :title="`Painel da Organização | ${organization.name}`" />

    <div class="py-2">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill small">
                            <font-awesome-icon :icon="['fas', 'building']" class="me-1" />
                            Painel da Instituição
                        </span>
                        <span v-if="organization.type" class="badge bg-light text-secondary border px-2 py-1 rounded-pill small">
                            {{ organization.type }}
                        </span>
                    </div>
                    <h1 class="h2 fw-bold text-dark mb-0">{{ organization.name }}</h1>
                    <p class="text-muted small mb-0 mt-1">
                        Gerenciamento completo de hackathons, participantes, equipes e equipe administrativa.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <Link :href="route('organization.users.index')" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                        <font-awesome-icon :icon="['fas', 'users-gear']" />
                        <span>Usuários da Instituição</span>
                    </Link>
                    <Link
                        v-if="is_org_admin"
                        :href="route('hackathon.manage.create')"
                        class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Novo Hackathon</span>
                    </Link>
                </div>
            </div>

            
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Total de Hackathons</span>
                                <h2 class="h3 fw-bold mb-0 mt-1">{{ kpis.total_hackathons }}</h2>
                            </div>
                            <div class="kpi-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                                <font-awesome-icon :icon="['fas', 'trophy']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Hackathons Ativos</span>
                                <h2 class="h3 fw-bold mb-0 mt-1 text-success">{{ kpis.active_hackathons }}</h2>
                            </div>
                            <div class="kpi-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                                <font-awesome-icon :icon="['fas', 'circle-play']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Encerrados</span>
                                <h2 class="h3 fw-bold mb-0 mt-1 text-secondary">{{ kpis.finished_hackathons }}</h2>
                            </div>
                            <div class="kpi-icon bg-light text-secondary rounded-3 d-flex align-items-center justify-content-center border">
                                <font-awesome-icon :icon="['fas', 'flag-checkered']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Membros da Equipe</span>
                                <h2 class="h3 fw-bold mb-0 mt-1 text-info">{{ kpis.total_members }}</h2>
                            </div>
                            <div class="kpi-icon bg-info-subtle text-info rounded-3 d-flex align-items-center justify-content-center">
                                <font-awesome-icon :icon="['fas', 'user-shield']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Participantes</span>
                                <h2 class="h3 fw-bold mb-0 mt-1">{{ kpis.total_participants }}</h2>
                            </div>
                            <div class="kpi-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                                <font-awesome-icon :icon="['fas', 'user-group']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Equipes</span>
                                <h2 class="h3 fw-bold mb-0 mt-1">{{ kpis.total_teams }}</h2>
                            </div>
                            <div class="kpi-icon bg-warning-subtle text-warning-emphasis rounded-3 d-flex align-items-center justify-content-center">
                                <font-awesome-icon :icon="['fas', 'people-group']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Inscrições</span>
                                <h2 class="h3 fw-bold mb-0 mt-1">{{ kpis.total_registrations }}</h2>
                            </div>
                            <div class="kpi-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                                <font-awesome-icon :icon="['fas', 'clipboard-list']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small text-uppercase fw-semibold kpi-caption">Submissões</span>
                                <h2 class="h3 fw-bold mb-0 mt-1">{{ kpis.total_submissions }}</h2>
                            </div>
                            <div class="kpi-icon bg-success-subtle text-success rounded-3 d-flex align-items-center justify-content-center">
                                <font-awesome-icon :icon="['fas', 'file-arrow-up']" class="fs-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-bottom p-3 d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                    <div>
                        <h2 class="h5 fw-bold mb-0 text-dark">Hackathons da Instituição</h2>
                        <span class="text-muted small">Todos os eventos criados no contexto desta organização</span>
                    </div>

                    
                    <div class="btn-group btn-group-sm" role="group">
                        <button
                            type="button"
                            class="btn"
                            :class="activeFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="activeFilter = 'all'"
                        >
                            Todos
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="activeFilter === 'active' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="activeFilter = 'active'"
                        >
                            Ativos
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="activeFilter === 'draft' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="activeFilter = 'draft'"
                        >
                            Rascunhos
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="activeFilter === 'finished' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="activeFilter = 'finished'"
                        >
                            Encerrados
                        </button>
                    </div>
                </div>

                <div v-if="filteredHackathons.length > 0" class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th scope="col" class="ps-3">Nome do Hackathon</th>
                                <th scope="col">Status</th>
                                <th scope="col">Período</th>
                                <th scope="col" class="text-center">Inscritos</th>
                                <th scope="col" class="text-center">Equipes</th>
                                <th scope="col">Responsáveis</th>
                                <th scope="col" class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="hackathon in filteredHackathons" :key="hackathon.id">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="hackathon-thumb bg-light border rounded-2 d-flex align-items-center justify-content-center text-primary fw-bold">
                                            <font-awesome-icon :icon="['fas', 'laptop-code']" />
                                        </div>
                                        <div>
                                            <Link :href="route('hackathon.show', hackathon.slug)" class="fw-bold text-dark text-decoration-none hover-primary">
                                                {{ hackathon.title }}
                                            </Link>
                                            <div class="small text-muted">
                                                <span class="badge bg-light text-dark border me-1">{{ hackathon.modality || 'Online' }}</span>
                                                <span v-if="hackathon.category">{{ hackathon.category }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill" :class="getStatusBadge(hackathon.status).class">
                                        {{ getStatusBadge(hackathon.status).text }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small text-dark fw-medium">
                                        {{ formatDate(hackathon.start_date) }} - {{ formatDate(hackathon.end_date) }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold">{{ hackathon.registrations_count || 0 }}</span>
                                </td>
                                <td class="text-center">
                                    <span class="fw-semibold">{{ hackathon.projects_count || 0 }}</span>
                                </td>
                                <td>
                                    
                                    <div v-if="hackathon.members && hackathon.members.length > 0" class="d-flex align-items-center gap-1 flex-wrap">
                                        <span
                                            v-for="m in hackathon.members.slice(0, 3)"
                                            :key="m.id"
                                            class="badge bg-light text-dark border rounded-pill small py-1 px-2"
                                            :title="`${m.user?.name} (${m.role})`"
                                        >
                                            {{ m.user?.name?.split(' ')[0] }}
                                            <span class="opacity-75 small">({{ m.role === 'admin' ? 'Adm' : (m.role === 'manager' ? 'Gest' : 'Colab') }})</span>
                                        </span>
                                        <span v-if="hackathon.members.length > 3" class="badge bg-secondary-subtle text-secondary small rounded-pill">
                                            +{{ hackathon.members.length - 3 }}
                                        </span>
                                    </div>
                                    <span v-else class="text-muted small fst-italic">Nenhum designado</span>
                                </td>
                                <td class="text-end pe-3">
                                    <Link
                                        :href="route('hackathon.manage.index', hackathon.id)"
                                        class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1"
                                    >
                                        <font-awesome-icon :icon="['fas', 'gear']" />
                                        <span>Gerenciar</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-5">
                    <div class="icon-circle bg-light text-muted mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
                        <font-awesome-icon :icon="['fas', 'calendar-xmark']" class="fs-3" />
                    </div>
                    <h3 class="h5 fw-bold text-dark">Nenhum hackathon encontrado</h3>
                    <p class="text-muted small">Crie o primeiro hackathon da sua instituição para começar a receber equipes.</p>
                    <Link
                        v-if="is_org_admin"
                        :href="route('hackathon.manage.create')"
                        class="btn btn-primary btn-sm"
                    >
                        Criar Hackathon
                    </Link>
                </div>
            </div>
        </div>
</template>

