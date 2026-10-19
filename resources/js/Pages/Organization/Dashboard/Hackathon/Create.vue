<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    organizations: {
        type: Array,
        required: true,
    },
    activeOrganization: {
        type: Object,
        required: true,
    },
});

const currentStep = ref(1);

const defaultStages = [
    {
        name: "Inscrições e Formação de Equipes",
        description: "Período para cadastro de participantes, formação ou adesão a equipes.",
        start_date: new Date(Date.now() + 1 * 86400000).toISOString().slice(0, 16),
        end_date: new Date(Date.now() + 10 * 86400000).toISOString().slice(0, 16),
        allowed_actions: ["team_register"],
        order: 1,
    },
    {
        name: "Desenvolvimento e Mentorias",
        description: "Fase de ideação, prototipagem e desenvolvimento da solução com suporte de mentores.",
        start_date: new Date(Date.now() + 11 * 86400000).toISOString().slice(0, 16),
        end_date: new Date(Date.now() + 13 * 86400000).toISOString().slice(0, 16),
        allowed_actions: [],
        order: 2,
    },
    {
        name: "Submissão Final de Projetos",
        description: "Prazo limite para envio do repositório, link do pitch e documentação do projeto.",
        start_date: new Date(Date.now() + 13 * 86400000).toISOString().slice(0, 16),
        end_date: new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 16),
        allowed_actions: ["project_submit"],
        order: 3,
    },
    {
        name: "Avaliação da Banca Julgadora",
        description: "Período em que os juízes avaliam os projetos com base nos critérios estabelecidos.",
        start_date: new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 16),
        end_date: new Date(Date.now() + 16 * 86400000).toISOString().slice(0, 16),
        allowed_actions: ["judging"],
        order: 4,
    },
    {
        name: "Resultado e Premiação",
        description: "Divulgação do ranking oficial, vencedores e entrega das premiações.",
        start_date: new Date(Date.now() + 17 * 86400000).toISOString().slice(0, 16),
        end_date: new Date(Date.now() + 17 * 86400000).toISOString().slice(0, 16),
        allowed_actions: [],
        order: 5,
    },
];

const defaultCriteria = [
    {
        name: "Inovação e Criatividade",
        description: "Originalidade da proposta e abordagem não convencional para resolver o problema.",
        weight: 30,
        min_score: 0,
        max_score: 10,
        order: 1,
    },
    {
        name: "Qualidade Técnica e Execução",
        description: "Arquitetura, funcionamento do código ou protótipo funcional demonstrado.",
        weight: 25,
        min_score: 0,
        max_score: 10,
        order: 2,
    },
    {
        name: "Impacto e Viabilidade",
        description: "Potencial real de mercado, escalabilidade e valor gerado para os usuários.",
        weight: 25,
        min_score: 0,
        max_score: 10,
        order: 3,
    },
    {
        name: "Design e Experiência do Usuário (UX/UI)",
        description: "Clareza, facilidade de uso, navegabilidade e refinamento visual da interface.",
        weight: 10,
        min_score: 0,
        max_score: 10,
        order: 4,
    },
    {
        name: "Apresentação e Pitch",
        description: "Capacidade de sintetizar o problema, demonstrar a solução e defender os diferenciais.",
        weight: 10,
        min_score: 0,
        max_score: 10,
        order: 5,
    },
];

const form = useForm({
    organization_id: props.activeOrganization?.id || props.organizations[0]?.id || "",
    title: "",
    description: "",
    category: "",
    modality: "online",
    location: "",
    start_date: new Date(Date.now() + 1 * 86400000).toISOString().slice(0, 16),
    end_date: new Date(Date.now() + 17 * 86400000).toISOString().slice(0, 16),
    registration_start_date: new Date(Date.now() + 1 * 86400000).toISOString().slice(0, 16),
    registration_end_date: new Date(Date.now() + 10 * 86400000).toISOString().slice(0, 16),
    registration_type: "individual_or_team",
    allow_team_creation: true,
    allow_team_invites: true,
    allow_team_requests: true,
    allow_org_team_management: true,
    team_formation_deadline: new Date(Date.now() + 10 * 86400000).toISOString().slice(0, 16),
    min_team_members: 1,
    max_team_members: 5,
    max_teams: 50,
    max_participants: 250,
    rules: "",
    stages: JSON.parse(JSON.stringify(defaultStages)),
    criteria: JSON.parse(JSON.stringify(defaultCriteria)),
});

const totalWeight = computed(() => {
    return form.criteria.reduce((sum, item) => sum + (parseFloat(item.weight) || 0), 0);
});

