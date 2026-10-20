<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    team: {
        type: Object,
        required: true,
    },
    isLeader: {
        type: Boolean,
        default: false,
    },
    hasLeader: {
        type: Boolean,
        default: false,
    },
    canElectLeader: {
        type: Boolean,
        default: false,
    },
});

const inviteForm = useForm({
    email_or_username: "",
});

const leaderForm = useForm({
    leader_id: props.team.leader_id || "",
});

const submitInvite = () => {
    inviteForm.post(route("team.invite", props.team.id), {
        onSuccess: () => inviteForm.reset(),
    });
};

const submitElectLeader = () => {
    leaderForm.post(route("team.leader", props.team.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head>
        <title>Equipe | {{ team.name }}</title>
    </Head>

    <div class="py-4">
        <Link :href="route('participant.dashboard')" class="text-decoration-none text-muted mb-2 d-inline-flex align-items-center gap-1">
            <font-awesome-icon :icon="['fas', 'arrow-left']" class="small" />
            <span>Voltar para o Dashboard</span>
        </Link>

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4 pb-3 border-bottom">
            <div>
                <h1 class="fw-bold mb-1">{{ team.name }}</h1>
                <p class="text-muted mb-0">{{ team.description || 'Sem descrição cadastrada.' }}</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span v-if="isLeader" class="badge bg-primary px-3 py-2">Você é o Líder</span>
                <span v-else-if="!team.leader_id" class="badge bg-warning text-dark px-3 py-2">Sem líder definido</span>
                <span v-else class="badge bg-light text-dark border px-3 py-2">Líder: {{ team.leader?.name }}</span>
            </div>
        </div>

        <!-- Alerta se o Hackathon exigir líder e não houver líder -->
        <div v-if="team.registrations?.some(r => r.hackathon?.team_leader_required) && !team.leader_id" class="alert alert-warning border-0 shadow-sm rounded-3 p-3 mb-4">
            <h6 class="fw-bold mb-1">
                <font-awesome-icon :icon="['fas', 'triangle-exclamation']" class="me-1" />
                Definição de Líder Obrigatória
            </h6>
            <p class="small mb-0">
                O Hackathon vinculado exige que a equipe possua um líder formalmente designado para validar e concluir sua participação no evento. Escolha um dos integrantes elegíveis abaixo.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <!-- Integrantes da Equipe -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'users']" class="text-primary me-2" />
                            Integrantes da Equipe ({{ team.members ? team.members.length : 0 }})
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li v-for="member in team.members" :key="member.id" class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ member.name }}</h6>
                                    <small class="text-muted">@{{ member.username || member.email }}</small>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" :class="member.id === team.leader_id ? 'bg-warning text-dark' : 'bg-light text-dark border'">
                                        {{ member.id === team.leader_id ? 'Líder' : 'Membro' }}
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Escolha / Substituição Livre do Líder -->
                <div v-if="canElectLeader && team.members && team.members.length > 0" class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'crown']" class="text-warning me-2" />
                            Definição de Liderança da Equipe
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            A liderança pode ser livremente escolhida ou substituída entre qualquer um dos integrantes ativos da equipe.
                        </p>
                        <form @submit.prevent="submitElectLeader" class="d-flex flex-column flex-sm-row gap-2">
                            <select v-model="leaderForm.leader_id" class="form-select" required>
                                <option value="" disabled>Selecione um integrante...</option>
                                <option v-for="member in team.members" :key="member.id" :value="member.id">
                                    {{ member.name }} ({{ member.email }}) {{ member.id === team.leader_id ? '— [Atual Líder]' : '' }}
                                </option>
                            </select>
                            <button type="submit" class="btn btn-outline-primary text-nowrap" :disabled="leaderForm.processing || !leaderForm.leader_id">
                                {{ team.leader_id ? 'Substituir Líder' : 'Definir Líder' }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Participações em Hackathons -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'trophy']" class="text-warning me-2" />
                            Participações em Hackathons
                        </h5>
                    </div>
                    <div class="card-body">
                        <div v-if="!team.registrations || team.registrations.length === 0" class="text-muted text-center py-3">
                            Esta equipe ainda não está inscrita em nenhum hackathon.
                        </div>
                        <ul v-else class="list-group list-group-flush">
                            <li v-for="reg in team.registrations" :key="reg.id" class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <div>
                                    <h6 class="mb-0 fw-bold">{{ reg.hackathon ? reg.hackathon.title : '-' }}</h6>
                                    <small class="text-muted">Status: {{ reg.status }}</small>
                                </div>
                                <Link v-if="reg.hackathon" :href="route('hackathon.show', reg.hackathon.slug)" class="btn btn-sm btn-outline-primary">
                                    Ver Evento
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- Enviar Convite -->
                <div v-if="isLeader || !hasLeader" class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'user-plus']" class="text-success me-2" />
                            Convidar Integrante
                        </h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitInvite">
                            <div class="mb-3">
                                <label class="form-label">E-mail ou Nome de Usuário</label>
                                <input v-model="inviteForm.email_or_username" type="text" class="form-control" placeholder="ex: dev@email.com ou dev_username" required />
                                <div class="form-text small">
                                    {{ team.hackathon_id ? 'Para este evento, o participante deve estar cadastrado no Motirõ.' : 'Caso o participante ainda não tenha cadastro, receberá convite por e-mail.' }}
                                </div>
                                <div v-if="inviteForm.errors.email_or_username" class="text-danger small mt-1">
                                    {{ inviteForm.errors.email_or_username }}
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" :disabled="inviteForm.processing">
                                Enviar Convite
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Histórico de Convites -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3">
                        <h6 class="card-title mb-0 fw-bold text-muted">Convites Enviados</h6>
                    </div>
                    <div class="card-body">
                        <div v-if="!team.invitations || team.invitations.length === 0" class="text-muted small">
                            Nenhum convite pendente.
                        </div>
                        <ul v-else class="list-group list-group-flush small">
                            <li v-for="inv in team.invitations" :key="inv.id" class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>{{ inv.invitee ? inv.invitee.name : inv.email }}</span>
                                <span class="badge" :class="{
                                    'bg-warning text-dark': inv.status === 'pending',
                                    'bg-success': inv.status === 'accepted',
                                    'bg-secondary': inv.status === 'declined' || inv.status === 'cancelled'
                                }">{{ inv.status }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
