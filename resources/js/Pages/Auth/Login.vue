<script setup>
import { ref } from "vue";
import { useForm, Head, Link } from "@inertiajs/vue3";

const isView = ref(false);

const form = useForm({
    email: "",
    password: "",
});

const toggleView = () => {
    isView.value = !isView.value;
};
</script>

<template>
    <Head title="Login" />

    <form @submit.prevent="form.post(route('login.store'))">
        <div class="mb-3">
            <label class="form-label fw-semibold" for="email">Email</label>
            <input
                type="email"
                id="email"
                v-model="form.email"
                class="form-control"
                required
                autocomplete="email"
            />
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold" for="password">Senha</label>
            <div class="input-group">
                <input
                    :type="isView ? 'text' : 'password'"
                    id="password"
                    v-model="form.password"
                    class="form-control"
                    required
                />
                <button
                    type="button"
                    class="btn btn-primary text-white"
                    @click="toggleView"
                >
                    <font-awesome-icon
                        :icon="
                            isView ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'
                        "
                    />
                </button>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <Link class="text-decoration-none link-thirdary small" href="#"
                >Esqueceu sua senha?</Link
            >
        </div>

        <div class="d-grid mb-2">
            <button
                type="submit"
                class="btn btn-primary fw-semibold py-2"
                :disabled="form.processing"
            >
                Entrar
            </button>
        </div>

        <p class="text-center small mb-4">
            Ao fazer login você concorda com os
            <a href="#" class="text-thirdary" target="_blank">Termos de Uso</a>
            e
            <a href="#" class="text-thirdary" target="_blank"
                >Política de Privacidade</a
            >.
        </p>
    </form>

    <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1" />
        <span class="mx-2 small">ou continue com</span>
        <hr class="flex-grow-1" />
    </div>

    <div class="d-flex justify-content-center gap-3 mb-4">
        <a
            href="/auth/google/redirect"
            class="btn btn-light border d-flex align-items-center gap-2 px-3 py-2 shadow-sm disabled"
        >
            <font-awesome-icon
                icon="fa-brands fa-google"
                class="text-danger fs-5"
            />
            <span class="fw-semibold d-none d-sm-inline text-dark">Google</span>
        </a>
    </div>

    <div class="text-center">
        <Link
            :href="route('register')"
            class="text-decoration-none fw-semibold link-light"
        >
            Ainda não possui uma conta?
            <span class="text-thirdary">Registre-se</span>
        </Link>
        <br />
        <Link
            :href="route('home')"
            class="text-decoration-none fw-semibold link-light"
        >
            <font-awesome-icon icon="fa-solid fa-door-open" class="me-1" />
            Voltar para a Home
        </Link>
    </div>
</template>
