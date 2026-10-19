<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import HackathonDiscoveryCard from "@/js/Components/Hackathon/HackathonDiscoveryCard.vue";
import HackathonFilters from "@/js/Components/Hackathon/HackathonFilters.vue";
import Pagination from "@/js/Components/Pagination.vue";

const props = defineProps({
    hackathons: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    userTeams: {
        type: Array,
        default: () => [],
    },
});

const selectedHackathon = ref(null);
const showRegisterModal = ref(false);

const registrationForm = useForm({
    hackathon_id: "",
    participation_type: "individual",
    team_id: "",
});

watch(
    () => registrationForm.participation_type,
    (newVal) => {
        if (newVal === "individual") {
            registrationForm.team_id = "";
        } else if (newVal === "team" && !registrationForm.team_id) {
            registrationForm.team_id = props.userTeams?.[0]?.id || "";
        }
    }
);

const allowsIndividual = computed(() => {
    if (!selectedHackathon.value) return true;
    return selectedHackathon.value.registration_type !== "team_only";
});

const allowsTeam = computed(() => {
    if (!selectedHackathon.value) return true;
    return selectedHackathon.value.registration_type !== "individual";
});

function openRegisterModal(hackathon) {
    selectedHackathon.value = hackathon;
    registrationForm.hackathon_id = hackathon.id;

    if (hackathon.registration_type === "team_only") {
        registrationForm.participation_type = "team";
        registrationForm.team_id = props.userTeams?.[0]?.id || "";
    } else {
        registrationForm.participation_type = "individual";
        registrationForm.team_id = "";
    }

    showRegisterModal.value = true;
}

function closeRegisterModal() {
    showRegisterModal.value = false;
    selectedHackathon.value = null;
    registrationForm.reset();
    registrationForm.clearErrors();
}

function submitRegistration() {
    registrationForm.post(route("registration.store"), {
        preserveScroll: true,
        onSuccess: () => {
            closeRegisterModal();
        },
    });
}
</script>

