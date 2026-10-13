<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    isTeamMember: {
        type: Boolean,
        default: false,
    },
    isSubmissionOpen: {
        type: Boolean,
        default: true,
    },
});

const form = useForm({
    hackathon_id: props.project.hackathon_id,
    team_id: props.project.team_id,
    title: props.project.title || "",
    short_description: props.project.short_description || "",
    problem_statement: props.project.problem_statement || "",
    solution_description: props.project.solution_description || "",
    target_audience: props.project.target_audience || "",
    pitch: props.project.pitch || "",
    video_url: props.project.video_url || "",
    github_repository_url: props.project.github_repository_url || "",
    demo_url: props.project.demo_url || "",
    documentation_url: props.project.documentation_url || "",
});

const submitProject = () => {
    form.post(route("project.submit"));
};
</script>

<template>
    <Head>
        <title>Projeto | {{ project.title }}</title>
    </Head>

    <div class="py-4">
        <Link :href="route('dashboard.index')" class="text-decoration-none text-muted mb-2 d-inline-block">
            &larr; Voltar para o Dashboard
        </Link>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h1 class="fw-bold mb-1">{{ project.title }}</h1>
                <span class="badge bg-primary">Equipe: {{ project.team ? project.team.name : '-' }}</span>
                <span class="badge bg-secondary ms-2">Status: {{ project.status }}</span>
            </div>
            <div v-if="!isSubmissionOpen" class="badge bg-danger p-2 fs-6">
                Submissões Encerradas
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="card-title mb-0 fw-bold">Informações do Projeto & Submissão</h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitProject">
                            <fieldset :disabled="!isTeamMember || !isSubmissionOpen">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Título do Projeto</label>
                                    <input v-model="form.title" type="text" class="form-control" required />
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Descrição Curta / Resumo</label>
                                    <textarea v-model="form.short_description" class="form-control" rows="2"></textarea>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Problema</label>
                                        <textarea v-model="form.problem_statement" class="form-control" rows="4"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Solução Proposta</label>
                                        <textarea v-model="form.solution_description" class="form-control" rows="4"></textarea>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Público-Alvo</label>
                                    <input v-model="form.target_audience" type="text" class="form-control" />
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Repositório GitHub</label>
                                        <input v-model="form.github_repository_url" type="url" class="form-control" placeholder="https://github.com/..." />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Link de Demonstração</label>
                                        <input v-model="form.demo_url" type="url" class="form-control" placeholder="https://..." />
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Link do Vídeo / Pitch</label>
                                        <input v-model="form.video_url" type="url" class="form-control" placeholder="https://youtube.com/..." />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Documentação</label>
                                        <input v-model="form.documentation_url" type="url" class="form-control" placeholder="https://..." />
                                    </div>
                                </div>

                                <button v-if="isTeamMember && isSubmissionOpen" type="submit" class="btn btn-primary" :disabled="form.processing">
                                    Salvar & Submeter Projeto
                                </button>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Version History / Submissions Snapshot -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'clock-rotate-left']" class="me-2" />
                            Histórico de Submissões
                        </h6>
                    </div>
                    <div class="card-body">
                        <div v-if="!project.submissions || project.submissions.length === 0" class="text-muted small">
                            Nenhum snapshot de submissão registrado.
                        </div>
                        <ul v-else class="list-group list-group-flush small">
                            <li v-for="sub in project.submissions" :key="sub.id" class="list-group-item px-0">
                                <div class="d-flex justify-content-between">
                                    <strong>Versão {{ sub.version }}</strong>
                                    <span class="text-muted">{{ new Date(sub.created_at).toLocaleString() }}</span>
                                </div>
                                <div class="text-secondary">Submetido por: {{ sub.submitter ? sub.submitter.name : 'Membro' }}</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
