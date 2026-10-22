<script setup>
import { useForm, Head, Link } from "@inertiajs/vue3";

defineProps({
    status: String,
});

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.email"));
};
</script>

<template>
    <Head title="Recuperar Senha | Motirõ" />

    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light py-5">
        <div class="card shadow-sm border-0 rounded-4 p-4 p-md-5 auth-card">
            <div class="text-center mb-4">
                <Link href="#" class="text-decoration-none text-dark">
                    <h2 class="fw-bold tracking-tight mb-1">Motirõ</h2>
                </Link>
                <p class="text-muted small mb-0">Digite seu e-mail para receber as instruções de redefinição.</p>
            </div>

            <div v-if="status" class="alert alert-success small py-2" role="alert">
                {{ status }}
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
                        autofocus
                    />
                    <div class="invalid-feedback" v-if="form.errors.email">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div class="d-grid mb-3">
                    <button
                        type="submit"
                        class="btn btn-primary fw-semibold py-2"
                        :disabled="form.processing"
                    >
                        Enviar link de redefinição
                    </button>
                </div>

                <div class="text-center">
                    <Link href="#" class="text-decoration-none small text-muted">
                        &larr; Voltar para a página inicial
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
