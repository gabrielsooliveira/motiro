<script setup>
import { useForm, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import PasswordStrength from "@/js/Components/PasswordStrength.vue";

const emit = defineEmits(["authenticated"]);

const authMode = ref("register");

const showLoginPassword = ref(false);
const showAccountPassword = ref(false);
const showAccountConfirmPassword = ref(false);

const loginForm = useForm({
    email: "",
    password: "",
    remember: false,
});

const accountForm = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const handleAuthSuccess = (page) => {
    const user = page.props.auth?.user || page.props.user;
    if (user) {
        emit("authenticated", user);
    }
};

const submitLogin = () => {
    loginForm.post(route("organization.register.login"), {
        preserveScroll: true,
        onSuccess: handleAuthSuccess,
    });
};

const submitAccount = () => {
    accountForm.post(route("organization.register.account"), {
        preserveScroll: true,
        onSuccess: handleAuthSuccess,
    });
};

const switchToLoginWithEmail = () => {
    authMode.value = "login";
    loginForm.email = accountForm.email;
};
</script>

<template>
    <div class="animate__animated animate__fadeIn">
        <div class="mb-3 text-center">
            <h2 class="h5 fw-bold text-white mb-1">Identidade Pessoal no Motirõ</h2>
            <p class="text-white small mb-0">
                Cada pessoa tem uma única conta no Motirõ para participar e gerenciar hackathons. Acesse ou crie sua conta para começar.
            </p>
        </div>

        <div class="d-flex bg-dark border border-secondary rounded-3 p-1 mb-3 gap-1">
            <button
                type="button"
                class="btn btn-sm flex-fill fw-semibold rounded-2"
                :class="authMode === 'register' ? 'btn-primary text-white shadow-sm' : 'btn-dark text-white border-0'"
                @click="authMode = 'register'"
            >
                Quero criar minha conta
            </button>
            <button
                type="button"
                class="btn btn-sm flex-fill fw-semibold rounded-2"
                :class="authMode === 'login' ? 'btn-primary text-white shadow-sm' : 'btn-dark text-white border-0'"
                @click="authMode = 'login'"
            >
                Já tenho uma conta
            </button>
        </div>

        <div v-if="authMode === 'login'">
            <div
                v-if="loginForm.errors.login_email || loginForm.errors.email"
                class="alert alert-danger py-2 px-3 small rounded-3 mb-3"
                role="alert"
            >
                {{ loginForm.errors.login_email || loginForm.errors.email }}
            </div>

            <form @submit.prevent="submitLogin">
                <div class="mb-3">
                    <label class="form-label text-white small fw-semibold" for="login_email">
                        E-mail de Login *
                    </label>
                    <input
                        id="login_email"
                        v-model="loginForm.email"
                        type="email"
                        class="form-control"
                        :class="{ 'is-invalid': loginForm.errors.login_email || loginForm.errors.email }"
                        placeholder="seu@email.com"
                        required
                        autocomplete="email"
                    />
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label text-white small fw-semibold mb-0" for="login_pass">
                            Sua Senha *
                        </label>
                        <Link
                            :href="route('organization.password.request')"
                            class="text-info small text-decoration-none"
                            target="_blank"
                        >
                            Esqueceu a senha?
                        </Link>
                    </div>
                    <div class="input-group">
                        <input
                            id="login_pass"
                            v-model="loginForm.password"
                            :type="showLoginPassword ? 'text' : 'password'"
                            class="form-control"
                            :class="{ 'is-invalid': loginForm.errors.password }"
                            placeholder="Sua senha secreta"
                            required
                            autocomplete="current-password"
                        />
                        <button
                            type="button"
                            class="btn btn-outline-secondary text-white"
                            @click="showLoginPassword = !showLoginPassword"
                        >
                            <font-awesome-icon :icon="showLoginPassword ? ['fas', 'eye-slash'] : ['fas', 'eye']" />
                        </button>
                    </div>
                    <span v-if="loginForm.errors.password" class="text-danger small mt-1 d-block">
                        {{ loginForm.errors.password }}
                    </span>
                </div>

                <div class="form-check mb-3">
                    <input
                        id="remember_me"
                        v-model="loginForm.remember"
                        type="checkbox"
                        class="form-check-input"
                    />
                    <label class="form-check-label text-white small" for="remember_me">
                        Lembrar de mim neste dispositivo
                    </label>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 mb-3 shadow-sm"
                    :disabled="loginForm.processing"
                >
                    <span v-if="loginForm.processing" class="spinner-border spinner-border-sm" role="status"></span>
                    <span>{{ loginForm.processing ? 'Verificando...' : 'Entrar e Continuar' }}</span>
                    <font-awesome-icon v-if="!loginForm.processing" :icon="['fas', 'arrow-right']" class="small" />
                </button>
            </form>
        </div>

        <!-- Register Form -->
        <div v-else>
            <div
                v-if="accountForm.errors.email"
                class="alert alert-warning py-2 px-3 small rounded-3 mb-3 d-flex align-items-start gap-2"
                role="alert"
            >
                <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="mt-1 flex-shrink-0" />
                <div>
                    <span>{{ accountForm.errors.email }}</span>
                    <div class="mt-1">
                        <button
                            type="button"
                            class="btn btn-link link-dark text-decoration-underline p-0 small fw-bold"
                            @click="switchToLoginWithEmail"
                        >
                            Fazer login com este e-mail
                        </button>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submitAccount">
                <div class="mb-3">
                    <label class="form-label text-white small fw-semibold" for="acc_name">
                        Nome Completo *
                    </label>
                    <input
                        id="acc_name"
                        v-model="accountForm.name"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': accountForm.errors.name }"
                        placeholder="Seu nome pessoal"
                        required
                        autocomplete="name"
                    />
                    <span v-if="accountForm.errors.name" class="text-danger small mt-1 d-block">
                        {{ accountForm.errors.name }}
                    </span>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white small fw-semibold" for="acc_email">
                        E-mail Pessoal (Login) *
                    </label>
                    <input
                        id="acc_email"
                        v-model="accountForm.email"
                        type="email"
                        class="form-control"
                        :class="{ 'is-invalid': accountForm.errors.email }"
                        placeholder="seu@email.com"
                        required
                        autocomplete="email"
                    />
                    <div class="form-text text-white small">
                        Este e-mail será sua credencial de acesso no Motirõ.
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-6">
                        <label class="form-label text-white small fw-semibold" for="acc_pass">
                            Senha *
                        </label>
                        <div class="input-group">
                            <input
                                id="acc_pass"
                                v-model="accountForm.password"
                                :type="showAccountPassword ? 'text' : 'password'"
                                class="form-control"
                                :class="{ 'is-invalid': accountForm.errors.password }"
                                placeholder="Mínimo 6 dígitos"
                                required
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="btn btn-outline-secondary text-white"
                                @click="showAccountPassword = !showAccountPassword"
                            >
                                <font-awesome-icon :icon="showAccountPassword ? ['fas', 'eye-slash'] : ['fas', 'eye']" />
                            </button>
                        </div>

                        <!-- Bootstrap Password Strength Meter -->
                        <PasswordStrength :password="accountForm.password" />

                        <span v-if="accountForm.errors.password" class="text-danger small mt-1 d-block">
                            {{ accountForm.errors.password }}
                        </span>
                    </div>

                    <div class="col-12 col-sm-6">
                        <label class="form-label text-white small fw-semibold" for="acc_conf">
                            Confirmar Senha *
                        </label>
                        <div class="input-group">
                            <input
                                id="acc_conf"
                                v-model="accountForm.password_confirmation"
                                :type="showAccountConfirmPassword ? 'text' : 'password'"
                                class="form-control"
                                placeholder="Repita sua senha"
                                required
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                class="btn btn-outline-secondary text-white"
                                @click="showAccountConfirmPassword = !showAccountConfirmPassword"
                            >
                                <font-awesome-icon :icon="showAccountConfirmPassword ? ['fas', 'eye-slash'] : ['fas', 'eye']" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input
                        id="acc_terms"
                        v-model="accountForm.terms"
                        type="checkbox"
                        class="form-check-input"
                        :class="{ 'is-invalid': accountForm.errors.terms }"
                        required
                    />
                    <label class="form-check-label text-light text-opacity-75 small" for="acc_terms">
                        Concordo com os Termos de Uso e Política de Privacidade (LGPD).
                    </label>
                    <span v-if="accountForm.errors.terms" class="text-danger small d-block">
                        {{ accountForm.errors.terms }}
                    </span>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary w-100 fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 mb-3 shadow-sm"
                    :disabled="accountForm.processing"
                >
                    <span v-if="accountForm.processing" class="spinner-border spinner-border-sm" role="status"></span>
                    <span>{{ accountForm.processing ? 'Criando conta...' : 'Criar Conta Pessoal e Continuar' }}</span>
                    <font-awesome-icon v-if="!accountForm.processing" :icon="['fas', 'arrow-right']" class="small" />
                </button>
            </form>
        </div>
    </div>
</template>
