<script setup>
import { Head, router } from "@inertiajs/vue3";

const props = defineProps({
    organizations: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        required: true,
    },
});

const selectContext = (type, orgId = null) => {
    router.post(route("context.switch"), {
        type,
        organization_id: orgId,
    });
};

const getRoleBadge = (role) => {
    switch (role) {
        case "admin": return { text: "Administrador", class: "bg-primary text-white" };
        case "manager": return { text: "Gestor de Hackathon", class: "bg-success text-white" };
        case "collaborator": return { text: "Colaborador", class: "bg-info text-dark" };
        default: return { text: "Membro", class: "bg-secondary text-white" };
    }
};
</script>

<template>
    <Head title="Selecionar Instituição | Motirõ" />

    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center py-5 bg-light">
        <div class="container" style="max-width: 820px;">
            <div class="text-center mb-4">
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill fw-semibold mb-2">
                    Multi-Institucional
                </span>
                <h1 class="h3 fw-bold text-dark mb-1">Bem-vindo(a), {{ user.name }}</h1>
                <p class="text-muted">Selecione a instituição que deseja gerenciar ou acesse como participante.</p>
            </div>

            <div class="row g-4">
                <!-- Competitor Card -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 context-card position-relative overflow-hidden">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="icon-bubble bg-warning bg-opacity-15 text-warning mb-3 d-flex align-items-center justify-content-center rounded-3">
                                    <font-awesome-icon :icon="['fas', 'trophy']" class="fs-4 text-warning" />
                                </div>
                                <span class="badge bg-warning-subtle text-warning-emphasis mb-2">Área Pessoal</span>
                                <h2 class="h5 fw-bold text-dark mb-2">Participante / Competidor</h2>
                                <p class="text-muted small mb-0">
                                    Explore hackathons, gerencie suas equipes e submeta projetos para avaliação.
                                </p>
                            </div>
                            <div class="mt-4 pt-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-warning w-100 fw-semibold d-flex align-items-center justify-content-center gap-2"
                                    @click="selectContext('competitor')"
                                >
                                    <span>Acessar como Competidor</span>
                                    <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Organizations Cards -->
                <div
                    v-for="org in organizations"
                    :key="org.id"
                    class="col-12 col-md-6 col-lg-4"
                >
                    <div class="card h-100 border-0 shadow-sm rounded-4 context-card position-relative overflow-hidden">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="icon-bubble bg-primary bg-opacity-10 text-primary mb-3 d-flex align-items-center justify-content-center rounded-3">
                                    <font-awesome-icon :icon="['fas', 'building']" class="fs-4 text-primary" />
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-light text-secondary border">
                                        {{ org.type || 'Instituição' }}
                                    </span>
                                    <span
                                        v-if="org.pivot?.role"
                                        class="badge rounded-pill"
                                        :class="getRoleBadge(org.pivot.role).class"
                                    >
                                        {{ getRoleBadge(org.pivot.role).text }}
                                    </span>
                                </div>
                                <h2 class="h5 fw-bold text-dark mb-2">{{ org.name }}</h2>
                                <p class="text-muted small mb-0">
                                    Administre hackathons, configure etapas, gerencie inscrições e participantes.
                                </p>
                            </div>
                            <div class="mt-4 pt-2">
                                <button
                                    type="button"
                                    class="btn btn-primary w-100 fw-semibold d-flex align-items-center justify-content-center gap-2"
                                    @click="selectContext('organization', org.id)"
                                >
                                    <span>Acessar Painel</span>
                                    <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom info -->
            <div class="text-center mt-5 text-muted small">
                <span>Deseja cadastrar uma nova instituição? </span>
                <a :href="route('register') + '?type=organization'" class="text-primary text-decoration-none fw-semibold">
                    Cadastrar instituição
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.context-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: 1px solid rgba(0, 0, 0, 0.06) !important;
}
.context-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
}
.icon-bubble {
    width: 48px;
    height: 48px;
}
</style>