const isWeightBalanced = computed(() => {
    return Math.abs(totalWeight.value - 100) < 0.01;
});

const addStage = () => {
    const nextOrder = form.stages.length + 1;
    form.stages.push({
        name: `Nova Etapa ${nextOrder}`,
        description: "",
        start_date: form.end_date || new Date().toISOString().slice(0, 16),
        end_date: form.end_date || new Date(Date.now() + 86400000).toISOString().slice(0, 16),
        allowed_actions: [],
        order: nextOrder,
    });
};

const removeStage = (index) => {
    if (form.stages.length <= 1) return;
    form.stages.splice(index, 1);
    form.stages.forEach((s, idx) => {
        s.order = idx + 1;
    });
};

const addCriterion = () => {
    const nextOrder = form.criteria.length + 1;
    form.criteria.push({
        name: `Critério ${nextOrder}`,
        description: "",
        weight: 10,
        min_score: 0,
        max_score: 10,
        order: nextOrder,
    });
};

const removeCriterion = (index) => {
    if (form.criteria.length <= 1) return;
    form.criteria.splice(index, 1);
    form.criteria.forEach((c, idx) => {
        c.order = idx + 1;
    });
};

const nextStep = () => {
    if (currentStep.value < 4) {
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
};

const goToStep = (step) => {
    if (step >= 1 && step <= 4) {
        currentStep.value = step;
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
};

const submit = () => {
    form.post(route("hackathon.manage.store"));
};

const formatDate = (isoStr) => {
    if (!isoStr) return "Não informado";
    const d = new Date(isoStr);
    return isNaN(d) ? isoStr : d.toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" });
};
</script>

<template>
    <Head title="Criar Novo Hackathon" />

    <div class="container-fluid py-2 max-w-6xl">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
            <div>
                <Link
                    :href="route('organization.dashboard')"
                    class="btn btn-outline-secondary btn-sm mb-2 d-inline-flex align-items-center gap-2"
                >
                    <font-awesome-icon :icon="['fas', 'arrow-left']" />
                    <span>Voltar ao Painel</span>
                </Link>
                <h1 class="h3 fw-bold text-dark mb-0">Cadastro de Hackathon</h1>
                <p class="text-muted small mb-0 mt-1">
                    Defina os dados gerais, cronograma de etapas com prazos limites e os pesos de cada entrega.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold">
                    Etapa {{ currentStep }} de 4
                </span>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-3 p-md-4">
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3">
                        <button
                            type="button"
                            class="btn w-100 p-2 text-start rounded-3 border d-flex align-items-center gap-2"
                            :class="currentStep === 1 ? 'btn-primary text-white border-primary' : 'btn-light text-secondary border-light-subtle'"
                            @click="goToStep(1)"
                        >
                            <span class="badge rounded-circle p-2 d-flex align-items-center justify-content-center step-indicator-badge" :class="currentStep === 1 ? 'bg-white text-primary' : 'bg-secondary bg-opacity-25 text-dark'">1</span>
                            <div class="d-flex flex-column text-truncate">
                                <span class="fw-bold small text-truncate">1. Dados Gerais</span>
                                <span class="fs-8 text-truncate opacity-75">Modalidade & tema</span>
                            </div>
                        </button>
                    </div>

                    <div class="col-6 col-md-3">
                        <button
                            type="button"
                            class="btn w-100 p-2 text-start rounded-3 border d-flex align-items-center gap-2"
                            :class="currentStep === 2 ? 'btn-primary text-white border-primary' : 'btn-light text-secondary border-light-subtle'"
                            @click="goToStep(2)"
                        >
                            <span class="badge rounded-circle p-2 d-flex align-items-center justify-content-center step-indicator-badge" :class="currentStep === 2 ? 'bg-white text-primary' : 'bg-secondary bg-opacity-25 text-dark'">2</span>
                            <div class="d-flex flex-column text-truncate">
                                <span class="fw-bold small text-truncate">2. Prazos das Etapas</span>
                                <span class="fs-8 text-truncate opacity-75">Cronograma & datas</span>
                            </div>
                        </button>
                    </div>

                    <div class="col-6 col-md-3">
                        <button
                            type="button"
                            class="btn w-100 p-2 text-start rounded-3 border d-flex align-items-center gap-2"
                            :class="currentStep === 3 ? 'btn-primary text-white border-primary' : 'btn-light text-secondary border-light-subtle'"
                            @click="goToStep(3)"
                        >
                            <span class="badge rounded-circle p-2 d-flex align-items-center justify-content-center step-indicator-badge" :class="currentStep === 3 ? 'bg-white text-primary' : 'bg-secondary bg-opacity-25 text-dark'">3</span>
                            <div class="d-flex flex-column text-truncate">
                                <span class="fw-bold small text-truncate">3. Pesos de Entrega</span>
                                <span class="fs-8 text-truncate opacity-75">Critérios & notas</span>
                            </div>
                        </button>
                    </div>

                    <div class="col-6 col-md-3">
                        <button
                            type="button"
                            class="btn w-100 p-2 text-start rounded-3 border d-flex align-items-center gap-2"
                            :class="currentStep === 4 ? 'btn-primary text-white border-primary' : 'btn-light text-secondary border-light-subtle'"
                            @click="goToStep(4)"
                        >
                            <span class="badge rounded-circle p-2 d-flex align-items-center justify-content-center step-indicator-badge" :class="currentStep === 4 ? 'bg-white text-primary' : 'bg-secondary bg-opacity-25 text-dark'">4</span>
                            <div class="d-flex flex-column text-truncate">
                                <span class="fw-bold small text-truncate">4. Revisão & Envio</span>
                                <span class="fs-8 text-truncate opacity-75">Confirmar dados</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit">
            
            <div v-show="currentStep === 1" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="border-bottom pb-3 mb-4">
                    <h5 class="fw-bold text-dark mb-1">Informações Básicas do Evento</h5>
                    <p class="text-muted small mb-0">Preencha o título, instituição responsável e modalidade de participação.</p>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Instituição Promotora *</label>
                        <select v-model="form.organization_id" class="form-select" required>
                            <option v-for="org in organizations" :key="org.id" :value="org.id">
                                {{ org.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.organization_id" class="text-danger small mt-1">
                            {{ form.errors.organization_id }}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Título do Hackathon *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="form-control"
                            placeholder="ex: Hackathon Nacional de Inovação e Inteligência Artificial 2026"
                            required
                        />
                        <div v-if="form.errors.title" class="text-danger small mt-1">
                            {{ form.errors.title }}
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Categoria / Área de Foco</label>
                        <input
                            v-model="form.category"
                            type="text"
                            class="form-control"
                            placeholder="ex: Inteligência Artificial, FinTech, Saúde, Sustentabilidade"
                        />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Modalidade *</label>
                        <select v-model="form.modality" class="form-select" required>
                            <option value="online">Online (100% Remoto)</option>
                            <option value="presential">Presencial</option>
                            <option value="hybrid">Híbrido</option>
                        </select>
                    </div>
                </div>

                <div v-if="form.modality !== 'online'" class="mb-3">
                    <label class="form-label fw-semibold">Local Presencial / Campus</label>
                    <input
                        v-model="form.location"
                        type="text"
                        class="form-control"
                        placeholder="Endereço, campus, auditório ou laboratório do evento"
                    />
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data e Hora de Início Geral</label>
                        <input v-model="form.start_date" type="datetime-local" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Data e Hora de Término Geral</label>
                        <input v-model="form.end_date" type="datetime-local" class="form-control" />
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Início do Período de Inscrições</label>
                        <input v-model="form.registration_start_date" type="datetime-local" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Término do Período de Inscrições</label>
                        <input v-model="form.registration_end_date" type="datetime-local" class="form-control" />
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tipo de Inscrição</label>
                        <select v-model="form.registration_type" class="form-select">
                            <option value="individual_or_team">Individual ou em Equipe</option>
                            <option value="team_only">Apenas Equipe Formada</option>
                            <option value="individual">Apenas Individual</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mín. Integrantes / Time</label>
                        <input v-model="form.min_team_members" type="number" min="1" class="form-control" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Máx. Integrantes / Time</label>
                        <input v-model="form.max_team_members" type="number" min="1" class="form-control" required />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Limite Total de Equipes</label>
                        <input v-model="form.max_teams" type="number" min="1" class="form-control" />
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Descrição do Hackathon</label>
                    <textarea
                        v-model="form.description"
                        class="form-control"
                        rows="3"
                        placeholder="Apresente os objetivos do evento, o público-alvo e o impacto esperado..."
                    ></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Regulamento e Diretrizes Gerais</label>
                    <textarea
                        v-model="form.rules"
                        class="form-control"
                        rows="3"
                        placeholder="Requisitos de elegibilidade, termos de conduta e normas do evento..."
                    ></textarea>
                </div>
            </div>

            
            <div v-show="currentStep === 2" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 border-bottom pb-3 mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Cronograma e Prazos de Cada Etapa</h5>
                        <p class="text-muted small mb-0">
                            Configure os prazos de início e encerramento para cada fase da competição.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2"
                        @click="addStage"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Adicionar Etapa</span>
                    </button>
                </div>

                <div class="d-flex flex-column gap-3">
                    <div
                        v-for="(stage, idx) in form.stages"
                        :key="idx"
                        class="border rounded-4 p-3 p-md-4 bg-light bg-opacity-25 position-relative"
                    >
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary text-white px-2 py-1 rounded-pill small">
                                Etapa {{ idx + 1 }}
                            </span>
                            <button
                                v-if="form.stages.length > 1"
                                type="button"
                                class="btn btn-link text-danger p-0 small"
                                @click="removeStage(idx)"
                                title="Remover esta etapa"
                            >
                                <font-awesome-icon :icon="['fas', 'trash-can']" class="me-1" />
                                <span>Remover</span>
                            </button>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small">Nome da Etapa *</label>
                                <input
                                    v-model="stage.name"
                                    type="text"
                                    class="form-control"
                                    placeholder="ex: Desenvolvimento & Mentorias"
                                    required
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Prazo de Início *</label>
                                <input
                                    v-model="stage.start_date"
                                    type="datetime-local"
                                    class="form-control"
                                    required
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Prazo Limite / Término *</label>
                                <input
                                    v-model="stage.end_date"
                                    type="datetime-local"
                                    class="form-control"
                                    required
                                />
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Descrição & Orientações da Etapa</label>
                            <textarea
                                v-model="stage.description"
                                class="form-control"
                                rows="2"
                                placeholder="Descreva o que os participantes devem executar ou entregar neste período..."
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            
            <div v-show="currentStep === 3" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 border-bottom pb-3 mb-4">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Pesos de Cada Entrega & Critérios de Avaliação</h5>
                        <p class="text-muted small mb-0">
                            Defina os critérios e a ponderação percentual de cada entrega para o cálculo da nota final.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2"
                        @click="addCriterion"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Adicionar Critério / Entrega</span>
                    </button>
                </div>

                
                <div class="p-3 rounded-3 mb-4 border" :class="isWeightBalanced ? 'bg-success-subtle border-success-subtle text-success-emphasis' : 'bg-warning-subtle border-warning-subtle text-warning-emphasis'">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold small">
                            <font-awesome-icon :icon="isWeightBalanced ? ['fas', 'circle-check'] : ['fas', 'triangle-exclamation']" class="me-1" />
                            Distribuição dos Pesos das Entregas
                        </span>
                        <span class="fw-bold">{{ totalWeight.toFixed(1) }}% de 100%</span>
                    </div>
                    <div class="progress progress-xs">
                        <div
                            class="progress-bar"
                            :class="[
                                isWeightBalanced ? 'bg-success' : (totalWeight > 100 ? 'bg-danger' : 'bg-warning'),
                                'progress-w-' + Math.min(Math.round(totalWeight), 100)
                            ]"
                        ></div>
                    </div>
                    <small class="d-block mt-2">
                        {{ isWeightBalanced ? 'A soma dos pesos totaliza exatamente 100%. A ponderação está perfeita.' : 'Atenção: A soma recomendada dos pesos para as entregas é de 100%.' }}
                    </small>
                </div>

                <div class="d-flex flex-column gap-3">
                    <div
                        v-for="(crit, idx) in form.criteria"
                        :key="idx"
                        class="border rounded-4 p-3 p-md-4 bg-light bg-opacity-25"
                    >
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-secondary text-white px-2 py-1 rounded-pill small">
                                Entrega / Critério {{ idx + 1 }}
                            </span>
                            <button
                                v-if="form.criteria.length > 1"
                                type="button"
                                class="btn btn-link text-danger p-0 small"
                                @click="removeCriterion(idx)"
                                title="Remover este critério"
                            >
                                <font-awesome-icon :icon="['fas', 'trash-can']" class="me-1" />
                                <span>Remover</span>
                            </button>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-5">
                                <label class="form-label fw-semibold small">Nome da Entrega / Critério *</label>
                                <input
                                    v-model="crit.name"
                                    type="text"
                                    class="form-control"
                                    placeholder="ex: Inovação e Originalidade"
                                    required
                                />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold small">Peso da Entrega (%) *</label>
                                <div class="input-group">
                                    <input
                                        v-model.number="crit.weight"
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        class="form-control"
                                        required
                                    />
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Nota Mínima</label>
                                <input
                                    v-model.number="crit.min_score"
                                    type="number"
                                    step="0.5"
                                    class="form-control"
                                />
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold small">Nota Máxima</label>
                                <input
                                    v-model.number="crit.max_score"
                                    type="number"
                                    step="0.5"
                                    class="form-control"
                                />
                            </div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label fw-semibold small">Descrição & Diretriz para os Avaliadores</label>
                            <textarea
                                v-model="crit.description"
                                class="form-control"
                                rows="2"
                                placeholder="O que a banca de jurados deve levar em consideração ao atribuir a nota para esta entrega..."
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>

            
            <div v-show="currentStep === 4" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="border-bottom pb-3 mb-4">
                    <h5 class="fw-bold text-dark mb-1">Revisão Geral e Confirmação</h5>
                    <p class="text-muted small mb-0">
                        Revise as informações gerais, os prazos de cada etapa e os pesos de entrega antes de finalizar.
                    </p>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 bg-light bg-opacity-25 h-100">
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Resumo Geral</h6>
                            <p class="mb-1"><strong>Título:</strong> {{ form.title || 'Não informado' }}</p>
                            <p class="mb-1"><strong>Categoria:</strong> {{ form.category || 'Geral' }}</p>
                            <p class="mb-1"><strong>Modalidade:</strong> <span class="text-capitalize">{{ form.modality }}</span></p>
                            <p v-if="form.location" class="mb-1"><strong>Local:</strong> {{ form.location }}</p>
                            <p class="mb-1"><strong>Inscrições:</strong> {{ formatDate(form.registration_start_date) }} até {{ formatDate(form.registration_end_date) }}</p>
                            <p class="mb-0"><strong>Equipes:</strong> {{ form.min_team_members }} a {{ form.max_team_members }} membros</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 bg-light bg-opacity-25 h-100">
                            <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Distribuição das Avaliações</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold">Total de Critérios:</span>
                                <span>{{ form.criteria.length }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold">Soma dos Pesos:</span>
                                <span :class="isWeightBalanced ? 'text-success fw-bold' : 'text-warning fw-bold'">{{ totalWeight }}%</span>
                            </div>
                            <ul class="list-unstyled mb-0 small">
                                <li v-for="c in form.criteria" :key="c.name" class="d-flex justify-content-between py-1 border-bottom border-light-subtle">
                                    <span>{{ c.name }}</span>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ c.weight }}%</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="border rounded-4 p-3 bg-light bg-opacity-25 mb-4">
                    <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Cronograma de Etapas ({{ form.stages.length }})</h6>
                    <div class="row g-3">
                        <div v-for="(stg, idx) in form.stages" :key="idx" class="col-md-6">
                            <div class="bg-white border rounded-3 p-3">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <span class="fw-bold text-dark">{{ idx + 1 }}. {{ stg.name }}</span>
                                </div>
                                <div class="text-muted small">
                                    <font-awesome-icon :icon="['fas', 'calendar-days']" class="me-1 text-primary" />
                                    {{ formatDate(stg.start_date) }} &rarr; {{ formatDate(stg.end_date) }}
                                </div>
                                <p v-if="stg.description" class="small text-secondary mb-0 mt-2 text-truncate">
                                    {{ stg.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="form.hasErrors" class="alert alert-danger rounded-3 p-3 mb-4">
                    <strong class="d-block mb-1">Existem erros no formulário:</strong>
                    <ul class="mb-0 ps-3 small">
                        <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                    </ul>
                </div>
            </div>

            
            <div class="d-flex justify-content-between align-items-center pt-2">
                <div>
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        class="btn btn-outline-secondary d-flex align-items-center gap-2"
                        @click="prevStep"
                    >
                        <font-awesome-icon :icon="['fas', 'arrow-left']" />
                        <span>Etapa Anterior</span>
                    </button>
                </div>

                <div class="d-flex gap-2">
                    <button
                        v-if="currentStep < 4"
                        type="button"
                        class="btn btn-primary d-flex align-items-center gap-2"
                        @click="nextStep"
                    >
                        <span>Próxima Etapa</span>
                        <font-awesome-icon :icon="['fas', 'arrow-right']" />
                    </button>

                    <button
                        v-else
                        type="submit"
                        class="btn btn-success d-flex align-items-center gap-2 px-4 py-2"
                        :disabled="form.processing"
                    >
                        <font-awesome-icon v-if="!form.processing" :icon="['fas', 'check']" />
                        <span>{{ form.processing ? 'Cadastrando Hackathon...' : 'Concluir e Salvar Hackathon' }}</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>
