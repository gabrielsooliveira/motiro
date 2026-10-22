<script setup>
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ModalConfirmation from "@/js/Components/Modals/ModalConfirmation.vue";

const props = defineProps({
    registrations: {
        type: Array,
        default: () => [],
    },
    user: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const currentUser = computed(() => props.user || page.props.user);

const selectedRegistrationForCancel = ref(null);
const isCancelling = ref(false);

const stats = computed(() => {
    const total = props.registrations.length;
    const approved = props.registrations.filter((r) => r.status === "approved").length;
    const pending = props.registrations.filter((r) => r.status === "pending").length;
    const inTeam = props.registrations.filter((r) => r.group || r.team).length;
    return { total, approved, pending, inTeam };
});

const confirmCancel = (reg) => {
    selectedRegistrationForCancel.value = reg;
};

const executeCancel = () => {
    if (!selectedRegistrationForCancel.value) return;
    isCancelling.value = true;
    router.delete(route("registration.cancel", selectedRegistrationForCancel.value.id), {
        onFinish: () => {
            isCancelling.value = false;
            selectedRegistrationForCancel.value = null;
        },
    });
};
</script>

<template>
    <Head>
        <title>Meus Hackathons e Inscrições | Motirõ</title>
    </Head>

    <div class="py-2">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <h1 class="h2 fw-bold text-dark mb-0">Meus Hackathons</h1>
                <p class="text-muted small mb-0 mt-1">
                    Acompanhe o andamento das suas inscrições, formações de equipe e status de elegibilidade em cada evento.
                </p>
            </div>

            <div class="d-flex gap-2">
                <Link href="#" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" />
                    <span>Voltar ao Painel</span>
                </Link>
                <Link href="#" class="btn btn-primary d-flex align-items-center gap-2 shadow-sm">
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" />
                    <span>Explorar Hackathons</span>
                </Link>
            </div>
        </div>

        <!-- Cards de Estatísticas / Resumo -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="small text-muted text-uppercase fw-semibold d-block mb-1">Total de Inscrições</span>
                    <h3 class="fw-bold text-dark mb-0">{{ stats.total }}</h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="small text-muted text-uppercase fw-semibold d-block mb-1">Inscrições Aprovadas</span>
                    <h3 class="fw-bold text-success mb-0">{{ stats.approved }}</h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="small text-muted text-uppercase fw-semibold d-block mb-1">Em Análise</span>
                    <h3 class="fw-bold text-warning mb-0">{{ stats.pending }}</h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                    <span class="small text-muted text-uppercase fw-semibold d-block mb-1">Em Equipe / Grupo</span>
                    <h3 class="fw-bold text-primary mb-0">{{ stats.inTeam }}</h3>
                </div>
            </div>
        </div>

        <!-- Estado Vazio -->
        <div v-if="registrations.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-3">
            <div class="mb-3 text-muted">
                <font-awesome-icon :icon="['fas', 'trophy']" class="fs-1 opacity-50" />
            </div>
            <h4 class="fw-bold text-dark mb-2">Você ainda não se inscreveu em nenhum Hackathon.</h4>
            <p class="text-muted small mx-auto mb-4 col-md-8">
                Descubra competições incríveis, participe de desafios com prêmios e conecte-se com desenvolvedores e organizações inovadoras.
            </p>
            <div>
                <Link :href="route('participant.hackathons')" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" />
                    <span>Ver Hackathons Disponíveis</span>
                </Link>
            </div>
        </div>

        <!-- Tabela de Inscrições -->
        <div v-else class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th scope="col" class="ps-4 py-3">Hackathon</th>
                            <th scope="col" class="py-3">Modalidade / Formação</th>
                            <th scope="col" class="py-3">Elegibilidade</th>
                            <th scope="col" class="py-3">Status da Inscrição</th>
                            <th scope="col" class="text-end pe-4 py-3">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="reg in registrations" :key="reg.id">
                            <!-- Coluna do Hackathon -->
                            <td class="ps-4 py-3">
                                <strong class="text-dark d-block">{{ reg.hackathon?.title }}</strong>
                                <small class="text-muted">
                                    {{ reg.hackathon?.organization?.name || 'Motirõ' }} &bull;
                                    <span class="badge bg-light text-secondary border small">{{ reg.hackathon?.modality || 'Online' }}</span>
                                </small>
                            </td>

                            <!-- Modalidade / Formação -->
                            <td class="py-3">
                                <div v-if="reg.group">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <font-awesome-icon :icon="['fas', 'people-group']" class="me-1" />
                                        {{ reg.group.name }}
                                    </span>
                                    <small class="d-block text-muted mt-1">Grupo do Hackathon</small>
                                </div>
                                <div v-else-if="reg.team">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <font-awesome-icon :icon="['fas', 'users-rectangle']" class="me-1" />
                                        {{ reg.team.name }}
                                    </span>
                                    <small class="d-block text-muted mt-1">Equipe</small>
                                </div>
                                <div v-else>
                                    <span class="badge bg-secondary-subtle text-secondary">
                                        <font-awesome-icon :icon="['fas', 'user']" class="me-1" />
                                        Participação Individual
                                    </span>
                                </div>
                            </td>

                            <!-- Elegibilidade -->
                            <td class="py-3">
                                <span v-if="reg.is_eligible" class="badge bg-success-subtle text-success border border-success-subtle">
                                    <font-awesome-icon :icon="['fas', 'check']" class="me-1" /> Apto a competir
                                </span>
                                <span v-else class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                    <font-awesome-icon :icon="['fas', 'clock']" class="me-1" /> Pendente de Grupo
                                </span>
                            </td>

                            <!-- Status da Inscrição -->
                            <td class="py-3">
                                <span class="badge" :class="{
                                    'bg-success': reg.status === 'approved',
                                    'bg-warning text-dark': reg.status === 'pending',
                                    'bg-danger': reg.status === 'rejected',
                                    'bg-secondary': reg.status === 'cancelled'
                                }">
                                    {{ reg.status === 'approved' ? 'Aprovada' : (reg.status === 'pending' ? 'Em Análise' : (reg.status === 'cancelled' ? 'Cancelada' : reg.status)) }}
                                </span>
                            </td>

                            <!-- Ações -->
                            <td class="text-end pe-4 py-3">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <Link
                                        v-if="reg.hackathon"
                                        :href="route('hackathon.show', reg.hackathon.slug)"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        Acessar &rarr;
                                    </Link>
                                    <button
                                        v-if="reg.status !== 'cancelled' && reg.status !== 'disqualified'"
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Cancelar Inscrição"
                                        @click="confirmCancel(reg)"
                                    >
                                        <font-awesome-icon :icon="['fas', 'ban']" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de Confirmação de Cancelamento de Inscrição -->
        <ModalConfirmation
            :is-visible="Boolean(selectedRegistrationForCancel)"
            message="Cancelar Inscrição no Hackathon"
            :sub-message="selectedRegistrationForCancel ? `Tem certeza de que deseja cancelar sua inscrição em '${selectedRegistrationForCancel.hackathon?.title}'? Sua vaga individual será liberada e você sairá do grupo do evento.` : ''"
            confirm-text="Sim, Cancelar Inscrição"
            cancel-text="Voltar"
            @confirm="executeCancel"
            @close="selectedRegistrationForCancel = null"
        />
    </div>
</template>
