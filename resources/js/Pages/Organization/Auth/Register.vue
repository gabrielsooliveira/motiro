<script setup>
import { useForm, Head, Link, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    authUser: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [
            "Empresa",
            "Universidade ou instituição de ensino",
            "Comunidade ou coletivo",
            "Organização social / Sem fins lucrativos",
            "Instituição pública",
            "Startup",
            "Outra",
        ],
    },
    invitation: {
        type: Object,
        default: null,
    },
});

// Usuário autenticado ativo na sessão
const currentUser = ref(props.authUser || null);

// Etapa atual: se já estiver autenticado, inicia diretamente na Etapa 2
const currentStep = ref(props.authUser ? 2 : 1);

// Modo na Etapa 1: 'register' (criar conta) ou 'login' (já tenho conta)
const authMode = ref("register");

// Visualizador de senha
const showLoginPassword = ref(false);
const showAccountPassword = ref(false);
const showAccountConfirmPassword = ref(false);

// Form de Login pessoal (Etapa 1 — Opção A)
const loginForm = useForm({
    email: "",
    password: "",
    remember: false,
});

// Form de Criação de conta pessoal (Etapa 1 — Opção B)
const accountForm = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

// Form de Dados da Organização (Etapa 2 e 3)
const orgForm = useForm({
    organization_name: "",
    organization_type: props.categories[0] || "Empresa",
    organization_email: "",
    website: "",
    description: "",
    logo: null,
});

// Form para aceitação de convite
const invitationForm = useForm({
    invitation_token: props.invitation?.token || "",
    name: "",
    password: "",
    password_confirmation: "",
});

// Preview do logotipo
const logoPreview = ref(null);
const logoError = ref("");

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    logoError.value = "";
    if (file) {
        if (!file.type.startsWith("image/")) {
            logoError.value = "Por favor, selecione um arquivo de imagem válido (PNG, JPG, SVG ou WEBP).";
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            logoError.value = "O logotipo deve ter no máximo 2MB.";
            return;
        }
        orgForm.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    orgForm.logo = null;
    logoPreview.value = null;
};

// Erros locais de validação na Etapa 2
const step2Errors = ref({});

const validateStep2 = () => {
    step2Errors.value = {};
    if (!orgForm.organization_name || !orgForm.organization_name.trim()) {
        step2Errors.value.organization_name = "O nome da organização é obrigatório.";
    }
    if (!orgForm.organization_type) {
        step2Errors.value.organization_type = "Selecione o tipo de organização.";
    }
    if (orgForm.organization_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(orgForm.organization_email)) {
        step2Errors.value.organization_email = "Informe um endereço de e-mail institucional válido.";
    }
    if (orgForm.website && !/^https?:\/\/.+/.test(orgForm.website)) {
        step2Errors.value.website = "Informe uma URL válida (ex: https://suaorganizacao.com).";
    }

    return Object.keys(step2Errors.value).length === 0;
};

const goToStep3 = () => {
    if (validateStep2()) {
        currentStep.value = 3;
    }
};

const goToStep2 = () => {
    currentStep.value = 2;
};

const goToStep1 = () => {
    currentStep.value = 1;
};

// Submissão do Login pessoal (Etapa 1 - Opção A)
const submitLogin = () => {
    loginForm.post(route("organization.register.login"), {
        preserveScroll: true,
        onSuccess: (page) => {
            const user = page.props.auth?.user || page.props.user;
            if (user) {
                currentUser.value = user;
                currentStep.value = 2;
            }
        },
    });
};

// Submissão da Criação de conta pessoal (Etapa 1 - Opção B)
const submitAccount = () => {
    accountForm.post(route("organization.register.account"), {
        preserveScroll: true,
        onSuccess: (page) => {
            const user = page.props.auth?.user || page.props.user;
            if (user) {
                currentUser.value = user;
                currentStep.value = 2;
            }
        },
    });
};

// Submissão definitiva da Organização (Etapa 3)
const submitOrganization = () => {
    orgForm.post(route("organization.register.store"), {
        preserveScroll: true,
        forceFormData: true,
    });
};