<template>
    <Head title="Explorar Hackathons" />

    <div class="participant-discovery-page py-2">
        
        <header class="discovery-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="discovery-header__title">
                    Explorar Hackathons
                </h1>
                <p class="discovery-header__subtitle">
                    Descubra desafios, forme equipes e construa soluções inovadoras na plataforma Motirõ.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <Link
                    :href="route('participant.dashboard')"
                    class="btn btn-outline-secondary btn-sm"
                >
                    <font-awesome-icon :icon="['fas', 'arrow-left']" class="me-1" />
                    Meu Painel
                </Link>
            </div>
        </header>

        
        <HackathonFilters
            :filters="filters"
            :total-count="hackathons.total"
        />

        
        <section v-if="hackathons.data && hackathons.data.length > 0" aria-label="Lista de Hackathons">
            <div class="row g-4">
                <div
                    v-for="hackathon in hackathons.data"
                    :key="hackathon.id"
                    class="col-12 col-md-6 col-lg-4"
                >
                    <HackathonDiscoveryCard
                        :hackathon="hackathon"
                        @register="openRegisterModal"
                    />
                </div>
            </div>

            
            <div class="mt-5 d-flex justify-content-center">
                <Pagination :links="hackathons.links" />
            </div>
        </section>

        
        <section v-else class="empty-state py-5 text-center bg-surface border rounded-4 p-5" aria-label="Nenhum hackathon encontrado">
            <div class="empty-state__icon-wrapper mb-3">
                <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="display-4 text-muted" />
            </div>
            <h2 class="h4 fw-bold text-dark mb-2">Nenhum hackathon encontrado</h2>
            <p class="text-secondary max-w-md mx-auto mb-4">
                Não localizamos nenhum evento correspondente aos filtros selecionados. Tente ajustar os termos de busca ou remover as restrições de filtro.
            </p>
            <div>
                <Link :href="route('participant.hackathons')" class="btn btn-primary">
                    <font-awesome-icon :icon="['fas', 'rotate-left']" class="me-1" />
                    Redefinir filtros
                </Link>
            </div>
        </section>

        
        <div
            v-if="showRegisterModal && selectedHackathon"
            class="modal-backdrop fade show"
            @click="closeRegisterModal"
        ></div>

        <div
            v-if="showRegisterModal && selectedHackathon"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="registerModalTitle"
        >
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0">
                        <div>
                            <h2 id="registerModalTitle" class="modal-title h5 fw-bold text-dark">
                                Inscrição no Hackathon
                            </h2>
                            <p class="small text-muted mb-0">
                                {{ selectedHackathon.title }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="btn-close"
                            aria-label="Fechar"
                            @click="closeRegisterModal"
                        ></button>
                    </div>

                    <form @submit.prevent="submitRegistration">
                        <div class="modal-body py-4">
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-dark">
                                    Como deseja participar?
                                </label>

                                <div class="d-flex flex-column gap-2">
                                    <div v-if="allowsIndividual" class="form-check p-3 border rounded-3">
                                        <input
                                            id="regTypeIndividual"
                                            v-model="registrationForm.participation_type"
                                            class="form-check-input"
                                            type="radio"
                                            value="individual"
                                        />
                                        <label class="form-check-label w-100 cursor-pointer" for="regTypeIndividual">
                                            <div class="fw-semibold text-dark">Participação Individual</div>
                                            <div class="small text-muted">Você participará do evento de forma autônoma.</div>
                                        </label>
                                    </div>

                                    <div v-if="allowsTeam" class="form-check p-3 border rounded-3">
                                        <input
                                            id="regTypeTeam"
                                            v-model="registrationForm.participation_type"
                                            class="form-check-input"
                                            type="radio"
                                            value="team"
                                        />
                                        <label class="form-check-label w-100 cursor-pointer" for="regTypeTeam">
                                            <div class="fw-semibold text-dark">Participação por Equipe</div>
                                            <div class="small text-muted">Inscreva uma de suas equipes cadastradas no evento.</div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            
                            <div v-if="registrationForm.participation_type === 'team'" class="mb-3">
                                <label class="form-label fw-semibold text-dark" for="teamSelector">
                                    Selecione a sua equipe
                                </label>

                                <div v-if="userTeams && userTeams.length > 0">
                                    <select
                                        id="teamSelector"
                                        v-model="registrationForm.team_id"
                                        class="form-select"
                                        required
                                    >
                                        <option value="" disabled>Escolha uma equipe...</option>
                                        <option
                                            v-for="team in userTeams"
                                            :key="team.id"
                                            :value="team.id"
                                        >
                                            {{ team.name }}
                                        </option>
                                    </select>
                                </div>

                                <div v-else class="alert alert-warning small mb-0">
                                    <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                                    Você ainda não possui equipes cadastradas.
                                    <span class="d-block mt-1">
                                        Crie uma equipe antes de realizar a inscrição por equipe.
                                    </span>
                                </div>

                                <div v-if="registrationForm.errors.team_id" class="text-danger small mt-1">
                                    {{ registrationForm.errors.team_id }}
                                </div>
                            </div>

                            <div v-if="registrationForm.errors.hackathon_id" class="text-danger small mt-1">
                                {{ registrationForm.errors.hackathon_id }}
                            </div>

                            <div v-if="registrationForm.errors.registration" class="alert alert-danger py-2 small mt-2">
                                <font-awesome-icon :icon="['fas', 'circle-exclamation']" class="me-1" />
                                {{ registrationForm.errors.registration }}
                            </div>
                        </div>

                        <div class="modal-footer border-top-0 pt-0">
                            <button
                                type="button"
                                class="btn btn-outline-secondary"
                                @click="closeRegisterModal"
                            >
                                Cancelar
                            </button>

                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="registrationForm.processing || (registrationForm.participation_type === 'team' && !registrationForm.team_id)"
                            >
                                <span
                                    v-if="registrationForm.processing"
                                    class="spinner-border spinner-border-sm me-1"
                                    role="status"
                                    aria-hidden="true"
                                ></span>
                                Confirmar Inscrição
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
