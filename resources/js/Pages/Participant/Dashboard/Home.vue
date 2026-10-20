<script setup>
import { Head, Link, usePage, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import CardSimple from "@/js/Components/Cards/CardSimple.vue";
import CompetitorDashboard from "@/js/Components/Team/CompetitorDashboard.vue";
import JudgeDashboard from "@/js/Components/Judge/JudgeDashboard.vue";

const page = usePage();
const { user, competitor, judge } = page.props;

const showCreateTeamModal = ref(false);

const teamForm = useForm({
    name: "",
    description: "",
    area_of_focus: "",
    invite_emails: "",
    set_as_leader: true,
});

const submitTeam = () => {
    teamForm.post(route("team.store"), {
        onSuccess: () => {
            showCreateTeamModal.value = false;
            teamForm.reset();
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
        <title>Área do Participante | Início</title>
    </Head>

    <section class="pt-2">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
                <div>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill small mb-1">
                        <font-awesome-icon :icon="['fas', 'trophy']" class="me-1" />
                        Área do Competidor
                    </span>
                    <h1 class="h2 fw-bold text-capitalize text-dark mb-0">Olá, {{ user.name }}</h1>
                    <p class="text-muted small mb-0 mt-1">Aqui está o resumo dos seus hackathons, equipes e projetos no Motirõ.</p>
                </div>

                <div class="d-flex gap-2">
                    <Link :href="route('participant.hackathons')" class="btn btn-outline-primary d-flex align-items-center gap-2">
                        <font-awesome-icon :icon="['fas', 'magnifying-glass']" />
                        <span>Explorar Hackathons</span>
                    </Link>
                    <button
                        type="button"
                        class="btn btn-primary d-flex align-items-center gap-2"
                        @click="showCreateTeamModal = true"
                    >
                        <font-awesome-icon :icon="['fas', 'plus']" />
                        <span>Nova Equipe (Guilda)</span>
                    </button>
                </div>
            </div>
            
            <div v-if="competitor?.available_hackathons && competitor.available_hackathons.length > 0" class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h5 fw-bold mb-0 text-dark">Hackathons Disponíveis</h2>
                        <span class="text-muted small">Eventos abertos para inscrição que você pode participar</span>
                    </div>
                    <Link :href="route('participant.hackathons')" class="text-primary text-decoration-none small fw-semibold">
                        Ver todos &rarr;
                    </Link>
                </div>

                <div class="row g-3">
                    <div
                        v-for="h in competitor.available_hackathons.slice(0, 3)"
                        :key="h.id"
                        class="col-md-4"
                    >
                        <div class="card h-100 border rounded-3 p-3 bg-light bg-opacity-25 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-light text-secondary border small">{{ h.modality || 'Online' }}</span>
                                    <span class="badge bg-success-subtle text-success small">Inscrições Abertas</span>
                                </div>
                                <h3 class="h6 fw-bold text-dark mb-1">{{ h.title }}</h3>
                                <p class="text-muted small mb-2 text-truncate">{{ h.organization ? h.organization.name : 'Motirõ' }}</p>
                            </div>
                            <div class="pt-2 border-top mt-2">
                                <Link :href="route('hackathon.show', h.slug)" class="btn btn-sm btn-outline-primary w-100">
                                    Ver Detalhes e Inscrever
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            
            <CompetitorDashboard
                :teams="competitor?.teams || []"
                :groups="competitor?.groups || []"
                :registrations="competitor?.registrations || []"
                :invitations="competitor?.invitations || []"
                :projects="competitor?.projects || []"
                @create-team="showCreateTeamModal = true"
                @accept-invite="handleAcceptInvite"
                @decline-invite="handleDeclineInvite"
            />

            
            <div v-if="judge?.assignments?.length > 0" class="dashboard-role-panel dashboard-role-panel--judge mt-4">
                <div class="dashboard-role-panel__header">
                    <font-awesome-icon :icon="['fas', 'gavel']" />
                    <h2 class="mb-0">Painel do Juiz / Avaliador</h2>
                </div>
                <JudgeDashboard
                    :assignments="judge?.assignments || []"
                    :pending-count="judge?.pendingCount || 0"
                    :completed-count="judge?.completedCount || 0"
                />
            </div>

            
            <div v-if="showCreateTeamModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content rounded-4 border-0 shadow">
                        <form @submit.prevent="submitTeam">
                            <div class="modal-header border-bottom">
                                <div>
                                    <h5 class="modal-title fw-bold">Criar Equipe Permanente (Guilda)</h5>
                                    <span class="small text-muted">Guilda independente de hackathons para reunir participantes</span>
                                </div>
                                <button type="button" class="btn-close" @click="showCreateTeamModal = false"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="alert alert-info py-2 small mb-3">
                                    <font-awesome-icon :icon="['fas', 'circle-info']" class="me-1" />
                                    Esta equipe é permanente e pertence à sua conta. Ela não inscreve seus integrantes automaticamente em nenhum hackathon.
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nome da Guilda *</label>
                                    <input v-model="teamForm.name" type="text" class="form-control" placeholder="ex: ByteCraft, InovaTech" required />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Área de Foco</label>
                                    <input v-model="teamForm.area_of_focus" type="text" class="form-control" placeholder="ex: Fintech, IA, Saúde, Sustentabilidade" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Descrição</label>
                                    <textarea v-model="teamForm.description" class="form-control" rows="3" placeholder="Apresentação da equipe e objetivos..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Convidar Integrantes por E-mail (opcional)</label>
                                    <input v-model="teamForm.invite_emails" type="text" class="form-control" placeholder="ex: dev1@email.com, dev2@email.com" />
                                    <div class="form-text small">E-mails separados por vírgula. Se o convidado ainda não tiver conta, ele receberá o convite para registro.</div>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="set_as_leader" v-model="teamForm.set_as_leader" />
                                    <label class="form-check-label fw-semibold small" for="set_as_leader">
                                        Definir-me como líder da equipe
                                    </label>
                                    <div class="form-text small">Se desmarcado, a equipe será criada sem líder inicial e os membros poderão eleger o líder livremente.</div>
                                </div>
                            </div>
                            <div class="modal-footer border-top bg-light rounded-bottom-4">
                                <button type="button" class="btn btn-secondary" @click="showCreateTeamModal = false">Cancelar</button>
                                <button type="submit" class="btn btn-primary" :disabled="teamForm.processing">Criar Equipe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
</template>
