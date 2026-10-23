<script setup>
import { useForm, Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import PasswordStrength from "@/js/Components/PasswordStrength.vue";

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const submit = () => {
    form.post(route("participant.register.store"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head>
        <title>Área do Participante | Criar Conta</title>
    </Head>

    <div class="mb-4 text-center">
        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 rounded-pill small mb-2">
            <font-awesome-icon :icon="['fas', 'user-astronaut']" class="me-1" />
            Portal do Participante
        </span>
        <h2 class="h3 fw-bold text-white mb-1">Crie sua Conta de Participante</h2>
        <p class="text-white text-opacity-75 small mb-0">
            Descubra desafios, monte equipes multidisciplinares e submeta projetos inovadores.
        </p>
    </div>

    <form @submit.prevent="submit" class="auth-form">
        <!-- Nome Completo -->
        <div class="mb-3">
            <label class="form-label fw-semibold text-white small" for="name">Nome Completo *</label>
            <input
                id="name"
                type="text"
                v-model="form.name"
                class="form-control"
                :class="{ 'is-invalid': form.errors.name }"
                placeholder="ex: Ana Clara Santos"
                required
                autofocus
            />
            <div v-if="form.errors.name" class="invalid-feedback d-block">
                {{ form.errors.name }}
            </div>
        </div>

        <!-- E-mail -->
        <div class="mb-3">
            <label class="form-label fw-semibold text-white small" for="email">E-mail *</label>
            <input
                id="email"
                type="email"
                v-model="form.email"
                class="form-control"
                :class="{ 'is-invalid': form.errors.email }"
                placeholder="seu.email@exemplo.com"
                required
                autocomplete="email"
            />
            <div v-if="form.errors.email" class="invalid-feedback d-block">
                {{ form.errors.email }}
            </div>
        </div>

        <!-- Senha e Confirmação -->
        <div class="row g-2 mb-3">
            <div class="col-12 col-sm-6">
                <label class="form-label fw-semibold text-white small" for="password">Senha *</label>
                <div class="input-group">
                    <input
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.password }"
                        placeholder="Mínimo 6 dígitos"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary text-white"
                        @click="showPassword = !showPassword"
                        tabindex="-1"
                        aria-label="Alternar visibilidade da senha"
                    >
                        <font-awesome-icon :icon="showPassword ? ['fas', 'eye-slash'] : ['fas', 'eye']" />
                    </button>
                </div>

                <!-- Password Strength Meter (Bootstrap 6 style) -->
                <PasswordStrength :password="form.password" />

                <div v-if="form.errors.password" class="invalid-feedback d-block mt-1">
                    {{ form.errors.password }}
                </div>
            </div>

            <div class="col-12 col-sm-6">
                <label class="form-label fw-semibold text-white small" for="password_confirmation">Confirmar Senha *</label>
                <div class="input-group">
                    <input
                        id="password_confirmation"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        class="form-control"
                        placeholder="Repita sua senha"
                        required
                        autocomplete="new-password"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary text-white"
                        @click="showConfirmPassword = !showConfirmPassword"
                        tabindex="-1"
                        aria-label="Alternar visibilidade da confirmação de senha"
                    >
                        <font-awesome-icon :icon="showConfirmPassword ? ['fas', 'eye-slash'] : ['fas', 'eye']" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Termos de Uso e LGPD -->
        <div class="form-check mb-4">
            <input
                type="checkbox"
                v-model="form.terms"
                class="form-check-input"
                id="terms"
                required
            />
            <label class="form-check-label text-white text-opacity-75 small" for="terms">
                Concordo com os <a href="#" class="text-warning text-decoration-underline">Termos de Uso</a> e autorizo o tratamento de dados conforme a <a href="#" class="text-warning text-decoration-underline">Política de Privacidade (LGPD)</a>.
            </label>
            <div v-if="form.errors.terms" class="invalid-feedback d-block">
                {{ form.errors.terms }}
            </div>
        </div>

        <!-- Botão de Cadastro -->
        <div class="d-grid mb-3">
            <button
                type="submit"
                class="btn btn-primary fw-semibold py-2 d-flex align-items-center justify-content-center gap-2"
                :disabled="form.processing"
            >
                <span v-if="form.processing" class="spinner-border spinner-border-sm" role="status"></span>
                <span>{{ form.processing ? "Criando conta..." : "Criar Conta de Participante" }}</span>
                <font-awesome-icon v-if="!form.processing" :icon="['fas', 'arrow-right']" class="small" />
            </button>
        </div>
    </form>

    <!-- Links de Navegação e Contexto -->
    <div class="text-center pt-3 border-top border-secondary-subtle">
        <p class="small text-white mb-2">
            Já possui uma conta no Motirõ?
            <Link
                :href="route('participant.login')"
                class="text-decoration-none fw-semibold text-warning"
            >
                Fazer login
            </Link>
        </p>

        <p class="small text-white mb-3">
            Representa uma instituição ou deseja organizar um evento?
            <Link
                :href="route('organization.register')"
                class="text-decoration-none fw-semibold text-info"
            >
                Cadastrar Organização
            </Link>
        </p>

        <Link
            :href="route('home')"
            class="text-decoration-none text-white text-opacity-75 small"
        >
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
            Voltar para a página inicial
        </Link>
    </div>
</template>
