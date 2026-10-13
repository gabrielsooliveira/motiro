<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
    rankings: {
        type: Array,
        default: null,
    },
    canManageStaff: {
        type: Boolean,
        default: false,
    },
});

const activeTab = ref("registrations");

// Modals
const showAddMemberModal = ref(false);
const showAddChallengeModal = ref(false);

const memberForm = useForm({
    user_id: "",
    role: "collaborator",
});

const challengeForm = useForm({
    title: "",
    description: "",
    requirements: "",
    prize: "",
    order: 1,
});

const handleStatusUpdate = (registrationId, status) => {
    router.patch(route("registration.status.update", registrationId), {
        status: status,
    });
};

const handleDistributeJudging = () => {
    if (confirm("Deseja distribuir os projetos submetidos entre os juízes cadastrados?")) {
        router.post(route("hackathon.manage.distribute", props.hackathon.id));
    }
};

const handlePublishResults = () => {
    if (confirm("Deseja publicar os resultados e finalizar este Hackathon?")) {
        router.post(route("hackathon.manage.publish", props.hackathon.id));
    }
};

// Staff management (Sections 9 & 10)
const submitAddMember = () => {
    memberForm.post(route("organization.hackathon.members.store", props.hackathon.id), {
        onSuccess: () => {
            showAddMemberModal.value = false;
            memberForm.reset();
        },
    });
};

const removeStaffMember = (memberId, name) => {
    if (confirm(`Remover ${name} da equipe responsável deste hackathon?`)) {
        router.delete(route("organization.hackathon.members.destroy", [props.hackathon.id, memberId]));
    }
};

const updateStaffRole = (memberId, newRole) => {
    router.put(route("organization.hackathon.members.update", [props.hackathon.id, memberId]), {
        role: newRole,
    });
};

// Challenge management
const submitAddChallenge = () => {
    challengeForm.post(route("organization.hackathon.challenges.store", props.hackathon.id), {
        onSuccess: () => {
            showAddChallengeModal.value = false;
            challengeForm.reset();
        },
    });
};

const deleteChallenge = (challengeId, title) => {
    if (confirm(`Remover o desafio '${title}'?`)) {
        router.delete(route("organization.hackathon.challenges.destroy", [props.hackathon.id, challengeId]));
    }
};

// Organization members available to be added
const availableOrgUsers = computed(() => {
    const orgUsers = props.hackathon.organization?.users || [];
    const currentMemberIds = (props.hackathon.members || []).map(m => m.user_id);
    return orgUsers.filter(u => !currentMemberIds.includes(u.id));
});

const getStaffRoleBadge = (role) => {
    switch (role) {
        case "admin": return { label: "Administrador", class: "bg-primary text-white" };
        case "manager": return { label: "Gestor", class: "bg-success text-white" };
        case "collaborator": return { label: "Colaborador", class: "bg-info text-dark" };
        default: return { label: role, class: "bg-secondary text-white" };
    }
};
</script>