// Submissão de convite
const submitInvitation = () => {
    invitationForm.post(route("organization.register.store"), {
        preserveScroll: true,
    });
};

const logoutCurrentIdentity = () => {
    router.post(route("logout"), {}, {
        onSuccess: () => {
            currentUser.value = null;
            currentStep.value = 1;
        },
    });
};
</script>

<template>
    <Head title="Cadastrar Organização | Motirõ" />

    <div class="py-2">
        <!-- Logo / Marca -->
        <div class="text-center mb-4">
            <Link :href="route('home')" class="text-decoration-none">
                <h1 class="fw-bold text-white fs-3 mb-1 tracking-tight">Motirõ</h1>
            </Link>
            <p class="text-secondary small mb-0">
                Plataforma de gestão integrada de hackathons e ecossistemas de inovação.
            </p>
        </div>

        <!-- CASO 0: ACEITAÇÃO DE CONVITE INSTITUCIONAL -->
        <div v-if="invitation" class="card bg-dark border-secondary shadow-sm text-white mb-3">
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
                            class="form-control bg-dark border-secondary text-secondary"
                            readonly
                            disabled
                        />
                        <div class="form-text text-secondary small">
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

        <!-- CASO NORMAL: FLUXO DE CADASTRO DE ORGANIZAÇÃO EM ETAPAS -->
        <div v-else>
            <!-- Indicador Visual de Progresso das Etapas -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between position-relative px-2">
                    <!-- Linha de conexão de fundo -->
                    <div
                        class="position-absolute top-50 start-0 end-0 translate-middle-y bg-secondary bg-opacity-25 step-progress-line"
                    ></div>
                    <!-- Linha de progresso ativa -->
                    <div
                        class="position-absolute top-50 start-0 translate-middle-y bg-primary step-progress-line"
                        :style="{
                            width: currentStep === 1 ? '0%' : currentStep === 2 ? '50%' : '100%',
                            transition: 'width 0.3s ease'
                        }"
                    ></div>

                    <!-- Step 1 Indicator -->
                    <div class="d-flex flex-column align-items-center position-relative step-indicator-wrapper">
                        <div
                            class="rounded-circle d-flex align-items-center justify-content-center fw-bold small step-circle"
                            :class="currentStep > 1 ? 'bg-success text-white' : currentStep === 1 ? 'bg-primary text-white shadow' : 'bg-dark border border-secondary text-secondary'"
                        >
                            <font-awesome-icon v-if="currentStep > 1" :icon="['fas', 'check']" class="small" />
                            <span v-else>1</span>
                        </div>
                        <span class="small mt-1 text-nowrap" :class="currentStep === 1 ? 'text-primary fw-bold' : 'text-secondary'">
                            Sua Conta
                        </span>
                    </div>

                    <!-- Step 2 Indicator -->
                    <div class="d-flex flex-column align-items-center position-relative step-indicator-wrapper">
                        <div
                            class="rounded-circle d-flex align-items-center justify-content-center fw-bold small step-circle"
                            :class="currentStep > 2 ? 'bg-success text-white' : currentStep === 2 ? 'bg-primary text-white shadow' : 'bg-dark border border-secondary text-secondary'"
                        >
                            <font-awesome-icon v-if="currentStep > 2" :icon="['fas', 'check']" class="small" />
                            <span v-else>2</span>
                        </div>
                        <span class="small mt-1 text-nowrap" :class="currentStep === 2 ? 'text-primary fw-bold' : 'text-secondary'">
                            Organização
                        </span>
                    </div>

                    <!-- Step 3 Indicator -->
                    <div class="d-flex flex-column align-items-center position-relative step-indicator-wrapper">
                        <div
                            class="rounded-circle d-flex align-items-center justify-content-center fw-bold small step-circle"
                            :class="currentStep === 3 ? 'bg-primary text-white shadow' : 'bg-dark border border-secondary text-secondary'"
                        >
                            <span>3</span>
                        </div>
                        <span class="small mt-1 text-nowrap" :class="currentStep === 3 ? 'text-primary fw-bold' : 'text-secondary'">
                            Revisar
                        </span>
                    </div>
                </div>
            </div>

            <!-- Banner Informativo da Identidade Conectada (se autenticado) -->
            <div
                v-if="currentUser && currentStep >= 2"
                class="card bg-dark border-secondary-subtle p-2 px-3 rounded-3 mb-3 d-flex flex-row align-items-center justify-content-between shadow-sm"
            >
                <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                    <div class="rounded-circle bg-success bg-opacity-20 text-success d-flex align-items-center justify-content-center flex-shrink-0 user-avatar--sm">
                        <font-awesome-icon :icon="['fas', 'user-check']" class="small" />
                    </div>
                    <div class="d-flex flex-column overflow-hidden">
                        <span class="small text-white fw-semibold text-truncate">{{ currentUser.name }}</span>
                        <span class="text-secondary small text-truncate fs-8">{{ currentUser.email }}</span>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary text-light text-nowrap fs-8"
                    @click="logoutCurrentIdentity"
                    title="Alternar para outra conta pessoal"
                >
                    Trocar conta
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- ETAPA 1 — CRIAR OU ACESSAR CONTA PESSOAL                 -->
            <!-- ======================================================== -->
            <div v-if="currentStep === 1" class="animate__animated animate__fadeIn">
                <div class="mb-3 text-center">
                    <h2 class="h5 fw-bold text-white mb-1">Identidade Pessoal no Motirõ</h2>
                    <p class="text-secondary small mb-0">
                        Cada pessoa tem uma única conta no Motirõ para participar e gerenciar hackathons. Acesse ou crie sua conta para começar.
                    </p>
                </div>

                <!-- Tabs de escolha: Quero criar conta vs Já tenho conta -->
                <div class="d-flex bg-dark border border-secondary rounded-3 p-1 mb-3">
                    <button
                        type="button"
                        class="btn btn-sm flex-fill fw-semibold py-2 transition-all rounded-2"
                        :class="authMode === 'register' ? 'btn-primary text-white shadow-sm' : 'btn-dark text-secondary border-0'"
                        @click="authMode = 'register'"
                    >
                        Quero criar minha conta
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm flex-fill fw-semibold py-2 transition-all rounded-2"
                        :class="authMode === 'login' ? 'btn-primary text-white shadow-sm' : 'btn-dark text-secondary border-0'"
                        @click="authMode = 'login'"
                    >
                        Já tenho uma conta
                    </button>
                </div>

                <!-- OPÇÃO A: JÁ TENHO UMA CONTA (LOGIN) -->
                <div v-if="authMode === 'login'">
                    <!-- Alerta de erro de login -->
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
                                    :href="route('password.request')"
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
                            <label class="form-check-label text-secondary small" for="remember_me">
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

                <!-- OPÇÃO B: QUERO CRIAR MINHA CONTA -->
                <div v-else>
                    <!-- Alerta de erro de registro -->
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
                                    @click="authMode = 'login'; loginForm.email = accountForm.email;"
                                >
                                    Fazer login com este e-mail &rarr;
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
                            <div class="form-text text-secondary small">
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

            <!-- ======================================================== -->
            <!-- ETAPA 2 — INFORMAÇÕES DA ORGANIZAÇÃO                      -->
            <!-- ======================================================== -->
            <div v-else-if="currentStep === 2" class="animate__animated animate__fadeIn">
                <div class="mb-3 text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill small mb-2 fw-semibold">
                        Etapa 2 de 3 &bull; Dados Institucionais
                    </span>
                    <h2 class="h5 fw-bold text-white mb-1">Vamos cadastrar sua organização</h2>
                    <p class="text-secondary small mb-0">
                        Agora vamos cadastrar a instituição que você representa. Os dados desta etapa são independentes das informações da sua conta pessoal.
                    </p>
                </div>

                <form @submit.prevent="goToStep3">
                    <!-- Nome da Organização -->
                    <div class="mb-3">
                        <label class="form-label text-white small fw-semibold" for="org_name">
                            Nome da Organização *
                        </label>
                        <input
                            id="org_name"
                            v-model="orgForm.organization_name"
                            type="text"
                            class="form-control"
                            :class="{ 'is-invalid': step2Errors.organization_name || orgForm.errors.organization_name }"
                            placeholder="ex: InovaTech Labs, Universidade XPTO, Comunidade Dev"
                            required
                        />
                        <span v-if="step2Errors.organization_name || orgForm.errors.organization_name" class="text-danger small mt-1 d-block">
                            {{ step2Errors.organization_name || orgForm.errors.organization_name }}
                        </span>
                    </div>

                    <!-- Tipo da Organização -->
                    <div class="mb-3">
                        <label class="form-label text-white small fw-semibold" for="org_type">
                            Tipo de Organização *
                        </label>
                        <select
                            id="org_type"
                            v-model="orgForm.organization_type"
                            class="form-select"
                            :class="{ 'is-invalid': step2Errors.organization_type || orgForm.errors.organization_type }"
                            required
                        >
                            <option v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                        <span v-if="step2Errors.organization_type || orgForm.errors.organization_type" class="text-danger small mt-1 d-block">
                            {{ step2Errors.organization_type || orgForm.errors.organization_type }}
                        </span>
                    </div>

                    <!-- E-mail Institucional ou de Contato -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-white small fw-semibold mb-0" for="org_email">
                                E-mail Institucional ou de Contato
                            </label>
                            <span class="text-secondary small">Opcional</span>
                        </div>
                        <input
                            id="org_email"
                            v-model="orgForm.organization_email"
                            type="email"
                            class="form-control"
                            :class="{ 'is-invalid': step2Errors.organization_email || orgForm.errors.organization_email }"
                            placeholder="contato@organizacao.com"
                        />
                        <div class="form-text text-secondary small">
                            Endereço público da organização. Não altera nem substitui o seu e-mail de login pessoal.
                        </div>
                        <span v-if="step2Errors.organization_email || orgForm.errors.organization_email" class="text-danger small mt-1 d-block">
                            {{ step2Errors.organization_email || orgForm.errors.organization_email }}
                        </span>
                    </div>

                    <!-- Website Oficial -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-white small fw-semibold mb-0" for="org_web">
                                Website Oficial
                            </label>
                            <span class="text-secondary small">Opcional</span>
                        </div>
                        <input
                            id="org_web"
                            v-model="orgForm.website"
                            type="url"
                            class="form-control"
                            :class="{ 'is-invalid': step2Errors.website || orgForm.errors.website }"
                            placeholder="https://suaorganizacao.com"
                        />
                        <span v-if="step2Errors.website || orgForm.errors.website" class="text-danger small mt-1 d-block">
                            {{ step2Errors.website || orgForm.errors.website }}
                        </span>
                    </div>

                    <!-- Logotipo -->
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-white small fw-semibold mb-0" for="org_logo">
                                Logotipo da Instituição
                            </label>
                            <span class="text-secondary small">Opcional</span>
                        </div>
                        <input
                            id="org_logo"
                            type="file"
                            class="form-control"
                            accept="image/*"
                            @change="handleLogoUpload"
                        />
                        <div class="form-text text-secondary small">
                            Formatos aceitos: PNG, JPG, SVG ou WEBP (máx. 2MB).
                        </div>
                        <span v-if="logoError" class="text-danger small mt-1 d-block">
                            {{ logoError }}
                        </span>

                        <!-- Preview do logotipo -->
                        <div v-if="logoPreview" class="d-flex align-items-center gap-3 mt-2 p-2 bg-dark border border-secondary rounded-3">
                            <img :src="logoPreview" alt="Prévia do logotipo" class="rounded object-fit-cover logo-preview" />
                            <div class="flex-grow-1 overflow-hidden">
                                <span class="text-white small fw-semibold d-block text-truncate">Logotipo selecionado</span>
                                <span class="text-secondary small">Pronto para envio</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLogo">
                                Remover
                            </button>
                        </div>
                    </div>

                    <!-- Descrição Institucional -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-white small fw-semibold mb-0" for="org_desc">
                                Descrição Institucional
                            </label>
                            <span class="text-secondary small">Opcional</span>
                        </div>
                        <textarea
                            id="org_desc"
                            v-model="orgForm.description"
                            class="form-control"
                            rows="3"
                            placeholder="Apresente sua instituição, área de atuação e objetivos ao realizar hackathons..."
                        ></textarea>
                    </div>

                    <!-- Botões de navegação -->
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <button
                            v-if="!props.authUser"
                            type="button"
                            class="btn btn-outline-secondary text-white"
                            @click="goToStep1"
                        >
                            &larr; Voltar
                        </button>
                        <div v-else></div>

                        <button
                            type="submit"
                            class="btn btn-primary fw-semibold px-4 py-2 d-flex align-items-center gap-2 shadow-sm"
                        >
                            <span>Avançar para Revisão</span>
                            <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                        </button>
                    </div>
                </form>
            </div>

            <!-- ======================================================== -->
            <!-- ETAPA 3 — REVISAR E CONFIRMAR                            -->
            <!-- ======================================================== -->
            <div v-else-if="currentStep === 3" class="animate__animated animate__fadeIn">
                <div class="mb-3 text-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill small mb-2 fw-semibold">
                        Etapa 3 de 3 &bull; Revisão e Confirmação
                    </span>
                    <h2 class="h5 fw-bold text-white mb-1">Revise os dados antes de oficializar</h2>
                    <p class="text-secondary small mb-0">
                        Confira as informações da sua conta e da instituição antes de concluir a criação.
                    </p>
                </div>

                <!-- Resumo dos dados -->
                <div class="d-flex flex-column gap-3 mb-4">
                    <!-- Bloco 1: Sua Conta Pessoal -->
                    <div class="card bg-dark border-secondary shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary-subtle">
                                <span class="text-uppercase text-secondary fw-bold small summary-label">
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
                                    <span class="text-secondary small">{{ currentUser?.email }}</span>
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-top border-secondary-subtle text-secondary small summary-note">
                                Seu e-mail de login continuará o mesmo. Você poderá utilizar esta mesma conta para atuar como participante e gerenciar outras organizações.
                            </div>
                        </div>
                    </div>

                    <!-- Bloco 2: Sua Organização -->
                    <div class="card bg-dark border-secondary shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom border-secondary-subtle">
                                <span class="text-uppercase text-secondary fw-bold small summary-label">
                                    Dados da Organização
                                </span>
                                <button
                                    type="button"
                                    class="btn btn-link link-info text-decoration-none p-0 small fw-semibold"
                                    @click="goToStep2"
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
                                <div class="col-12 col-sm-6" v-if="orgForm.organization_email">
                                    <strong class="text-white d-block">E-mail Institucional:</strong>
                                    <span>{{ orgForm.organization_email }}</span>
                                </div>
                                <div class="col-12 col-sm-6" v-if="orgForm.website">
                                    <strong class="text-white d-block">Website:</strong>
                                    <span class="text-truncate d-block">{{ orgForm.website }}</span>
                                </div>
                                <div class="col-12 mt-2 pt-2 border-top border-secondary-subtle" v-if="orgForm.description">
                                    <strong class="text-white d-block">Descrição:</strong>
                                    <p class="mb-0 text-secondary small">{{ orgForm.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botões de Confirmação -->
                <div class="d-flex justify-content-between align-items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-outline-secondary text-white"
                        @click="goToStep2"
                        :disabled="orgForm.processing"
                    >
                        &larr; Voltar
                    </button>

                    <button
                        type="button"
                        class="btn btn-success fw-semibold px-4 py-2 d-flex align-items-center gap-2 shadow-sm"
                        :disabled="orgForm.processing"
                        @click="submitOrganization"
                    >
                        <span v-if="orgForm.processing" class="spinner-border spinner-border-sm" role="status"></span>
                        <span>{{ orgForm.processing ? 'Criando organização...' : 'Confirmar e Criar Organização' }}</span>
                        <font-awesome-icon v-if="!orgForm.processing" :icon="['fas', 'circle-check']" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Rodapé simples de navegação -->
        <div class="text-center mt-4 pt-3 border-top border-secondary-subtle">
            <Link :href="route('home')" class="text-secondary text-decoration-none small">
                <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                Voltar para a página inicial
            </Link>
        </div>
    </div>
</template>
