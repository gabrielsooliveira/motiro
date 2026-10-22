<script setup>
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    user: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Organização Cadastrada | Motirõ" />

    <div class="text-center py-2 animate__animated animate__fadeIn">
        
        <div class="mb-3">
            <div
                class="rounded-circle bg-success bg-opacity-20 text-success d-inline-flex align-items-center justify-content-center success-icon-bubble"
            >
                <font-awesome-icon :icon="['fas', 'circle-check']" class="fs-1 text-success" />
            </div>
        </div>

        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill small mb-2 fw-semibold">
            Etapa 4 de 4 &bull; Conclusão
        </span>
        <h2 class="h3 fw-bold text-white mb-2">Organização cadastrada com sucesso!</h2>
        <p class="text-light text-opacity-75 small mb-4 success-message">
            Agora você pode configurar seu espaço institucional, convidar membros da equipe e começar a criar seus hackathons.
        </p>

        
        <div class="card bg-dark border-secondary mb-4 text-start shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom border-secondary-subtle">
                    <div
                        class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0 success-org-logo"
                    >
                        <img
                            v-if="organization.logo"
                            :src="organization.logo"
                            :alt="organization.name"
                            class="img-fluid object-fit-cover w-100 h-100"
                        />
                        <font-awesome-icon v-else :icon="['fas', 'building']" class="fs-3 text-primary" />
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="h5 fw-bold text-white mb-0 text-truncate">{{ organization.name }}</h4>
                        <span class="badge bg-primary text-white small mt-1">{{ organization.type || 'Instituição' }}</span>
                    </div>
                </div>

                <div class="row g-2 small text-light text-opacity-75">
                    <div class="col-12 col-sm-6" v-if="organization.email">
                        <strong class="text-white d-block">E-mail Institucional:</strong>
                        <span>{{ organization.email }}</span>
                    </div>
                    <div class="col-12 col-sm-6" v-if="organization.website">
                        <strong class="text-white d-block">Website:</strong>
                        <a :href="organization.website" target="_blank" rel="noopener noreferrer" class="text-info text-decoration-none">
                            {{ organization.website }}
                        </a>
                    </div>
                    <div class="col-12 mt-2 pt-2 border-top border-secondary-subtle">
                        <strong class="text-white d-block">Administrador Responsável:</strong>
                        <span>{{ user.name }} ({{ user.email }})</span>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="d-flex flex-column gap-2 mb-4">
            <Link
                :href="route('organization.dashboard')"
                class="btn btn-primary fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm"
            >
                <span>Acessar Painel da Organização</span>
                <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
            </Link>

            <Link
                :href="route('participant.dashboard')"
                class="btn btn-outline-light fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
            >
                <font-awesome-icon :icon="['fas', 'user-astronaut']" class="small text-warning" />
                <span>Ir para Área do Participante</span>
            </Link>
        </div>

        <div class="text-center">
            <Link href="#" class="text-secondary text-decoration-none small">
                <font-awesome-icon :icon="['fas', 'house']" class="me-1" />
                Página Inicial do Motirõ
            </Link>
        </div>
    </div>
</template>
