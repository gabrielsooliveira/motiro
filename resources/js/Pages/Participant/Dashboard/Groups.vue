<script setup>
import { Head, Link, usePage, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import ModalDialog from "@/js/Components/Modals/ModalDialog.vue";

const props = defineProps({
    groups: {
        type: Array,
        default: () => [],
    },
    registrations: {
        type: Array,
        default: () => [],
    },
    teams: {
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

const showCreateGroupModal = ref(false);
const creationMode = ref("new"); // "new" | "from_guild"

// Registrations that can form or join a group (event allows team registration)
const eligibleRegistrations = computed(() => {
    return props.registrations.filter((reg) => {
        const type = reg.hackathon?.registration_type;
        return type === "team_only" || type === "both" || !type;
    });
});

const groupForm = useForm({
    hackathon_id: "",
    name: "",
    description: "",
    area_of_focus: "",
    guild_id: "",
});

const openCreateModal = () => {
    if (eligibleRegistrations.value.length > 0 && !groupForm.hackathon_id) {
        groupForm.hackathon_id = eligibleRegistrations.value[0].hackathon_id;
    }
    showCreateGroupModal.value = true;
};

const submitGroup = () => {
    if (!groupForm.hackathon_id) return;

    if (creationMode.value === "from_guild") {
        groupForm.post(route("group.form_from_guild", groupForm.hackathon_id), {
            onSuccess: () => {
                showCreateGroupModal.value = false;
                groupForm.reset();
            },
        });
    } else {
        groupForm.post(route("group.store", groupForm.hackathon_id), {
            onSuccess: () => {
                showCreateGroupModal.value = false;
                groupForm.reset();
            },
        });
    }
};
</script>

<template>
    <Head>
        <title>Grupos de Hackathons | Motirõ</title>
    </Head>

    <div class="py-2">
        <!-- Cabeçalho da Página -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <h1 class="h2 fw-bold text-dark mb-0">Grupos de Hackathons</h1>
                <p class="text-muted small mb-0 mt-1">
                    Equipes competitivas ativas formadas exclusivamente para um hackathon específico. Somente participantes com inscrições válidas no evento podem integrá-las.
                </p>
            </div>

            <div class="d-flex gap-2">
                <Link :href="route('participant.dashboard')" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'arrow-left']" />
                    <span>Voltar ao Painel</span>
                </Link>
                <button
                    v-if="eligibleRegistrations.length > 0"
                    type="button"
                    class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
                    @click="openCreateModal"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                    <span>Criar Grupo no Hackathon</span>
                </button>
            </div>
        </div>

        <!-- Alerta Educativo de Domínio -->
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-3 p-3 mb-4 rounded-3">
            <font-awesome-icon :icon="['fas', 'circle-info']" class="text-info fs-5 mt-1" />
            <div class="small">
                <strong>O que é um Grupo de Participação?</strong>
                <p class="mb-0 mt-1 text-secondary">
                    Ao contrário de uma guild permanente, o Grupo de Participação existe <strong>estritamente no contexto de um hackathon específico</strong>. Cada participante só pode pertencer a <strong>um grupo ativo por evento</strong>. Os integrantes podem vir da mesma guild permanente, de guilds diferentes ou ser participantes avulsos que se inscreveram individualmente.
                </p>
            </div>
        </div>

        <!-- Estado Vazio -->
        <div v-if="groups.length === 0" class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white my-3">
            <div class="mb-3 text-muted">
                <font-awesome-icon :icon="['fas', 'people-group']" class="fs-1 opacity-50" />
            </div>
            <h4 class="fw-bold text-dark mb-2">Você ainda não está em nenhum grupo de participação.</h4>
            <p class="text-muted small mx-auto mb-4 col-md-8">
                Para competir em equipe, inscreva-se em um hackathon que permita formação de grupos e crie ou solicite entrada no grupo da sua equipe.
            </p>
            <div class="d-flex justify-content-center gap-2">
                <Link :href="route('participant.hackathons')" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" />
                    <span>Explorar Hackathons Abertos</span>
                </Link>
                <button
                    v-if="eligibleRegistrations.length > 0"
                    type="button"
                    class="btn btn-primary d-inline-flex align-items-center gap-2"
                    @click="openCreateModal"
                >
                    <font-awesome-icon :icon="['fas', 'plus']" />
                    <span>Formar Grupo para Minha Inscrição</span>
                </button>
            </div>
        </div>

        <!-- Grid de Grupos -->
        <div v-else class="row g-3">
            <div v-for="group in groups" :key="group.id" class="col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 bg-white d-flex flex-column justify-content-between">
                    <div>
                        <!-- Cabeçalho do Card -->
                        <div class="d-flex justify-content-between align-items-start mb-2 gap-2">
                            <span class="badge bg-dark text-white text-truncate">
                                <font-awesome-icon :icon="['fas', 'trophy']" class="me-1 text-warning" />
                                {{ group.hackathon?.title || 'Hackathon' }}
                            </span>
                            <span v-if="group.leader_id === currentUser?.id" class="badge bg-warning text-dark">
                                <font-awesome-icon :icon="['fas', 'crown']" class="me-1" /> Líder do Grupo
                            </span>
                            <span v-else class="badge bg-light text-secondary border">Integrante</span>
                        </div>

                        <h4 class="h5 fw-bold text-dark mb-1">{{ group.name }}</h4>

                        <!-- Badge de Guild de Origem -->
                        <div v-if="group.origin_team" class="mb-2">
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle small">
                                <font-awesome-icon :icon="['fas', 'users-rectangle']" class="me-1" />
                                Formado a partir da guild: {{ group.origin_team.name }}
                            </span>
                        </div>

                        <p class="text-muted small mb-3">
                            {{ group.description || 'Grupo competitivo formado para este Hackathon.' }}
                        </p>

                        <!-- Integrantes Confirmados -->
                        <div class="border rounded-3 p-3 bg-light bg-opacity-50 mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-2">
                                <strong class="text-secondary text-uppercase small">Integrantes Inscritos:</strong>
                                <span class="badge bg-white text-dark border">{{ group.members?.length || 1 }} membro(s)</span>
                            </div>
                            <ul class="list-unstyled mb-0 small">
                                <li
                                    v-for="member in group.members"
                                    :key="member.id"
                                    class="d-flex justify-content-between align-items-center py-1 border-bottom-subtle"
                                >
                                    <div class="d-flex align-items-center gap-2">
                                        <font-awesome-icon :icon="['fas', 'user']" class="text-muted small" />
                                        <span class="fw-semibold text-dark">{{ member.name }}</span>
                                    </div>
                                    <span v-if="member.id === group.leader_id" class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">
                                        Líder
                                    </span>
                                    <span v-else class="text-muted small">Membro</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Rodapé do Card -->
                    <div class="pt-3 border-top mt-2 d-flex gap-2">
                        <Link
                            v-if="group.hackathon"
                            :href="route('hackathon.show', group.hackathon.slug)"
                            class="btn btn-primary btn-sm w-100 d-flex align-items-center justify-content-center gap-2"
                        >
                            <span>Ir para o Hackathon</span>
                            <font-awesome-icon :icon="['fas', 'arrow-right']" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Criação de Grupo de Hackathon -->
        <ModalDialog
            :is-visible="showCreateGroupModal"
            title="Criar Grupo de Participação"
            size="md"
            @close="showCreateGroupModal = false"
        >
            <form @submit.prevent="submitGroup">
                <div class="mb-3">
                    <span class="small text-muted">Formação competitiva exclusiva para o Hackathon</span>
                </div>

                <!-- Seleção do Hackathon onde o participante está inscrito -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Selecione o Hackathon Inscrito *</label>
                    <select v-model="groupForm.hackathon_id" class="form-select" required>
                        <option value="" disabled>Escolha um evento...</option>
                        <option
                            v-for="reg in eligibleRegistrations"
                            :key="reg.hackathon_id"
                            :value="reg.hackathon_id"
                        >
                            {{ reg.hackathon ? reg.hackathon.title : 'Hackathon' }}
                        </option>
                    </select>
                </div>

                <!-- Modo de Formação -->
                <div v-if="teams.length > 0" class="mb-3">
                    <label class="form-label fw-semibold small d-block">Origem do Grupo</label>
                    <div class="d-flex gap-2">
                        <button
                            type="button"
                            class="btn btn-sm flex-fill"
                            :class="creationMode === 'new' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="creationMode = 'new'"
                        >
                            Grupo Avulso Novo
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm flex-fill"
                            :class="creationMode === 'from_guild' ? 'btn-primary' : 'btn-outline-secondary'"
                            @click="creationMode = 'from_guild'"
                        >
                            A partir de uma Guild
                        </button>
                    </div>
                </div>

                <!-- Caso a partir de guild -->
                <div v-if="creationMode === 'from_guild'" class="mb-3">
                    <label class="form-label fw-semibold small">Selecione sua Guild Permanente *</label>
                    <select v-model="groupForm.guild_id" class="form-select" required>
                        <option value="" disabled>Escolha uma guild...</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">
                            {{ team.name }} ({{ team.members?.length || 1 }} membros)
                        </option>
                    </select>
                    <div class="form-text small">
                        Os membros da guild que também possuírem inscrição válida neste hackathon serão adicionados ao grupo.
                    </div>
                </div>

                <!-- Campos padrão -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nome do Grupo *</label>
                    <input v-model="groupForm.name" type="text" class="form-control" placeholder="ex: Esquadrão Alpha, DreamTeam" required />
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Área de Foco</label>
                    <input v-model="groupForm.area_of_focus" type="text" class="form-control" placeholder="ex: Soluções Sustentáveis, IA" />
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Descrição</label>
                    <textarea v-model="groupForm.description" class="form-control" rows="3" placeholder="Apresentação do grupo para este evento..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="button" class="btn btn-secondary" @click="showCreateGroupModal = false">Cancelar</button>
                    <button type="submit" class="btn btn-primary" :disabled="groupForm.processing">Criar Grupo</button>
                </div>
            </form>
        </ModalDialog>
    </div>
</template>
