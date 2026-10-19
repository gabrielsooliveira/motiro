<script setup>
import { ref } from "vue";
import { useForm, Head, Link } from "@inertiajs/vue3";

const isView = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
    context: "participant",
});

const toggleView = () => {
    isView.value = !isView.value;
};
</script>

<template>
    <Head>
        <title>Área do Participante | Login</title>
    </Head>

    <div class="mb-4 text-center">
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 rounded-pill small mb-2">
            <font-awesome-icon :icon="['fas', 'user-astronaut']" class="me-1" />
            Portal do Participante
        </span>
        <h2 class="h3 fw-bold text-white mb-1">Entrar como Participante</h2>
        <p class="text-white small mb-0">
            Acesse para encontrar hackathons, montar equipes e submeter seus projetos.
        </p>
    </div>

    
    <div v-if="form.errors.email || $page.props.flash?.error" class="alert alert-danger py-2 px-3 small rounded-3 mb-3" role="alert">
        {{ form.errors.email || $page.props.flash?.error }}
    </div>

    <form @submit.prevent="form.post(route('participant.login.store'))">
        <div class="mb-3">
            <label class="form-label fw-semibold text-white small" for="email">E-mail</label>
            <input
                type="email"
                id="email"
                v-model="form.email"
                class="form-control"
                :class="{ 'is-invalid': form.errors.email }"
                placeholder="seu.email@exemplo.com"
                required
                autocomplete="email"
            />
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold text-white small" for="password">Senha</label>
            <div class="input-group">
                <input
                    :type="isView ? 'text' : 'password'"
                    id="password"
                    v-model="form.password"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.password }"
                    placeholder="Sua senha secreta"
                    required
                    autocomplete="current-password"
                />
                <button
                    type="button"
                    class="btn btn-outline-secondary text-white"
                    @click="toggleView"
                >
                    <font-awesome-icon
                        :icon="isView ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"
                    />
                </button>
            </div>
            <div v-if="form.errors.password" class="text-danger small mt-1">
                {{ form.errors.password }}
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="remember"
                    v-model="form.remember"
                />
                <label class="form-check-label text-white small" for="remember">
                    Lembrar de mim
                </label>
            </div>
            <Link
                class="text-decoration-none text-warning small fw-semibold"
                :href="route('participant.password.request')"
            >
                Esqueceu sua senha?
            </Link>
        </div>

        <div class="d-grid mb-3">
            <button
                type="submit"
                class="btn btn-primary fw-semibold py-2"
                :disabled="form.processing"
            >
                <span v-if="form.processing">Entrando...</span>
                <span v-else>Entrar no Portal do Participante</span>
            </button>
        </div>
    </form>

    <div class="text-center pt-2 border-top border-secondary-subtle">
        <p class="small text-white mb-2">
            Ainda não tem conta de participante?
            <Link
                :href="route('participant.register')"
                class="text-decoration-none fw-semibold text-warning"
            >
                Criar conta gratuita
            </Link>
        </p>
        <p class="small text-white mb-3">
            É gestor ou representa uma instituição?
            <Link
                :href="route('organization.login')"
                class="text-decoration-none fw-semibold text-info"
            >
                Ir para Área da Organização
            </Link>
        </p>

        <Link
            :href="route('home')"
            class="text-decoration-none text-white small"
        >
            <font-awesome-icon icon="fa-solid fa-arrow-left" class="me-1" />
            Voltar para a página inicial
        </Link>
    </div>
</template>
