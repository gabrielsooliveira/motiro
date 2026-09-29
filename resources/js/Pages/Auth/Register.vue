<script setup>
import { useForm, Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    initialType: {
        type: String,
        default: null,
    },
});

const selectedType = ref(props.initialType || null);

const competitorForm = useForm({
    type: "competitor",
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const organizationForm = useForm({
    type: "organization",
    name: "",
    email: "",
    organization_type: "Empresa",
    website: "",
    password: "",
    password_confirmation: "",
    terms: false,
});

const submitCompetitor = () => {
    competitorForm.post(route("register.store"));
};

const submitOrganization = () => {
    organizationForm.post(route("register.store"));
};
</script>

<template>
    <Head>
        <title>Criar Conta | Motirõ</title>
    </Head>

    <div class="p-3" style="max-width: 460px; margin: 0 auto;">
        <!-- Header -->
        <div class="text-center mb-4">
            <Link :href="route('home')" class="text-decoration-none">
                <h2 class="fw-bold text-white title-page mb-1">Motirõ</h2>
            </Link>
            <p class="text-light text-opacity-75 small">
                Conectando inovação, talentos e organizações em Hackathons.
            </p>
        </div>

        <!-- 1. Selection Step: Choose user type -->
        <div v-if="!selectedType" class="animate__animated animate__fadeIn">
            <h5 class="fw-bold text-white text-center mb-3">Como você deseja utilizar o Motirõ?</h5>
            
            <div class="card bg-dark border-secondary mb-3 shadow-sm text-white">
                <div class="card-body p-4 text-center">
                    <div class="mb-3 text-primary fs-1">
                        <font-awesome-icon :icon="['fas', 'user-astronaut']" />
                    </div>
                    <h5 class="fw-bold mb-1">Competidor</h5>
                    <p class="text-light text-opacity-75 small mb-3">
                        Participe de Hackathons, forme ou encontre equipes, submeta projetos e mostre seu potencial.
                    </p>
                    <button class="btn btn-primary w-100 fw-semibold" @click="selectedType = 'competitor'">
                        Criar Conta de Competidor
                    </button>
                </div>
            </div>

            <div class="card bg-dark border-secondary shadow-sm text-white">
                <div class="card-body p-4 text-center">
                    <div class="mb-3 text-info fs-1">
                        <font-awesome-icon :icon="['fas', 'building']" />
                    </div>
                    <h5 class="fw-bold mb-1">Organização</h5>
                    <p class="text-light text-opacity-75 small mb-3">
                        Crie e gerencie seus próprios Hackathons, etapas, regras, equipes e bancas de juízes.
                    </p>
                    <button class="btn btn-outline-light w-100 fw-semibold" @click="selectedType = 'organization'">
                        Cadastrar Organização
                    </button>
                </div>
            </div>

            <div class="text-center mt-4">
                <span class="text-light text-opacity-75 small">Já possui uma conta? </span>
                <Link :href="route('login')" class="text-primary text-decoration-none fw-semibold small">
                    Faça login
                </Link>
            </div>
        </div>

        <!-- 2. Competitor Form -->
        <div v-else-if="selectedType === 'competitor'">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-link link-light text-decoration-none p-0 small" @click="selectedType = null">
                    &larr; Escolher outro perfil
                </button>
                <span class="badge bg-primary">Perfil: Competidor</span>
            </div>

            <form @submit.prevent="submitCompetitor">
                <div class="mb-3">
                    <label class="form-label text-white fw-semibold small" for="comp_name">Nome Completo</label>
                    <input
                        id="comp_name"
                        type="text"
                        v-model="competitorForm.name"
                        class="form-control"
                        placeholder="Seu nome"
                        required
                    />
                    <span v-if="competitorForm.errors.name" class="text-danger small">{{ competitorForm.errors.name }}</span>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white fw-semibold small" for="comp_email">E-mail</label>
                    <input
                        id="comp_email"
                        type="email"
                        v-model="competitorForm.email"
                        class="form-control"
                        placeholder="seu@email.com"
                        required
                    />
                    <span v-if="competitorForm.errors.email" class="text-danger small">{{ competitorForm.errors.email }}</span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-white fw-semibold small" for="comp_pass">Senha</label>
                        <input
                            id="comp_pass"
                            type="password"
                            v-model="competitorForm.password"
                            class="form-control"
                            required
                        />
                        <span v-if="competitorForm.errors.password" class="text-danger small">{{ competitorForm.errors.password }}</span>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-white fw-semibold small" for="comp_conf">Confirmar Senha</label>
                        <input
                            id="comp_conf"
                            type="password"
                            v-model="competitorForm.password_confirmation"
                            class="form-control"
                            required
                        />
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" v-model="competitorForm.terms" class="form-check-input" id="comp_terms" required />
                    <label class="form-check-label text-light text-opacity-75 small" for="comp_terms">
                        Concordo com os Termos de Uso e Política de Privacidade (LGPD).
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold mb-3" :disabled="competitorForm.processing">
                    Finalizar Cadastro de Competidor
                </button>

                <div class="text-center">
                    <span class="text-light text-opacity-75 small">Já possui uma conta? </span>
                    <Link :href="route('login')" class="text-primary text-decoration-none fw-semibold small">
                        Faça login
                    </Link>
                </div>
            </form>
        </div>

        <!-- 3. Organization Form -->
        <div v-else-if="selectedType === 'organization'">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button type="button" class="btn btn-link link-light text-decoration-none p-0 small" @click="selectedType = null">
                    &larr; Escolher outro perfil
                </button>
                <span class="badge bg-info text-dark">Perfil: Organização</span>
            </div>

            <form @submit.prevent="submitOrganization">
                <div class="mb-3">
                    <label class="form-label text-white fw-semibold small" for="org_name">Nome da Organização / Empresa</label>
                    <input
                        id="org_name"
                        type="text"
                        v-model="organizationForm.name"
                        class="form-control"
                        placeholder="ex: Tech Inovações, Universidade XPTO"
                        required
                    />
                    <span v-if="organizationForm.errors.name" class="text-danger small">{{ organizationForm.errors.name }}</span>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white fw-semibold small" for="org_type">Tipo de Organização</label>
                    <select id="org_type" v-model="organizationForm.organization_type" class="form-select" required>
                        <option value="Empresa">Empresa</option>
                        <option value="Universidade">Universidade</option>
                        <option value="Escola">Escola</option>
                        <option value="Organização social">Organização social</option>
                        <option value="Órgão público">Órgão público</option>
                        <option value="Comunidade">Comunidade</option>
                        <option value="Startup">Startup</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white fw-semibold small" for="org_email">E-mail Corporativo / Organizacional</label>
                    <input
                        id="org_email"
                        type="email"
                        v-model="organizationForm.email"
                        class="form-control"
                        placeholder="contato@organizacao.com"
                        required
                    />
                    <span v-if="organizationForm.errors.email" class="text-danger small">{{ organizationForm.errors.email }}</span>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white fw-semibold small" for="org_website">Website Oficial (opcional)</label>
                    <input
                        id="org_website"
                        type="url"
                        v-model="organizationForm.website"
                        class="form-control"
                        placeholder="https://suaorganizacao.com"
                    />
                    <span v-if="organizationForm.errors.website" class="text-danger small">{{ organizationForm.errors.website }}</span>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-white fw-semibold small" for="org_pass">Senha</label>
                        <input
                            id="org_pass"
                            type="password"
                            v-model="organizationForm.password"
                            class="form-control"
                            required
                        />
                        <span v-if="organizationForm.errors.password" class="text-danger small">{{ organizationForm.errors.password }}</span>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-white fw-semibold small" for="org_conf">Confirmar Senha</label>
                        <input
                            id="org_conf"
                            type="password"
                            v-model="organizationForm.password_confirmation"
                            class="form-control"
                            required
                        />
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" v-model="organizationForm.terms" class="form-check-input" id="org_terms" required />
                    <label class="form-check-label text-light text-opacity-75 small" for="org_terms">
                        Concordo com os Termos de Uso e Política de Privacidade (LGPD).
                    </label>
                </div>

                <button type="submit" class="btn btn-info text-dark w-100 fw-semibold mb-3" :disabled="organizationForm.processing">
                    Criar Organização & Acessar Painel
                </button>

                <div class="text-center">
                    <span class="text-light text-opacity-75 small">Já possui uma conta? </span>
                    <Link :href="route('login')" class="text-primary text-decoration-none fw-semibold small">
                        Faça login
                    </Link>
                </div>
            </form>
        </div>
    </div>
</template>
