<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    education_institution: "",
    education_course: "",
    professional_experience: "",
    github_url: "",
    linkedin_url: "",
    portfolio_url: "",
    is_profile_public: true,
});

const submit = () => {
    form.post(route("participant.complete_profile.store"), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Ativar Perfil de Participante | Motirõ" />

    <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center bg-light py-5">
        <div class="container" style="max-width: 650px;">
            <div class="text-center mb-4">
                <Link :href="route('home')" class="text-decoration-none">
                    <span class="fs-2 fw-bold text-dark">Motirõ</span>
                </Link>
                <div class="mt-2">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-semibold small">
                        Área do Participante
                    </span>
                </div>
                <h1 class="h3 fw-bold text-dark mt-2 mb-1">Olá, {{ user.name }}!</h1>
                <p class="text-muted small">
                    Para participar de hackathons, formar guilds e submeter soluções, ative seu perfil de participante.
                </p>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="edu_inst">Instituição de Ensino / Universidade</label>
                        <input
                            id="edu_inst"
                            v-model="form.education_institution"
                            type="text"
                            class="form-control"
                            placeholder="ex: USP, Unicamp, IFSP, FIAP..."
                        />
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="edu_course">Curso / Formação</label>
                        <input
                            id="edu_course"
                            v-model="form.education_course"
                            type="text"
                            class="form-control"
                            placeholder="ex: Ciência da Computação, Engenharia, Design..."
                        />
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold" for="prof_exp">Resumo Profissional / Bio</label>
                        <textarea
                            id="prof_exp"
                            v-model="form.professional_experience"
                            class="form-control"
                            rows="3"
                            placeholder="Conte um pouco sobre suas habilidades, interesses e objetivos em hackathons..."
                        ></textarea>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold" for="github_url">GitHub</label>
                            <input
                                id="github_url"
                                v-model="form.github_url"
                                type="url"
                                class="form-control"
                                placeholder="https://github.com/seu-usuario"
                            />
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label small fw-semibold" for="linkedin_url">LinkedIn</label>
                            <input
                                id="linkedin_url"
                                v-model="form.linkedin_url"
                                type="url"
                                class="form-control"
                                placeholder="https://linkedin.com/in/seu-perfil"
                            />
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input
                            id="is_public"
                            v-model="form.is_profile_public"
                            type="checkbox"
                            class="form-check-input"
                        />
                        <label class="form-check-label small text-muted" for="is_public">
                            Tornar meu perfil público para outros participantes e líderes de equipe me encontrarem.
                        </label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <Link :href="route('organization.select')" class="btn btn-outline-secondary">
                            Voltar para o Painel da Organização
                        </Link>

                        <button
                            type="submit"
                            class="btn btn-warning fw-semibold px-4 py-2 d-flex align-items-center gap-2"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="spinner-border spinner-border-sm" role="status"></span>
                            <span>{{ form.processing ? 'Ativando...' : 'Ativar e Acessar Hackathons' }}</span>
                            <font-awesome-icon v-if="!form.processing" :icon="['fas', 'arrow-right']" class="small" />
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
