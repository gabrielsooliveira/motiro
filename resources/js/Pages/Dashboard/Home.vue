<script setup>
import { Head, Link, usePage, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import CardSimple from "@/js/Components/Cards/CardSimple.vue";
import CompetitorDashboard from "@/js/Components/Team/CompetitorDashboard.vue";
import JudgeDashboard from "@/js/Components/Judge/JudgeDashboard.vue";
import OrganizerDashboard from "@/js/Components/Hackathon/OrganizerDashboard.vue";

const page = usePage();
const { user, competitor, judge, admin } = page.props;

// Preserving existing summary cards section
const items = [
    {
        title: "Hackathons Ativos",
        value: admin?.hackathons?.length || competitor?.registrations?.length || 0,
        type: "number",
        icon: ["fas", "chess-board"],
    },
    {
        title: "Atividades Pendentes",
        value: judge?.pendingCount || competitor?.invitations?.length || 0,
        type: "number",
        icon: ["fas", "scroll"],
    },
    {
        title: "Equipes",
        value: competitor?.teams?.length || 0,
        type: "number",
        icon: ["fas", "users"],
    },
];

// Modals State
const showCreateTeamModal = ref(false);
const showCreateHackathonModal = ref(false);

const teamForm = useForm({
    name: "",
    description: "",
    area_of_focus: "",
});

const hackathonForm = useForm({
    organization_id: admin?.organizations?.[0]?.id || "",
    title: "",
    description: "",
    modality: "online",
    min_team_members: 1,
    max_team_members: 5,
});

const submitTeam = () => {
    teamForm.post(route("team.store"), {
        onSuccess: () => {
            showCreateTeamModal.value = false;
            teamForm.reset();
        },
    });
};

const submitHackathon = () => {
    hackathonForm.post(route("hackathon.manage.store"), {
        onSuccess: () => {
            showCreateHackathonModal.value = false;
            hackathonForm.reset();
        },
    });
};

const handleAcceptInvite = (id) => {
    router.post(route("team.invite.accept", id));
};

const handleDeclineInvite = (id) => {
    router.post(route("team.invite.decline", id));
};
</script>

<template>
    <Head>
        <title>Dashboard | Início</title>
    </Head>

    <section class="pt-3">
        <!-- 1. Preserved Top Summary Section -->
        <h1 class="fw-semibold text-capitalize">Olá, {{ user.name }}</h1>
        <p class="text-muted">Aqui está o resumo das suas atividades no Motirõ.</p>

        <div class="row g-3 mb-4">
            <div class="col-lg-4" v-for="(item, index) in items" :key="index">
                <CardSimple
                    :title="item.title"
                    :value="item.value"
                    :type="item.type"
                    :icon="item.icon"
                />
            </div>
        </div>

        <!-- 2. Modular Role Sections -->

        <!-- Primary: Competitor Dashboard (always shown) -->
        <CompetitorDashboard
            :teams="competitor?.teams || []"
            :registrations="competitor?.registrations || []"
            :invitations="competitor?.invitations || []"
            :projects="competitor?.projects || []"
            @create-team="showCreateTeamModal = true"
            @accept-invite="handleAcceptInvite"
            @decline-invite="handleDeclineInvite"
        />

        <!-- Supplementary: Judge Panel (only when user has assignments) -->
        <div v-if="judge?.assignments?.length > 0" class="dashboard-role-panel dashboard-role-panel--judge mt-4">
            <div class="dashboard-role-panel__header">
                <font-awesome-icon :icon="['fas', 'gavel']" />
                <h2 class="mb-0">Painel do Juiz</h2>
            </div>
            <JudgeDashboard
                :assignments="judge?.assignments || []"
                :pending-count="judge?.pendingCount || 0"
                :completed-count="judge?.completedCount || 0"
            />
        </div>

        <!-- Supplementary: Organizer Panel (only when user manages orgs) -->
        <div v-if="admin?.organizations?.length > 0" class="dashboard-role-panel dashboard-role-panel--org mt-4">
            <div class="dashboard-role-panel__header">
                <font-awesome-icon :icon="['fas', 'crown']" />
                <h2 class="mb-0">Painel da Organização</h2>
            </div>
            <OrganizerDashboard
                :organizations="admin?.organizations || []"
                :hackathons="admin?.hackathons || []"
                @create-hackathon="showCreateHackathonModal = true"
            />
        </div>

        <!-- Modal Create Team -->
        <div v-if="showCreateTeamModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitTeam">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Criar Nova Equipe</h5>
                            <button type="button" class="btn-close" @click="showCreateTeamModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nome da Equipe</label>
                                <input v-model="teamForm.name" type="text" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Área de Foco</label>
                                <input v-model="teamForm.area_of_focus" type="text" class="form-control" placeholder="ex: Fintech, IA, Saúde" />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrição</label>
                                <textarea v-model="teamForm.description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showCreateTeamModal = false">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="teamForm.processing">Criar Equipe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Create Hackathon -->
        <div v-if="showCreateHackathonModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form @submit.prevent="submitHackathon">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Criar Hackathon</h5>
                            <button type="button" class="btn-close" @click="showCreateHackathonModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Organização</label>
                                <select v-model="hackathonForm.organization_id" class="form-select" required>
                                    <option v-for="org in admin.organizations" :key="org.id" :value="org.id">
                                        {{ org.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Título do Hackathon</label>
                                <input v-model="hackathonForm.title" type="text" class="form-control" required />
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Modalidade</label>
                                <select v-model="hackathonForm.modality" class="form-select">
                                    <option value="online">Online</option>
                                    <option value="presential">Presencial</option>
                                    <option value="hybrid">Híbrido</option>
                                </select>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label class="form-label">Min. Integrantes</label>
                                    <input v-model="hackathonForm.min_team_members" type="number" class="form-control" min="1" required />
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Max. Integrantes</label>
                                    <input v-model="hackathonForm.max_team_members" type="number" class="form-control" min="1" required />
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descrição</label>
                                <textarea v-model="hackathonForm.description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showCreateHackathonModal = false">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="hackathonForm.processing">Criar Hackathon</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</template>
