<script setup>
import { useForm, Head, Link } from "@inertiajs/vue3";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});
</script>

<template>
    <Head>
        <title>Registre-se</title>
    </Head>

    <form @submit.prevent="form.post(route('register.store'))">
        <div class="mb-2">
            <label class="form-label fw-semibold" for="name">Nome</label>
            <input
                id="name"
                type="text"
                v-model="form.name"
                class="form-control"
                required
                autocomplete="name"
            />
            <span v-if="form.errors.name" class="text-danger small">{{
                form.errors.name
            }}</span>
        </div>

        <!-- Email -->
        <div class="mb-2">
            <label class="form-label fw-semibold" for="email">Email</label>
            <input
                id="email"
                type="email"
                v-model="form.email"
                class="form-control"
                required
                autocomplete="email"
            />
            <span v-if="form.errors.email" class="text-danger small">{{
                form.errors.email
            }}</span>
        </div>

        <!-- password -->
        <div class="mb-2">
            <label class="form-label fw-semibold" for="password">Senha</label>
            <input
                id="password"
                type="password"
                v-model="form.password"
                class="form-control"
                required
            />
            <span v-if="form.errors.password" class="text-danger small">{{
                form.errors.password
            }}</span>
        </div>
        <!-- Confirmar password -->
        <div class="mb-2">
            <label class="form-label fw-semibold" for="password_confirmation"
                >Confirme sua senha</label
            >
            <input
                id="password_confirmation"
                type="password"
                v-model="form.password_confirmation"
                class="form-control"
                required
            />
            <span
                v-if="form.errors.password_confirmation"
                class="text-danger small"
            >
                {{ form.errors.password_confirmation }}
            </span>
        </div>

        <!-- Termos -->
        <div class="form-check mb-3">
            <input
                type="checkbox"
                class="form-check-input"
                id="term"
                required
            />
            <label class="form-check-label small text-muted" for="term">
                Li e concordo com os
                <a href="#" target="_blank">Termos de Uso</a> e
                <a href="#" target="_blank">Política de Privacidade</a>
            </label>
        </div>

        <div class="d-grid mb-3">
            <button
                type="submit"
                class="btn btn-primary fw-semibold py-2 text-white"
                :disabled="form.processing"
            >
                Registre-se
            </button>
        </div>
    </form>

    <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1" />
        <span class="mx-2 text-muted small">ou registre-se com</span>
        <hr class="flex-grow-1" />
    </div>

    <div class="d-flex justify-content-center gap-3 mb-3">
        <a
            href="/auth/google/redirect"
            class="btn btn-light border d-flex align-items-center gap-2 px-3 py-2 shadow-sm disabled"
        >
            <font-awesome-icon
                icon="fa-brands fa-google"
                class="text-danger fs-5"
            />
            <span class="d-none d-sm-inline fw-semibold text-dark">Google</span>
        </a>
    </div>

    <div class="text-center small">
        <Link :href="route('login')" class="text-decoration-none fw-semibold">
            Já tem uma conta?
            <span class="text-dark">Faça login.</span>
        </Link>
        <br />
        <Link :href="route('home')" class="text-decoration-none fw-semibold">
            <font-awesome-icon icon="fa-solid fa-door-open" class="me-1" />
            Voltar para a Home
        </Link>
    </div>
</template>
