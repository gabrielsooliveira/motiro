<script setup>
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ModalDialog from "@/js/Components/Modals/ModalDialog.vue";
import ModalConfirmation from "@/js/Components/Modals/ModalConfirmation.vue";

const props = defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
    teamsCount: {
        type: Number,
        default: 0,
    },
    participantsCount: {
        type: Number,
        default: 0,
    },
    userTeams: {
        type: Array,
        default: () => [],
    },
    userRegisteredTeamId: {
        type: String,
        default: null,
    },
    userRegistration: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.user);
const activeTab = ref("overview");
const showRegisterModal = ref(false);

const allowsIndividual = computed(() => props.hackathon.registration_type !== 'team_only');
const allowsTeam = computed(() => props.hackathon.registration_type !== 'individual');

const registrationForm = useForm({
    hackathon_id: props.hackathon.id,
    participation_type: props.hackathon.registration_type === 'team_only' ? 'team' : 'individual',
    team_id: props.hackathon.registration_type === 'team_only' ? (props.userTeams?.[0]?.id || "") : "",
});

const isUserRegistered = computed(() => {
    if (props.userRegistration) {
        return props.userRegistration.status !== 'cancelled';
    }
    return !!props.userRegisteredTeamId;
});

const registrationStatus = computed(() => {
    return props.userRegistration?.status || (props.userRegisteredTeamId ? 'approved' : null);
});

const setParticipationType = (type) => {
    registrationForm.participation_type = type;
    if (type === 'individual') {
        registrationForm.team_id = '';
    } else if (type === 'team' && !registrationForm.team_id) {
        registrationForm.team_id = props.userTeams?.[0]?.id || '';
    }
};

const submitRegistration = () => {
    registrationForm.post(route("registration.store"), {
        preserveScroll: true,
        onSuccess: () => {
            showRegisterModal.value = false;
        },
    });
};

const submitDirectIndividual = () => {
    setParticipationType('individual');
    submitRegistration();
};

const showCancelConfirm = ref(false);

const cancelRegistration = () => {
    showCancelConfirm.value = true;
};

const executeCancelRegistration = () => {
    showCancelConfirm.value = false;
    const regId = props.userRegistration?.id;
    if (regId) {
        router.delete(route("registration.cancel", regId), {
            preserveScroll: true,
        });
    }
};

const getModalityLabel = (type) => {
    if (type === 'individual') return 'Inscrição Individual';
    if (type === 'team_only') return 'Obrigatoriamente por Equipe';
    return 'Individual ou por Equipe';
};

const getStatusBadge = (status) => {
    const map = {
        published: "bg-success",
        registration: "bg-primary",
        submission: "bg-warning text-dark",
        voting: "bg-info text-dark",
        finished: "bg-secondary",
        draft: "bg-light text-dark border",
    };
    return map[status] || "bg-secondary";
};
</script>

