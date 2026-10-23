<script setup>
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    invitation: {
        type: Object,
        required: true,
    },
});

const invitationForm = useForm({
    invitation_token: props.invitation?.token || "",
    name: "",
    password: "",
    password_confirmation: "",
});

const submitInvitation = () => {
    invitationForm.post(route("organization.register.store"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="card bg-dark border-secondary shadow-sm text-white mb-3">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <div class="icon-bubble icon-bubble--lg bg-info bg-opacity-15 text-info mx-auto mb-2 d-flex align-items-center justify-content-center rounded-circle">
                    <font-awesome-icon :icon="['fas', 'envelope-open-text']" class="fs-4 text-info" />
                </div>
                <span class="badge bg-info text-dark small fw-semibold mb-2">Convite Institucional</span>
                <h2 class="h5 fw-bold text-white mb-1">Você foi convidado(a)!</h2>
                <p class="text-light text-opacity-75 small mb-0">
                    A instituição <strong class="text-white">{{ invitation.organization_name }}</strong> convidou você para colaborar como
                    <strong class="text-info">{{ invitation.role }}</strong>.
                </p>
            </div>

            <form @submit.prevent="submitInvitation">
                <div class="mb-3">
                    <label class="form-label text-white small fw-semibold" for="inv_email">Seu E-mail</label>
                    <input
                        id="inv_email"
                        type="email"
                        :value="invitation.email"
                        class="form-control bg-dark border-secondary text-white"
                        readonly
                        disabled
                    />
                    <div class="form-text text-white small">
                        O acesso está vinculado ao e-mail deste convite.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-semibold" for="inv_name">Seu Nome Completo *</label>
                    <input
                        id="inv_name"
                        v-model="invitationForm.name"
                        type="text"
                        class="form-control"
                        placeholder="Como deseja ser chamado(a)"
                        required
                    />
                    <span v-if="invitationForm.errors.name" class="text-danger small">{{ invitationForm.errors.name }}</span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label text-white small fw-semibold" for="inv_pass">Senha *</label>
                        <input
                            id="inv_pass"
                            v-model="invitationForm.password"
                            type="password"
                            class="form-control"
                            placeholder="Mínimo 6 caracteres"
                            required
                        />
                        <span v-if="invitationForm.errors.password" class="text-danger small">{{ invitationForm.errors.password }}</span>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label class="form-label text-white small fw-semibold" for="inv_conf">Confirmar Senha *</label>
                        <input
                            id="inv_conf"
                            v-model="invitationForm.password_confirmation"
                            type="password"
                            class="form-control"
                            placeholder="Repita sua senha"
                            required
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                    :disabled="invitationForm.processing"
                >
                    <span v-if="invitationForm.processing">Processando...</span>
                    <span v-else>Aceitar Convite e Entrar na Equipe</span>
                </button>
            </form>
        </div>
    </div>
</template>
