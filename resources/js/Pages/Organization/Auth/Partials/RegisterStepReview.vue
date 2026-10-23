<script setup>
defineProps({
    currentUser: {
        type: Object,
        default: null,
    },
    orgForm: {
        type: Object,
        required: true,
    },
    logoPreview: {
        type: String,
        default: null,
    },
});

defineEmits(["back", "editOrg", "submit"]);
</script>

<template>
    <div class="animate__animated animate__fadeIn">
        <div class="mb-3 text-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill small mb-2 fw-semibold">
                Etapa 3 de 3 &bull; Revisão e Confirmação
            </span>
            <h2 class="h5 fw-bold text-white mb-1">Revise os dados antes de oficializar</h2>
            <p class="text-white small mb-0">
                Confira as informações da sua conta e da instituição antes de concluir a criação.
            </p>
        </div>

        <div class="d-flex flex-column gap-3 mb-4">
            <!-- User Summary Card -->
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary-subtle">
                        <span class="text-uppercase text-white fw-bold small summary-label">
                            Sua Conta Pessoal (Responsável)
                        </span>
                        <span class="badge bg-success bg-opacity-15 text-success small">Proprietário</span>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary bg-opacity-25 text-white d-flex align-items-center justify-content-center flex-shrink-0 summary-avatar">
                            <font-awesome-icon :icon="['fas', 'user']" class="fs-5" />
                        </div>
                        <div>
                            <h3 class="h6 fw-bold text-white mb-0">{{ currentUser?.name }}</h3>
                            <span class="text-white small">{{ currentUser?.email }}</span>
                        </div>
                    </div>
                    <div class="mt-2 pt-2 border-top border-secondary-subtle text-white small summary-note">
                        Seu e-mail de login continuará o mesmo. Você poderá utilizar esta mesma conta para atuar como participante e gerenciar outras organizações.
                    </div>
                </div>
            </div>

            <!-- Org Summary Card -->
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary-subtle">
                        <span class="text-uppercase text-white fw-bold small summary-label">
                            Dados da Organização
                        </span>
                        <button
                            type="button"
                            class="btn btn-link link-info text-decoration-none p-0 small fw-semibold"
                            @click="$emit('editOrg')"
                        >
                            Editar dados
                        </button>
                    </div>

                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0 summary-logo">
                            <img v-if="logoPreview" :src="logoPreview" alt="Logo preview" class="img-fluid object-fit-cover w-100 h-100" />
                            <font-awesome-icon v-else :icon="['fas', 'building']" class="fs-4 text-primary" />
                        </div>
                        <div class="overflow-hidden">
                            <h3 class="h6 fw-bold text-white mb-0 text-truncate">{{ orgForm.organization_name }}</h3>
                            <span class="badge bg-primary text-white small mt-1">{{ orgForm.organization_type }}</span>
                        </div>
                    </div>

                    <div class="row g-2 small text-light text-opacity-75">
                        <div v-if="orgForm.organization_email" class="col-12 col-sm-6">
                            <strong class="text-white d-block">E-mail Institucional:</strong>
                            <span>{{ orgForm.organization_email }}</span>
                        </div>
                        <div v-if="orgForm.website" class="col-12 col-sm-6">
                            <strong class="text-white d-block">Website:</strong>
                            <span class="text-truncate d-block">{{ orgForm.website }}</span>
                        </div>
                        <div v-if="orgForm.description" class="col-12 mt-2 pt-2 border-top border-secondary-subtle">
                            <strong class="text-white d-block">Descrição:</strong>
                            <p class="mb-0 text-white small">{{ orgForm.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="d-flex justify-content-between align-items-center gap-2">
            <button
                type="button"
                class="btn btn-outline-secondary text-white"
                :disabled="orgForm.processing"
                @click="$emit('back')"
            >
                &larr; Voltar
            </button>

            <button
                type="button"
                class="btn btn-success fw-semibold px-4 py-2 d-flex align-items-center gap-2 shadow-sm"
                :disabled="orgForm.processing"
                @click="$emit('submit')"
            >
                <span v-if="orgForm.processing" class="spinner-border spinner-border-sm" role="status"></span>
                <span>{{ orgForm.processing ? 'Criando organização...' : 'Confirmar e Criar Organização' }}</span>
                <font-awesome-icon v-if="!orgForm.processing" :icon="['fas', 'circle-check']" />
            </button>
        </div>
    </div>
</template>
