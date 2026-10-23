<script setup>
import { ref } from "vue";
import { useForm, Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        default: "",
    },
});

const isViewPassword = ref(false);
const isViewConfirm = ref(false);

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("password.update"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Redefinir Senha | Motirõ" />

    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light py-5">
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 auth-card">
            <div class="text-center mb-4">
                <Link :href="route('home')" class="text-decoration-none text-dark">
                    <h2 class="fw-bold tracking-tight mb-1">Motirõ</h2>
                </Link>
                <p class="text-muted small mb-0">Crie uma nova senha de acesso.</p>
            </div>

            <form @submit.prevent="submit">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        v-model="form.email"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.email }"
                        required
                        autocomplete="email"
                    />
                    <div class="invalid-feedback" v-if="form.errors.email">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" for="password">Nova Senha</label>
                    <div class="input-group">
                        <input
                            :type="isViewPassword ? 'text' : 'password'"
                            id="password"
                            v-model="form.password"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.password }"
                            required
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @click="isViewPassword = !isViewPassword"
                        >
                            <font-awesome-icon :icon="isViewPassword ? ['fas', 'eye-slash'] : ['fas', 'eye']" />
                        </button>
                        <div class="invalid-feedback" v-if="form.errors.password">
                            {{ form.errors.password }}
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold" for="password_confirmation">Confirmar Nova Senha</label>
                    <div class="input-group">
                        <input
                            :type="isViewConfirm ? 'text' : 'password'"
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            class="form-control"
                            required
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            @click="isViewConfirm = !isViewConfirm"
                        >
                            <font-awesome-icon :icon="isViewConfirm ? ['fas', 'eye-slash'] : ['fas', 'eye']" />
                        </button>
                    </div>
                </div>

                <div class="d-grid mb-3">
                    <button
                        type="submit"
                        class="btn btn-primary fw-semibold py-2"
                        :disabled="form.processing"
                    >
                        Redefinir senha
                    </button>
                </div>

                <div class="text-center">
                    <Link :href="route('home')" class="text-decoration-none small text-muted">
                        &larr; Voltar para a página inicial
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
