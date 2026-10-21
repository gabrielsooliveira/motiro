<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import ModalDialog from "@/js/Components/Modals/ModalDialog.vue";

const props = defineProps({
    registrations: {
        type: Array,
        default: () => [],
    },
    guilds: {
        type: Array,
        default: () => [],
    },
});

const showFormModal = ref(false);
const selectedRegistration = ref(null);

const form = useForm({
    name: "",
    area_of_focus: "",
    technologies: "",
    origin_guild_id: "",
});

const openFormModal = (reg) => {
    selectedRegistration.value = reg;
    form.reset();
    showFormModal.value = true;
};

const submitCreateTeamForHackathon = () => {
    if (!selectedRegistration.value?.hackathon_id) return;

    // Se selecionou uma guilda de origem, usa a rota especializada de formar a partir da guilda
    if (form.origin_guild_id && route().has("participant.groups.form_from_guild")) {
        form.post(route("participant.groups.form_from_guild", selectedRegistration.value.hackathon_id), {
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
        return;
    }

    // Caso contrário, cria equipe padrão
    if (route().has("participant.groups.store")) {
        form.post(route("participant.groups.store", selectedRegistration.value.hackathon_id), {
            onSuccess: () => {
                showFormModal.value = false;
            },
        });
    }
};
</script>

<template>
    <div>
        <div v-if="!registrations || registrations.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white">
            <font-awesome-icon :icon="['fas', 'trophy']" class="fs-1 text-warning opacity-50 mb-3" />
            <h5 class="fw-bold text-dark">Nenhum hackathon inscrito</h5>
            <p class="text-secondary small mb-4">Você ainda não realizou inscrições em hackathons para formar equipes.</p>
            <div>
                <Link :href="route('participant.hackathons')" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm">
                    Explorar Hackathons Abertos
                </Link>
            </div>
        </div>

        <div v-else class="row g-4">
            <div
                v-for="reg in registrations"
                :key="reg.id"
                class="col-12 col-lg-6"
            >
                <div class="card border-0 shadow-sm rounded-4 bg-white h-100 d-flex flex-column justify-content-between p-4">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small">
                                {{ reg.hackathon?.organization?.name || 'Evento Oficial' }}
                            </span>
                            <span class="badge" :class="{
                                'bg-success': reg.status === 'approved',
                                'bg-warning text-dark': reg.status === 'pending',
                                'bg-secondary': reg.status === 'cancelled'
                            }">
                                {{ reg.status === 'approved' ? 'Inscrição Aprovada' : 'Pendente' }}
                            </span>
                        </div>

                        <h5 class="fw-bold text-dark mb-1">{{ reg.hackathon?.title }}</h5>

                        <div class="my-3 p-3 rounded-3 bg-light border">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="small text-muted">Situação da Equipe:</span>
                                <div v-if="reg.group || reg.team">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <font-awesome-icon :icon="['fas', 'check']" class="me-1" />
                                        Em Equipe: <strong>{{ (reg.group || reg.team).name }}</strong>
                                    </span>
                                </div>
                                <div v-else>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        Inscrição Individual / Sem Equipe
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top gap-2 flex-wrap">
                        <Link
                            v-if="reg.hackathon?.slug"
                            :href="route('hackathon.show', reg.hackathon.slug)"
                            class="text-decoration-none text-muted small"
                        >
                            Ver detalhes do evento &rarr;
                        </Link>

                        <button
                            v-if="!reg.group && !reg.team"
                            type="button"
                            class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm"
                            @click="openFormModal(reg)"
                        >
                            <font-awesome-icon :icon="['fas', 'plus']" class="me-1" />
                            <span>Formar Equipe</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Formação de Equipe para Hackathon -->
        <ModalDialog
            :is-visible="showFormModal"
            title="Formar Equipe para o Hackathon"
            size="md"
            @close="showFormModal = false"
        >
            <form @submit.prevent="submitCreateTeamForHackathon">
                <div v-if="selectedRegistration?.hackathon" class="mb-3 text-muted small">
                    Evento: <strong class="text-dark">{{ selectedRegistration.hackathon.title }}</strong>
                </div>

                <!-- Opção de Formar a partir de uma Guilda -->
                <div v-if="guilds && guilds.length > 0" class="mb-3 p-3 bg-light rounded-3 border">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <font-awesome-icon :icon="['fas', 'shield-halved']" class="text-primary me-1" />
                        Puxar integrantes da sua Guilda (Opcional)
                    </label>
                    <select v-model="form.origin_guild_id" class="form-select form-select-sm">
                        <option value="">Formar equipe avulsa (sem guilda)</option>
                        <option v-for="g in guilds" :key="g.id" :value="g.id">
                            {{ g.name }} ({{ g.members ? g.members.length : 0 }} membros)
                        </option>
                    </select>
                    <div class="form-text small mt-1">
                        Seus colegas de guilda inscritos neste hackathon serão adicionados automaticamente à equipe.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Nome da Equipe no Hackathon</label>
                    <input v-model="form.name" type="text" class="form-control" placeholder="Ex: Squad Rocket" required />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Área de Foco / Trilha do Desafio</label>
                    <input v-model="form.area_of_focus" type="text" class="form-control" placeholder="Ex: Inteligência Artificial, Saúde, Fintech..." />
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-dark">Tecnologias Pretendidas</label>
                    <input v-model="form.technologies" type="text" class="form-control" placeholder="Ex: Vue, Laravel, Python..." />
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showFormModal = false">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">Criar Equipe</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>
