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
});

const inviteForm = useForm({
    email_or_username: "",
});

const submitInvite = () => {
    inviteForm.post(route("team.invite", props.team.id), {
        onSuccess: () => inviteForm.reset(),
    });
};
</script>

<template>
    <Head>
        <title>Equipe | {{ team.name }}</title>
    </Head>

    <div class="py-4">
        <Link :href="route('dashboard.index')" class="text-decoration-none text-muted mb-2 d-inline-block">
            &larr; Voltar para o Dashboard
        </Link>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <div>
                <h1 class="fw-bold mb-1">{{ team.name }}</h1>
                <p class="text-muted mb-0">{{ team.description || 'Sem descrição cadastrada.' }}</p>
            </div>
            <span v-if="isLeader" class="badge bg-primary">Você é o Líder</span>
        </div>

        <div class="row g-4">
            <!-- Members List -->
            <div class="col-lg-7">
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
                                    <small class="text-muted">@{{ member.username }}</small>
                                </div>
                                <span class="badge" :class="member.pivot?.role === 'leader' ? 'bg-warning text-dark' : 'bg-light text-dark border'">
                                    {{ member.pivot?.role === 'leader' ? 'Líder' : 'Membro' }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Team Registrations -->
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

            <!-- Invite New Members (Leader only) -->
            <div class="col-lg-5">
                <div v-if="isLeader" class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="card-title mb-0 fw-bold">
                            <font-awesome-icon :icon="['fas', 'user-plus']" class="text-success me-2" />
                            Convidar Novo Integrante
                        </h5>
                    </div>
                    <div class="card-body">
                        <form @submit.prevent="submitInvite">
                            <div class="mb-3">
                                <label class="form-label">E-mail ou Username do Usuário</label>
                                <input v-model="inviteForm.email_or_username" type="text" class="form-control" placeholder="ex: dev@email.com ou dev_username" required />
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

                <!-- Pending Invitations -->
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
                                <span class="badge bg-secondary">{{ inv.status }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
