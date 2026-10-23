<script setup>
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
    organizations: {
        type: Array,
        default: () => [],
    },
    hasParticipantProfile: {
        type: Boolean,
        default: false,
    },
    hasOrganizationProfile: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        required: true,
    },
});

const selectOrganization = (orgId) => {
    router.post(route("context.switch"), {
        type: "organization",
        organization_id: orgId,
    });
};

const selectParticipant = () => {
    router.post(route("context.switch"), {
        type: "competitor",
    });
};

const getRoleBadge = (role) => {
    switch (role) {
        case "owner": return { text: "Proprietário", class: "bg-danger text-white" };
        case "admin": return { text: "Administrador", class: "bg-primary text-white" };
        case "manager": return { text: "Gestor", class: "bg-success text-white" };
        case "collaborator": return { text: "Colaborador", class: "bg-info text-dark" };
        default: return { text: "Membro", class: "bg-secondary text-white" };
    }
};
</script>

<template>
    <Head title="Selecionar Contexto | Motirõ" />

    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light py-5">
        <div class="container select-context-container">
            <div class="text-center mb-4">
                <Link :href="route('home')" class="text-decoration-none">
                    <span class="fs-2 fw-bold text-dark">Motirõ</span>
                </Link>
                <h1 class="h3 fw-bold text-dark mt-2 mb-1">Olá, {{ user.name }}</h1>
                <p class="text-muted">Selecione o espaço que deseja acessar neste momento.</p>
            </div>

            <!-- Participant Context Card -->
            <div class="mb-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-bubble bg-warning-subtle text-warning d-flex align-items-center justify-content-center rounded-3 p-3">
                                <font-awesome-icon :icon="['fas', 'user-astronaut']" class="fs-3 text-warning" />
                            </div>
                            <div>
                                <span class="badge bg-warning text-dark small fw-semibold mb-1">Área do Participante</span>
                                <h2 class="h5 fw-bold text-dark mb-0">Portal de Competições e Hackathons</h2>
                                <p class="text-muted small mb-0">Participe de eventos, monte guilds e acompanhe suas submissões.</p>
                            </div>
                        </div>

                        <div>
                            <button
                                v-if="hasParticipantProfile"
                                type="button"
                                class="btn btn-warning fw-semibold px-4 py-2 d-flex align-items-center gap-2 text-nowrap"
                                @click="selectParticipant"
                            >
                                <span>Acessar Participante</span>
                                <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                            </button>
                            <Link
                                v-else
                                :href="route('participant.complete_profile')"
                                class="btn btn-outline-warning text-dark fw-semibold px-4 py-2 text-nowrap"
                            >
                                Ativar Perfil de Participante
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Organizations Section -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h3 class="h6 text-uppercase fw-bold text-muted mb-0">
                        <font-awesome-icon :icon="['fas', 'building']" class="me-1" />
                        Suas Organizações Institucionais
                    </h3>
                    <Link :href="route('organization.register')" class="text-primary text-decoration-none small fw-semibold">
                        + Cadastrar nova organização
                    </Link>
                </div>

                <!-- Empty State for Organizations -->
                <div v-if="organizations.length === 0" class="card border-0 shadow-sm rounded-4 p-4 text-center bg-white">
                    <div class="icon-bubble icon-bubble--xl bg-secondary-subtle text-secondary mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle">
                        <font-awesome-icon :icon="['fas', 'building']" class="fs-3" />
                    </div>
                    <h4 class="h6 fw-bold text-dark mb-1">Nenhuma organização vinculada</h4>
                    <p class="text-muted small mb-3">
                        Sua conta não possui vínculos administrativos com instituições no momento.
                    </p>
                    <div>
                        <Link :href="route('organization.register')" class="btn btn-sm btn-primary fw-semibold">
                            Cadastrar uma Organização
                        </Link>
                    </div>
                </div>

                <!-- Organizations Grid -->
                <div v-else class="row g-3">
                    <div
                        v-for="org in organizations"
                        :key="org.id"
                        class="col-12 col-md-6"
                    >
                        <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center overflow-hidden summary-logo">
                                            <img v-if="org.logo" :src="org.logo" :alt="org.name" class="img-fluid object-fit-cover w-100 h-100" />
                                            <font-awesome-icon v-else :icon="['fas', 'building']" class="fs-4 text-primary" />
                                        </div>
                                        <div>
                                            <h4 class="h6 fw-bold text-dark mb-0">{{ org.name }}</h4>
                                            <span class="text-muted small">{{ org.type || 'Instituição' }}</span>
                                        </div>
                                    </div>
                                    <span v-if="org.role" class="badge rounded-pill small" :class="getRoleBadge(org.role).class">
                                        {{ getRoleBadge(org.role).text }}
                                    </span>
                                </div>
                                <p class="text-secondary small mb-3">
                                    {{ org.description || 'Gestão institucional e realização de hackathons.' }}
                                </p>
                            </div>

                            <button
                                type="button"
                                class="btn btn-outline-primary fw-semibold w-100 d-flex align-items-center justify-content-center gap-2"
                                @click="selectOrganization(org.id)"
                            >
                                <span>Acessar Painel desta Organização</span>
                                <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