<template>
    <Head>
        <title>Hackathons |{{ hackathon.title }}</title>
    </Head>

    <div class="container py-4">
        
        <div class="mb-3">
            <Link :href="route('hackathon.index')" class="text-decoration-none text-muted fw-semibold">
                <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                Voltar para lista de Hackathons
            </Link>
        </div>

        
        <div class="card shadow-sm border-0 mb-4 overflow-hidden">
            <div class="position-relative">
                <img
                    :src="hackathon.banner_url || 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200&auto=format&fit=crop&q=80'"
                    class="w-100 object-fit-cover hackathon-banner-img"
                    alt="Banner do Hackathon"
                />
                <div class="position-absolute top-0 end-0 p-3">
                    <span class="badge" :class="getStatusBadge(hackathon.status)">
                        {{ hackathon.status }}
                    </span>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <span class="badge bg-light text-dark border text-capitalize">
                                <font-awesome-icon :icon="['fas', 'location-dot']" class="me-1" />
                                {{ hackathon.modality }}
                            </span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                <font-awesome-icon :icon="['fas', 'user-group']" class="me-1" />
                                {{ getModalityLabel(hackathon.registration_type) }}
                            </span>
                            <span v-if="hackathon.category" class="badge bg-secondary bg-opacity-10 text-secondary">
                                {{ hackathon.category }}
                            </span>
                        </div>

                        <h1 class="fw-bold mb-2">{{ hackathon.title }}</h1>
                        <p class="text-muted mb-3">{{ hackathon.description || 'Nenhuma descrição fornecida.' }}</p>

                        <div class="d-flex flex-wrap gap-4 text-secondary small">
                            <div>
                                <font-awesome-icon :icon="['fas', 'building']" class="me-1" />
                                <strong>Organização:</strong> {{ hackathon.organization ? hackathon.organization.name : 'Motirõ' }}
                            </div>
                            <div v-if="allowsTeam">
                                <font-awesome-icon :icon="['fas', 'user-group']" class="me-1" />
                                <strong>Integrantes por equipe:</strong> {{ hackathon.min_team_members }} a {{ hackathon.max_team_members }}
                            </div>
                        </div>
                    </div>

                    
                    <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                        <template v-if="!user">
                            <Link :href="route('select.login')" class="btn btn-primary btn-lg shadow-sm">
                                <font-awesome-icon :icon="['fas', 'right-to-bracket']" class="me-2" />
                                Entrar para se Inscrever
                            </Link>
                        </template>

                        <template v-else-if="isUserRegistered">
                            <!-- Approved Status -->
                            <div v-if="registrationStatus === 'approved'" class="alert alert-success d-inline-block text-start mb-0 py-2 px-3 border border-success-subtle rounded-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <font-awesome-icon :icon="['fas', 'circle-check']" class="fs-5 text-success" />
                                    <div>
                                        <strong>Inscrição Confirmada!</strong>
                                        <div class="small text-muted" v-if="userRegistration?.team_id || userRegisteredTeamId">
                                            Participando na equipe <strong>{{ userRegistration?.team?.name || 'Equipe' }}</strong>.
                                        </div>
                                        <div class="small text-muted" v-else>
                                            Participando individualmente.
                                        </div>
                                    </div>
                                </div>
                                <div v-if="userRegistration?.id" class="mt-2 pt-2 border-top border-success-subtle text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="cancelRegistration"
                                    >
                                        <font-awesome-icon :icon="['fas', 'ban']" class="me-1" />
                                        Cancelar Inscrição
                                    </button>
                                </div>
                            </div>

                            <!-- Pending Status -->
                            <div v-else-if="registrationStatus === 'pending'" class="alert alert-warning d-inline-block text-start mb-0 py-2 px-3 border border-warning-subtle rounded-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <font-awesome-icon :icon="['fas', 'clock']" class="fs-5 text-warning" />
                                    <div>
                                        <strong>Inscrição Pendente de Aprovação</strong>
                                        <div class="small text-muted">
                                            Sua solicitação está sendo avaliada pela organização.
                                        </div>
                                    </div>
                                </div>
                                <div v-if="userRegistration?.id" class="mt-2 pt-2 border-top border-warning-subtle text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        @click="cancelRegistration"
                                    >
                                        <font-awesome-icon :icon="['fas', 'ban']" class="me-1" />
                                        Cancelar Inscrição
                                    </button>
                                </div>
                            </div>

                            <!-- Rejected Status -->
                            <div v-else-if="registrationStatus === 'rejected'" class="alert alert-danger d-inline-block text-start mb-0 py-2 px-3 border border-danger-subtle rounded-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <font-awesome-icon :icon="['fas', 'circle-xmark']" class="fs-5 text-danger" />
                                    <div>
                                        <strong>Inscrição Recusada</strong>
                                        <div class="small text-muted" v-if="userRegistration?.rejection_reason">
                                            Motivo: {{ userRegistration.rejection_reason }}
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 border-top border-danger-subtle text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        @click="showRegisterModal = true"
                                    >
                                        Tentar Nova Inscrição
                                    </button>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <button
                                v-if="hackathon.registration_type === 'individual'"
                                class="btn btn-primary btn-lg shadow-sm"
                                :disabled="registrationForm.processing"
                                @click="submitDirectIndividual"
                            >
                                <font-awesome-icon :icon="['fas', 'user-check']" class="me-2" />
                                Inscrever-se Individualmente
                            </button>

                            <button
                                v-else
                                class="btn btn-primary btn-lg shadow-sm"
                                @click="showRegisterModal = true"
                            >
                                <font-awesome-icon :icon="['fas', 'user-plus']" class="me-2" />
                                Realizar Inscrição
                            </button>
                        </template>

                        <!-- Outside alert messages -->
                        <div v-if="page.props.flash?.error || registrationForm.errors.registration" class="alert alert-danger py-2 px-3 small text-start mt-2 mb-0 shadow-sm">
                            <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                            {{ page.props.flash?.error || registrationForm.errors.registration }}
                        </div>
                        <div v-if="page.props.flash?.success" class="alert alert-success py-2 px-3 small text-start mt-2 mb-0 shadow-sm">
                            <font-awesome-icon :icon="['fas', 'circle-check']" class="me-1" />
                            {{ page.props.flash?.success }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <ul class="nav nav-pills mb-4">
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'overview' }"
                    @click="activeTab = 'overview'"
                >
                    Visão Geral & Regulamento
                </button>
            </li>
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'stages' }"
                    @click="activeTab = 'stages'"
                >
                    Cronograma & Etapas ({{ hackathon.stages ? hackathon.stages.length : 0 }})
                </button>
            </li>
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'criteria' }"
                    @click="activeTab = 'criteria'"
                >
                    Critérios de Avaliação ({{ hackathon.evaluation_criteria ? hackathon.evaluation_criteria.length : 0 }})
                </button>
            </li>
            <li class="nav-item">
                <button
                    class="nav-link"
                    :class="{ active: activeTab === 'teams' }"
                    @click="activeTab = 'teams'"
                >
                    Equipes Confirmadas ({{ teamsCount }})
                </button>
            </li>
        </ul>

        
        <div v-if="activeTab === 'overview'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h4 class="fw-bold mb-3">Regulamento e Diretrizes</h4>
                <div v-if="hackathon.rules" class="text-secondary lh-lg mb-4 text-pre-line">
                    {{ hackathon.rules }}
                </div>
                <div v-else class="text-muted">
                    Nenhum regulamento detalhado cadastrado pela organização.
                </div>

                <div class="border-top pt-4 mt-4 row g-3">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-1">Período de Inscrição</h6>
                            <p class="mb-0 text-muted small">
                                {{ hackathon.registration_start_date ? new Date(hackathon.registration_start_date).toLocaleDateString() : 'Aberto' }} até
                                {{ hackathon.registration_end_date ? new Date(hackathon.registration_end_date).toLocaleDateString() : 'Encerramento' }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-1">Submissão dos Projetos</h6>
                            <p class="mb-0 text-muted small">
                                {{ hackathon.submission_start_date ? new Date(hackathon.submission_start_date).toLocaleDateString() : 'Durante o evento' }} até
                                {{ hackathon.submission_end_date ? new Date(hackathon.submission_end_date).toLocaleDateString() : 'Prazo final' }}
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <h6 class="fw-bold mb-1">Contato Oficial</h6>
                            <p class="mb-0 text-muted small">
                                {{ hackathon.contact_email || 'contato@motiro.test' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div v-if="activeTab === 'stages'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div v-if="!hackathon.stages || hackathon.stages.length === 0" class="text-muted text-center py-4">
                    Nenhuma etapa cadastrada para este hackathon.
                </div>
                <div v-else>
                    <div v-for="stage in hackathon.stages" :key="stage.id" class="timeline-stage-item">
                        <h5 class="fw-bold mb-1">{{ stage.name }}</h5>
                        <div class="badge bg-light text-dark border mb-2">
                            {{ new Date(stage.start_date).toLocaleDateString() }} às {{ new Date(stage.start_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                            — {{ new Date(stage.end_date).toLocaleDateString() }} às {{ new Date(stage.end_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                        </div>
                        <p class="text-secondary mb-0">{{ stage.description || 'Etapa do cronograma oficial.' }}</p>
                    </div>
                </div>
            </div>
        </div>

        
        <div v-if="activeTab === 'criteria'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div v-if="!hackathon.evaluation_criteria || hackathon.evaluation_criteria.length === 0" class="text-muted text-center py-4">
                    Nenhum critério de avaliação cadastrado.
                </div>
                <div v-else class="row g-3">
                    <div v-for="crit in hackathon.evaluation_criteria" :key="crit.id" class="col-md-6">
                        <div class="evaluation-criteria-box">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0">{{ crit.name }}</h6>
                                <span class="badge bg-primary">Peso: {{ (crit.weight * 100).toFixed(0) }}%</span>
                            </div>
                            <small class="text-muted">Faixa de notas: {{ crit.min_score }} a {{ crit.max_score }}</small>
                            <p class="mb-0 mt-2 text-secondary">{{ crit.description || 'Critério oficial considerado pelos jurados.' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div v-if="activeTab === 'teams'" class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div v-if="!hackathon.registrations || hackathon.registrations.length === 0" class="text-muted text-center py-4">
                    Nenhuma equipe confirmada até o momento. Seja a primeira a se inscrever!
                </div>
                <div v-else class="row g-3">
                    <div v-for="reg in hackathon.registrations.filter(r => r.status === 'approved')" :key="reg.id" class="col-md-6 col-lg-4">
                        <div class="p-3 border rounded-3 bg-light">
                            <h6 class="fw-bold mb-1">{{ reg.team ? reg.team.name : 'Equipe' }}</h6>
                            <small class="text-muted">{{ reg.team?.members?.length || 0 }} membro(s)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- Modal de Inscrição no Hackathon -->
        <ModalDialog
            :is-visible="showRegisterModal"
            title="Inscrição no Hackathon"
            size="md"
            @close="showRegisterModal = false"
        >
            <form @submit.prevent="submitRegistration">
                <div v-if="allowsIndividual && allowsTeam" class="mb-4">
                    <label class="form-label fw-semibold mb-2">Como você deseja se inscrever?</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <div
                                class="border rounded-3 p-3 text-center cursor-pointer h-100"
                                :class="{ 'border-primary bg-primary bg-opacity-10 text-primary fw-bold': registrationForm.participation_type === 'individual', 'bg-light': registrationForm.participation_type !== 'individual' }"
                                @click="setParticipationType('individual')"
                            >
                                <font-awesome-icon :icon="['fas', 'user']" class="fs-4 mb-2 d-block mx-auto" />
                                <span>Individualmente</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div
                                class="border rounded-3 p-3 text-center cursor-pointer h-100"
                                :class="{ 'border-primary bg-primary bg-opacity-10 text-primary fw-bold': registrationForm.participation_type === 'team', 'bg-light': registrationForm.participation_type !== 'team' }"
                                @click="setParticipationType('team')"
                            >
                                <font-awesome-icon :icon="['fas', 'users']" class="fs-4 mb-2 d-block mx-auto" />
                                <span>Com Equipe</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="registrationForm.participation_type === 'individual'" class="alert alert-info py-2 px-3 small mb-3">
                    <font-awesome-icon :icon="['fas', 'circle-info']" class="me-1" />
                    Você será inscrito individualmente. 
                    <span v-if="allowsTeam">Caso deseje, poderá integrar ou criar uma equipe posteriormente.</span>
                </div>

                <div v-if="registrationForm.participation_type === 'team'" class="mb-3">
                    <template v-if="userTeams.length > 0">
                        <label class="form-label fw-semibold">Selecione sua Equipe *</label>
                        <select v-model="registrationForm.team_id" class="form-select" required>
                            <option value="" disabled>Selecione uma equipe...</option>
                            <option v-for="team in userTeams" :key="team.id" :value="team.id">
                                {{ team.name }} ({{ team.members ? team.members.length : 1 }} integrante(s))
                            </option>
                        </select>
                        <small class="text-muted d-block mt-1">
                            A equipe deve cumprir entre {{ hackathon.min_team_members }} e {{ hackathon.max_team_members }} integrantes.
                        </small>
                    </template>
                    <template v-else>
                        <div class="alert alert-warning py-2 small mb-2">
                            Você ainda não integra nenhuma equipe. Você pode criar uma equipe agora ou inscrever-se individualmente para aguardar alocação.
                        </div>
                        <Link :href="route('participant.guilds.index')" class="btn btn-sm btn-outline-primary w-100 mb-2">
                            Criar Nova Equipe no Dashboard
                        </Link>
                        <button
                            v-if="allowsIndividual"
                            type="button"
                            class="btn btn-sm btn-secondary w-100"
                            @click="setParticipationType('individual')"
                        >
                            Mudar para Inscrição Individual
                        </button>
                    </template>
                </div>

                <div v-if="registrationForm.errors.registration" class="alert alert-danger py-2 small">
                    {{ registrationForm.errors.registration }}
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showRegisterModal = false">Cancelar</button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="registrationForm.processing || (registrationForm.participation_type === 'team' && userTeams.length === 0)"
                    >
                        Confirmar Inscrição
                    </button>
                </div>
            </form>
        </ModalDialog>

        <!-- Modal de Confirmação de Cancelamento -->
        <ModalConfirmation
            :is-visible="showCancelConfirm"
            message="Cancelar Inscrição"
            sub-message="Tem certeza que deseja cancelar sua inscrição neste Hackathon?"
            confirm-text="Sim, Cancelar"
            cancel-text="Voltar"
            @confirm="executeCancelRegistration"
            @close="showCancelConfirm = false"
        />
    </div>
</template>
