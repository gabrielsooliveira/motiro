<script setup>
import { Head, Link, router } from "@inertiajs/vue3";

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

const selectOrganization = (orgId) => {
    router.post(route("organization.select.store"), {
        type: "organization",
        organization_id: orgId,
    });
};

const getRoleBadge = (role) => {
    switch (role) {
        case "admin": return { text: "Administrador", class: "bg-primary text-white" };
        case "manager": return { text: "Gestor", class: "bg-success text-white" };
        case "collaborator": return { text: "Colaborador", class: "bg-info text-dark" };
        default: return { text: "Membro", class: "bg-secondary text-white" };
    }
};
</script>

<template>
    <Head title="Selecionar Organização | Motirõ" />

    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light">
        <div class="container select-context-container">
            <div class="text-center mb-4">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill fw-semibold mb-2">
                    <font-awesome-icon :icon="['fas', 'building']" class="me-1" />
                    Área da Organização
                </span>
                <h1 class="h3 fw-bold text-dark mb-1">Olá, {{ user.name }}</h1>
                <p class="text-muted">Selecione a instituição que deseja administrar neste momento.</p>
            </div>

            
            <div v-if="organizations.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
                <div class="icon-bubble icon-bubble--xl bg-secondary-subtle text-secondary mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle">
                    <font-awesome-icon :icon="['fas', 'building-circle-exclamation']" class="fs-2" />
                </div>
                <h3 class="h5 fw-bold text-dark mb-2">Nenhuma organização encontrada</h3>
                <p class="text-muted small select-context-subtitle mx-auto mb-4">
                    Sua conta ainda não possui vínculos ativos com nenhuma instituição para acesso administrativo.
                </p>
                <div class="d-flex justify-content-center gap-2">
                    <Link :href="route('organization.register')" class="btn btn-primary fw-semibold">
                        Cadastrar Organização
                    </Link>
                    <Link :href="route('participant.dashboard')" class="btn btn-outline-secondary fw-semibold">
                        Ir para Área do Participante
                    </Link>
                </div>
            </div>

            
            <div v-else class="row g-4 justify-content-center">
                <div
                    v-for="org in organizations"
                    :key="org.id"
                    class="col-12 col-md-6"
                >
                    <div class="card h-100 border-0 shadow-sm rounded-4 context-card position-relative overflow-hidden p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="icon-bubble bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center rounded-3 overflow-hidden flex-shrink-0">
                                        <img v-if="org.logo" :src="org.logo" :alt="org.name" class="img-fluid object-fit-cover w-100 h-100" />
                                        <font-awesome-icon v-else :icon="['fas', 'building']" class="fs-4 text-primary" />
                                    </div>
                                    <div>
                                        <h2 class="h5 fw-bold text-dark mb-0">{{ org.name }}</h2>
                                        <span class="text-muted small">{{ org.type || 'Instituição' }}</span>
                                    </div>
                                </div>
                                <span
                                    v-if="org.role"
                                    class="badge rounded-pill small"
                                    :class="getRoleBadge(org.role).class"
                                >
                                    {{ getRoleBadge(org.role).text }}
                                </span>
                            </div>

                            <p class="text-secondary small mb-3">
                                {{ org.description || 'Instituição cadastrada na plataforma Motirõ para gestão e realização de hackathons.' }}
                            </p>

                            <div class="d-flex align-items-center gap-2 text-muted small">
                                <span class="badge bg-light text-dark border">
                                    <font-awesome-icon :icon="['fas', 'trophy']" class="text-primary me-1" />
                                    {{ org.hackathons_count || 0 }} {{ (org.hackathons_count === 1) ? 'hackathon' : 'hackathons' }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button
                                type="button"
                                class="btn btn-primary w-100 fw-semibold d-flex align-items-center justify-content-center gap-2 py-2 shadow-sm"
                                @click="selectOrganization(org.id)"
                            >
                                <span>Acessar Painel da Organização</span>
                                <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mt-5 pt-3 border-top text-muted small gap-3">
                <Link :href="route('participant.dashboard')" class="text-secondary text-decoration-none">
                    <font-awesome-icon :icon="['fas', 'trophy']" class="me-1 text-warning" />
                    Ir para o meu Painel de Participante
                </Link>

                <div>
                    <span>Deseja gerenciar outra instituição? </span>
                    <Link :href="route('organization.register')" class="text-primary text-decoration-none fw-semibold">
                        Cadastrar nova organização
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