<template>
    <Head>
        <title>Gerenciar | {{ hackathon.title }}</title>
    </Head>

    <div class="py-2">
            <!-- Hackathon Header (Section 11) -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <Link :href="route('organization.dashboard')" class="text-decoration-none text-muted small d-inline-block mb-1">
                        <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                        Voltar para o Painel da Organização
                    </Link>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="h2 fw-bold text-dark mb-0">{{ hackathon.title }}</h1>
                        <span class="badge bg-light text-secondary border text-uppercase small">{{ hackathon.modality }}</span>
                        <span class="badge bg-primary text-uppercase small">{{ hackathon.status }}</span>
                        <span v-if="hackathon.organization" class="badge bg-secondary-subtle text-secondary small">
                            {{ hackathon.organization.name }}
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-sm btn-outline-info d-flex align-items-center gap-1" @click="handleDistributeJudging">
                        <font-awesome-icon :icon="['fas', 'share-nodes']" />
                        <span>Distribuir para Juízes</span>
                    </button>
                    <button class="btn btn-sm btn-success d-flex align-items-center gap-1" @click="handlePublishResults">
                        <font-awesome-icon :icon="['fas', 'award']" />
                        <span>Publicar Resultados</span>
                    </button>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs mb-4 flex-nowrap overflow-x-auto">
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'registrations' }" @click="activeTab = 'registrations'">
                        Inscrições ({{ hackathon.registrations?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'projects' }" @click="activeTab = 'projects'">
                        Projetos ({{ hackathon.projects?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'staff' }" @click="activeTab = 'staff'">
                        <font-awesome-icon :icon="['fas', 'user-shield']" class="me-1" />
                        Equipe do Hackathon ({{ hackathon.members?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'challenges' }" @click="activeTab = 'challenges'">
                        <font-awesome-icon :icon="['fas', 'bullseye']" class="me-1" />
                        Desafios ({{ hackathon.challenges?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'stages' }" @click="activeTab = 'stages'">
                        Etapas ({{ hackathon.stages?.length || 0 }})
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'criteria' }" @click="activeTab = 'criteria'">
                        Critérios ({{ hackathon.evaluation_criteria?.length || 0 }})
                    </button>
                </li>
                <li v-if="rankings" class="nav-item">
                    <button class="nav-link" :class="{ active: activeTab === 'rankings' }" @click="activeTab = 'rankings'">
                        Ranking Oficial
                    </button>
                </li>
            </ul>

            <!-- Tab 1: Registrations Management -->
            <div v-if="activeTab === 'registrations'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body">
                    <div v-if="!hackathon.registrations || hackathon.registrations.length === 0" class="text-center py-5 text-muted">
                        <font-awesome-icon :icon="['fas', 'clipboard-list']" class="fs-2 mb-2 opacity-50" />
                        <p class="mb-0">Nenhuma inscrição registrada até o momento.</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Equipe</th>
                                    <th>Integrantes</th>
                                    <th>Status</th>
                                    <th class="text-end">Ações Administrativas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="reg in hackathon.registrations" :key="reg.id">
                                    <td><strong>{{ reg.team ? reg.team.name : 'Equipe' }}</strong></td>
                                    <td>{{ reg.team && reg.team.members ? reg.team.members.length : '-' }} membro(s)</td>
                                    <td>
                                        <span class="badge" :class="{
                                            'bg-success': reg.status === 'approved',
                                            'bg-warning text-dark': reg.status === 'pending',
                                            'bg-danger': reg.status === 'rejected',
                                            'bg-dark': reg.status === 'disqualified',
                                        }">{{ reg.status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button v-if="reg.status !== 'approved'" class="btn btn-sm btn-success me-2" @click="handleStatusUpdate(reg.id, 'approved')">
                                            Aprovar
                                        </button>
                                        <button v-if="reg.status !== 'rejected'" class="btn btn-sm btn-outline-danger me-2" @click="handleStatusUpdate(reg.id, 'rejected')">
                                            Rejeitar
                                        </button>
                                        <button v-if="reg.status !== 'disqualified'" class="btn btn-sm btn-dark" @click="handleStatusUpdate(reg.id, 'disqualified')">
                                            Desclassificar
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Projects -->
            <div v-if="activeTab === 'projects'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body">
                    <div v-if="!hackathon.projects || hackathon.projects.length === 0" class="text-center py-5 text-muted">
                        <font-awesome-icon :icon="['fas', 'file-arrow-up']" class="fs-2 mb-2 opacity-50" />
                        <p class="mb-0">Nenhum projeto submetido até o momento.</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Projeto</th>
                                    <th>Equipe</th>
                                    <th>Status</th>
                                    <th>Links</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="proj in hackathon.projects" :key="proj.id">
                                    <td><strong>{{ proj.title }}</strong></td>
                                    <td>{{ proj.team ? proj.team.name : '-' }}</td>
                                    <td><span class="badge bg-primary">{{ proj.status }}</span></td>
                                    <td>
                                        <a v-if="proj.github_repository_url" :href="proj.github_repository_url" target="_blank" class="btn btn-sm btn-outline-dark me-2">GitHub</a>
                                        <a v-if="proj.demo_url" :href="proj.demo_url" target="_blank" class="btn btn-sm btn-outline-primary">Demo</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Equipe do Hackathon (Sections 9 & 10) -->
            <div v-if="activeTab === 'staff'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h6 fw-bold mb-0 text-dark">Equipe Responsável pelo Hackathon</h2>
                        <span class="text-muted small">Usuários da instituição autorizados a administrar este hackathon específico</span>
                    </div>
                    <button
                        v-if="canManageStaff"
                        type="button"
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                        @click="showAddMemberModal = true"
                    >
                        <font-awesome-icon :icon="['fas', 'user-plus']" />
                        <span>Adicionar Responsável</span>
                    </button>
                </div>

                <div class="card-body p-0">
                    <div v-if="!hackathon.members || hackathon.members.length === 0" class="text-center py-5 text-muted">
                        <font-awesome-icon :icon="['fas', 'user-shield']" class="fs-2 mb-2 opacity-50" />
                        <p class="mb-0">Nenhum responsável designado especificamente para este hackathon.</p>
                        <small class="text-muted">Apenas administradores gerais da instituição têm acesso no momento.</small>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th scope="col" class="ps-3">Usuário</th>
                                    <th scope="col">Função no Hackathon</th>
                                    <th scope="col">Designado Em</th>
                                    <th v-if="canManageStaff" scope="col" class="text-end pe-3">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="staff in hackathon.members" :key="staff.id">
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                                                {{ staff.user?.name?.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark d-block">{{ staff.user?.name }}</span>
                                                <span class="text-muted small">{{ staff.user?.email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge rounded-pill" :class="getStaffRoleBadge(staff.role).class">
                                                {{ getStaffRoleBadge(staff.role).label }}
                                            </span>
                                            <select
                                                v-if="canManageStaff"
                                                class="form-select form-select-sm"
                                                style="width: 140px; font-size: 0.8rem;"
                                                :value="staff.role"
                                                @change="updateStaffRole(staff.id, $event.target.value)"
                                            >
                                                <option value="admin">Administrador</option>
                                                <option value="manager">Gestor</option>
                                                <option value="collaborator">Colaborador</option>
                                            </select>
                                        </div>
                                    </td>
                                    <td class="text-muted small">
                                        {{ staff.created_at ? new Date(staff.created_at).toLocaleDateString('pt-BR') : '-' }}
                                    </td>
                                    <td v-if="canManageStaff" class="text-end pe-3">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="removeStaffMember(staff.id, staff.user?.name)"
                                            title="Remover deste hackathon"
                                        >
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Desafios (Challenges) -->
            <div v-if="activeTab === 'challenges'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="h6 fw-bold mb-0 text-dark">Desafios do Hackathon</h2>
                        <span class="text-muted small">Temas, problemas e trilhas propostos para resolução pelas equipes</span>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-primary d-flex align-items-center gap-1"
                        @click="showAddChallengeModal = true"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Novo Desafio</span>
                    </button>
                </div>

                <div class="card-body">
                    <div v-if="!hackathon.challenges || hackathon.challenges.length === 0" class="text-center py-5 text-muted">
                        <font-awesome-icon :icon="['fas', 'bullseye']" class="fs-2 mb-2 opacity-50" />
                        <p class="mb-0">Nenhum desafio cadastrado ainda.</p>
                        <small class="text-muted">Adicione os desafios para orientar os participantes sobre os temas a desenvolver.</small>
                    </div>

                    <div v-else class="row g-3">
                        <div v-for="ch in hackathon.challenges" :key="ch.id" class="col-md-6">
                            <div class="card border rounded-3 p-3 h-100 bg-light bg-opacity-25">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-primary-subtle text-primary">Desafio #{{ ch.order }}</span>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger border-0 p-1"
                                        @click="deleteChallenge(ch.id, ch.title)"
                                    >
                                        <font-awesome-icon :icon="['fas', 'trash']" />
                                    </button>
                                </div>
                                <h5 class="fw-bold text-dark mb-2">{{ ch.title }}</h5>
                                <p v-if="ch.description" class="text-muted small mb-2">{{ ch.description }}</p>
                                <div v-if="ch.requirements" class="mb-2">
                                    <span class="small fw-semibold text-secondary d-block">Requisitos:</span>
                                    <p class="text-muted small mb-0">{{ ch.requirements }}</p>
                                </div>
                                <div v-if="ch.prize" class="mt-auto pt-2 border-top">
                                    <span class="small fw-bold text-success">
                                        <font-awesome-icon :icon="['fas', 'gift']" class="me-1" />
                                        Premiação: {{ ch.prize }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Stages -->
            <div v-if="activeTab === 'stages'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-4">
                    <div v-for="stage in hackathon.stages" :key="stage.id" class="timeline-stage-item mb-3 pb-3 border-bottom">
                        <h6 class="fw-bold mb-1">{{ stage.name }}</h6>
                        <small class="text-muted d-block mb-1">
                            {{ new Date(stage.start_date).toLocaleDateString('pt-BR') }} até {{ new Date(stage.end_date).toLocaleDateString('pt-BR') }}
                        </small>
                        <p class="mb-0 text-secondary">{{ stage.description || 'Sem descrição adicional.' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab 6: Evaluation Criteria -->
            <div v-if="activeTab === 'criteria'" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div v-for="crit in hackathon.evaluation_criteria" :key="crit.id" class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light bg-opacity-25 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold mb-0">{{ crit.name }}</h6>
                                    <span class="badge bg-info text-dark">Peso: {{ (crit.weight * 100).toFixed(0) }}%</span>
                                </div>
                                <small class="text-muted">Notas de {{ crit.min_score }} até {{ crit.max_score }}</small>
                                <p class="mb-0 mt-2 text-secondary">{{ crit.description || 'Critério padrão de avaliação.' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 7: Official Rankings -->
            <div v-if="activeTab === 'rankings' && rankings" class="card border-0 shadow-sm rounded-3 bg-white">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Posição</th>
                                    <th>Projeto</th>
                                    <th>Equipe</th>
                                    <th>Avaliações</th>
                                    <th>Nota Final</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in rankings" :key="item.project.id">
                                    <td><strong class="fs-5">#{{ idx + 1 }}</strong></td>
                                    <td>{{ item.project.title }}</td>
                                    <td>{{ item.project.team ? item.project.team.name : '-' }}</td>
                                    <td>{{ item.evaluations_count }}</td>
                                    <td><span class="badge bg-success fs-6">{{ item.final_score }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Add Staff Member (Sections 9 & 10) -->
        <div v-if="showAddMemberModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitAddMember">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Adicionar Responsável ao Hackathon</h5>
                                <span class="small text-muted">{{ hackathon.title }}</span>
                            </div>
                            <button type="button" class="btn-close" @click="showAddMemberModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Usuário da Instituição *</label>
                                <select v-model="memberForm.user_id" class="form-select" required>
                                    <option value="" disabled>Selecione um usuário da instituição</option>
                                    <option v-for="user in availableOrgUsers" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <div v-if="availableOrgUsers.length === 0" class="form-text text-warning small mt-1">
                                    Todos os membros da instituição já estão vinculados ou nenhum está cadastrado.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Função neste Hackathon *</label>
                                <select v-model="memberForm.role" class="form-select" required>
                                    <option value="collaborator">Colaborador (Visualiza e apoia o hackathon)</option>
                                    <option value="manager">Gestor (Gerencia etapas, inscrições e submissões)</option>
                                    <option value="admin">Administrador (Controle total deste hackathon)</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showAddMemberModal = false">Cancelar</button>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="memberForm.processing || !memberForm.user_id"
                            >
                                Adicionar à Equipe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Add Challenge -->
        <div v-if="showAddChallengeModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitAddChallenge">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Novo Desafio do Hackathon</h5>
                                <span class="small text-muted">{{ hackathon.title }}</span>
                            </div>
                            <button type="button" class="btn-close" @click="showAddChallengeModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Título do Desafio *</label>
                                <input v-model="challengeForm.title" type="text" class="form-control" placeholder="ex: Solução para Eficiência Energética Urbana" required />
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Descrição do Problema</label>
                                <textarea v-model="challengeForm.description" class="form-control" rows="3" placeholder="Contextualize a dor a ser resolvida..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Requisitos / Regras Específicas</label>
                                <textarea v-model="challengeForm.requirements" class="form-control" rows="2" placeholder="Tecnologias obrigatórias, formato de protótipo, etc."></textarea>
                            </div>

                            <div class="row g-2">
                                <div class="col-8">
                                    <label class="form-label fw-semibold">Premiação (opcional)</label>
                                    <input v-model="challengeForm.prize" type="text" class="form-control" placeholder="ex: R$ 5.000 + Incubação" />
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Ordem</label>
                                    <input v-model="challengeForm.order" type="number" class="form-control" min="1" />
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showAddChallengeModal = false">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="challengeForm.processing">
                                Salvar Desafio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>
