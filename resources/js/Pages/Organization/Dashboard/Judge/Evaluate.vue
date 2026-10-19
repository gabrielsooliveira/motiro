<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    criteria: {
        type: Array,
        required: true,
    },
    evaluation: {
        type: Object,
        default: null,
    },
});

const initialScores = props.criteria.map((crit) => {
    const existing = props.evaluation?.scores?.find((s) => s.criteria_id === crit.id);
    return {
        criteria_id: crit.id,
        score: existing ? existing.score : 0,
        comment: existing ? existing.comment : "",
    };
});

const form = useForm({
    status: "submitted",
    scores: initialScores,
    feedback_positive: props.evaluation?.feedback_positive || "",
    feedback_improvement: props.evaluation?.feedback_improvement || "",
    private_admin_notes: props.evaluation?.private_admin_notes || "",
});

const submitEvaluation = (status) => {
    form.status = status;
    form.post(route("judge.evaluate.store", props.project.id));
};
</script>

<template>
    <Head>
        <title>Avaliar Projeto | {{ project.title }}</title>
    </Head>

    <div class="py-4">
        <Link :href="route('judge.index')" class="text-decoration-none text-muted mb-2 d-inline-block">
            &larr; Voltar para Painel do Juiz
        </Link>

        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="fw-bold mb-0">{{ project.title }}</h2>
                    <span class="badge bg-secondary">Equipe: {{ project.team ? project.team.name : '-' }}</span>
                </div>
                <p class="text-muted">{{ project.short_description || 'Sem descrição resumida.' }}</p>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a v-if="project.github_repository_url" :href="project.github_repository_url" target="_blank" class="btn btn-sm btn-outline-dark">
                        <font-awesome-icon :icon="['fab', 'github']" class="me-1" /> Repositório GitHub
                    </a>
                    <a v-if="project.demo_url" :href="project.demo_url" target="_blank" class="btn btn-sm btn-outline-primary">
                        Link de Demonstração
                    </a>
                    <a v-if="project.video_url" :href="project.video_url" target="_blank" class="btn btn-sm btn-outline-danger">
                        Vídeo / Pitch
                    </a>
                </div>
            </div>
        </div>

        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0 fw-bold">Critérios de Avaliação Ponderada</h5>
            </div>
            <div class="card-body">
                <form @submit.prevent="submitEvaluation('submitted')">
                    <div v-for="(crit, index) in criteria" :key="crit.id" class="evaluation-criteria-box">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-2">
                            <div>
                                <h6 class="fw-bold mb-0">{{ crit.name }}</h6>
                                <small class="text-muted">{{ crit.description }}</small>
                            </div>
                            <span class="badge bg-primary text-white">Peso: {{ (crit.weight * 100).toFixed(0) }}%</span>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-md-3">
                                <label class="form-label small">Nota ({{ crit.min_score }} a {{ crit.max_score }})</label>
                                <input
                                    v-model.number="form.scores[index].score"
                                    type="number"
                                    step="0.5"
                                    :min="crit.min_score"
                                    :max="crit.max_score"
                                    class="form-control"
                                    required
                                />
                            </div>
                            <div class="col-md-9">
                                <label class="form-label small">Comentário sobre este critério (opcional)</label>
                                <input
                                    v-model="form.scores[index].comment"
                                    type="text"
                                    class="form-control"
                                    placeholder="Justificativa da nota..."
                                />
                            </div>
                        </div>
                    </div>

                    
                    <div class="mb-3 mt-4">
                        <label class="form-label fw-bold">Pontos Fortes (Feedback Positivo)</label>
                        <textarea v-model="form.feedback_positive" class="form-control" rows="2" placeholder="O que mais chamou atenção de forma positiva na solução?"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Oportunidades de Melhoria</label>
                        <textarea v-model="form.feedback_improvement" class="form-control" rows="2" placeholder="Dicas para a equipe evoluir a solução..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Notas Privadas para os Organizadores</label>
                        <textarea v-model="form.private_admin_notes" class="form-control" rows="2" placeholder="Notas visíveis apenas para os administradores do hackathon..."></textarea>
                    </div>

                    <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" @click="submitEvaluation('draft')" :disabled="form.processing">
                            Salvar como Rascunho
                        </button>
                        <button type="submit" class="btn btn-success" :disabled="form.processing">
                            Finalizar e Enviar Avaliação
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
