<script setup>
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
    members: {
        type: Array,
        default: () => [],
    },
    invitations: {
        type: Array,
        default: () => [],
    },
    hackathons: {
        type: Array,
        default: () => [],
    },
    is_org_admin: {
        type: Boolean,
        default: false,
    },
});

const activeTab = ref("members");
const showInviteModal = ref(false);
const editingMember = ref(null);

const inviteForm = useForm({
    email: "",
    role: "collaborator",
});

const roleForm = useForm({
    role: "collaborator",
});

const submitInvite = () => {
    inviteForm.post(route("organization.users.invite"), {
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
        },
    });
};

const openEditRoleModal = (member) => {
    editingMember.value = member;
    roleForm.role = member.role;
};

const submitUpdateRole = () => {
    if (!editingMember.value) return;
    roleForm.put(route("organization.users.update_role", editingMember.value.id), {
        onSuccess: () => {
            editingMember.value = null;
        },
    });
};

const toggleMemberStatus = (member) => {
    const action = member.is_active ? "desativar" : "ativar";
    if (confirm(`Deseja realmente ${action} o acesso de ${member.name} nesta instituição?`)) {
        router.put(route("organization.users.toggle_status", member.id));
    }
};

const removeMember = (member) => {
    if (confirm(`Tem certeza que deseja remover o vínculo de ${member.name} com a instituição? Todos os acessos a hackathons desta organização serão revogados.`)) {
        router.delete(route("organization.users.remove", member.id));
    }
};

const cancelInvite = (invitationId) => {
    if (confirm("Deseja realmente cancelar este convite?")) {
        router.delete(route("organization.invitations.cancel", invitationId));
    }
};

const copyInviteLink = (token) => {
    const url = route("register") + "?invitation=" + token;
    navigator.clipboard.writeText(url).then(() => {
        alert("Link de cadastro copiado para a área de transferência!");
    });
};

const getRoleBadge = (role) => {
    switch (role) {
        case "admin":
            return { label: "Administrador da Instituição", class: "bg-primary text-white" };
        case "manager":
            return { label: "Gestor de Hackathon", class: "bg-success text-white" };
        case "collaborator":
            return { label: "Colaborador", class: "bg-info text-dark" };
        default:
            return { label: "Membro", class: "bg-secondary text-white" };
    }
};
</script>

<template>
    <Head :title="`Usuários | ${organization.name}`" />

    <div class="py-2">
            
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
                <div>
                    <Link
                        :href="route('organization.dashboard')"
                        class="btn btn-outline-secondary btn-sm mb-2 d-inline-flex align-items-center gap-2"
                    >
                        <font-awesome-icon :icon="['fas', 'arrow-left']" />
                        <span>Voltar ao Painel</span>
                    </Link>
                    <h1 class="h2 fw-bold text-dark mb-0">Usuários da Instituição</h1>
                    <p class="text-muted small mb-0 mt-1">
                        Gerencie quem tem autorização para atuar na organização {{ organization.name }} e nos seus hackathons.
                    </p>
                </div>

                <div v-if="is_org_admin">
                    <button
                        type="button"
                        class="btn btn-primary d-flex align-items-center gap-2 shadow-sm"
                        @click="showInviteModal = true"
                    >
                        <font-awesome-icon :icon="['fas', 'user-plus']" />
                        <span>Adicionar / Convidar Usuário</span>
                    </button>
                </div>
            </div>

            
            <ul class="nav nav-pills mb-4 gap-2">
                <li class="nav-item">
                    <button
                        class="nav-link"
                        :class="{ active: activeTab === 'members' }"
                        @click="activeTab = 'members'"
                    >
                        <font-awesome-icon :icon="['fas', 'users']" class="me-1" />
                        Membros Ativos ({{ members.length }})
                    </button>
                </li>
                <li class="nav-item">
                    <button
                        class="nav-link"
                        :class="{ active: activeTab === 'invitations' }"
                        @click="activeTab = 'invitations'"
                    >
                        <font-awesome-icon :icon="['fas', 'envelope-open-text']" class="me-1" />
                        Convites Pendentes ({{ invitations.length }})
                    </button>
                </li>
            </ul>

            
            <div v-if="activeTab === 'members'" class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th scope="col" class="ps-3">Usuário</th>
                                <th scope="col">Função Institucional</th>
                                <th scope="col">Status</th>
                                <th scope="col">Hackathons Atribuídos</th>
                                <th scope="col">Membro Desde</th>
                                <th v-if="is_org_admin" scope="col" class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="member in members" :key="member.id" :class="{ 'opacity-50': !member.is_active }">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold">
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark d-flex align-items-center gap-1">
                                                <span>{{ member.name }}</span>
                                                <span v-if="member.is_owner" class="badge bg-warning text-dark small badge-owner">
                                                    Proprietário
                                                </span>
                                            </div>
                                            <span class="text-muted small">{{ member.email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill" :class="getRoleBadge(member.role).class">
                                        {{ getRoleBadge(member.role).label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge rounded-pill" :class="member.is_active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'">
                                        {{ member.is_active ? 'Ativo' : 'Desativado' }}
                                    </span>
                                </td>
                                <td>
                                    
                                    <div v-if="member.assigned_hackathons && member.assigned_hackathons.length > 0" class="d-flex flex-wrap gap-1">
                                        <span
                                            v-for="h in member.assigned_hackathons"
                                            :key="h.id"
                                            class="badge bg-light text-dark border rounded-pill px-2 py-1 small"
                                        >
                                            {{ h.title }}
                                        </span>
                                    </div>
                                    <span v-else class="text-muted small fst-italic">
                                        {{ member.role === 'admin' ? 'Todos os Hackathons (Admin)' : 'Nenhum hackathon específico' }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ member.joined_at || 'Recente' }}
                                </td>
                                <td v-if="is_org_admin" class="text-end pe-3">
                                    <div v-if="!member.is_owner" class="btn-group btn-group-sm">
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openEditRoleModal(member)"
                                            title="Alterar Função"
                                        >
                                            <font-awesome-icon :icon="['fas', 'user-pen']" />
                                        </button>
                                        <button
                                            type="button"
                                            class="btn"
                                            :class="member.is_active ? 'btn-outline-warning' : 'btn-outline-success'"
                                            @click="toggleMemberStatus(member)"
                                            :title="member.is_active ? 'Desativar usuário' : 'Ativar usuário'"
                                        >
                                            <font-awesome-icon :icon="member.is_active ? ['fas', 'user-slash'] : ['fas', 'user-check']" />
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger"
                                            @click="removeMember(member)"
                                            title="Remover da instituição"
                                        >
                                            <font-awesome-icon :icon="['fas', 'trash']" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            
            <div v-if="activeTab === 'invitations'" class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
                <div v-if="invitations.length > 0" class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th scope="col" class="ps-3">E-mail Convidado</th>
                                <th scope="col">Função Proposta</th>
                                <th scope="col">Convidado Por</th>
                                <th scope="col">Expira Em</th>
                                <th scope="col" class="text-end pe-3">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="inv in invitations" :key="inv.id">
                                <td class="ps-3 fw-bold text-dark">
                                    {{ inv.email }}
                                </td>
                                <td>
                                    <span class="badge rounded-pill" :class="getRoleBadge(inv.role).class">
                                        {{ getRoleBadge(inv.role).label }}
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    {{ inv.invited_by?.name || 'Administrador' }}
                                </td>
                                <td class="text-muted small">
                                    {{ inv.expires_at ? new Date(inv.expires_at).toLocaleDateString('pt-BR') : '7 dias' }}
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary"
                                            @click="copyInviteLink(inv.token)"
                                            title="Copiar link de cadastro do convite"
                                        >
                                            <font-awesome-icon :icon="['fas', 'link']" />
                                            <span class="ms-1 d-none d-sm-inline">Copiar Link</span>
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-outline-danger"
                                            @click="cancelInvite(inv.id)"
                                            title="Cancelar convite"
                                        >
                                            <font-awesome-icon :icon="['fas', 'xmark']" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="text-center py-5">
                    <div class="icon-circle icon-circle--md bg-light text-muted mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center">
                        <font-awesome-icon :icon="['fas', 'envelope-circle-check']" class="fs-4" />
                    </div>
                    <h3 class="h6 fw-bold text-dark">Nenhum convite pendente</h3>
                    <p class="text-muted small">Convide novos gestores e colaboradores para participar da sua instituição.</p>
                </div>
            </div>
        </div>

        
        <div v-if="showInviteModal" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitInvite">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Convidar para a Instituição</h5>
                                <span class="small text-muted">{{ organization.name }}</span>
                            </div>
                            <button type="button" class="btn-close" @click="showInviteModal = false"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">E-mail do Usuário *</label>
                                <input
                                    v-model="inviteForm.email"
                                    type="email"
                                    class="form-control"
                                    placeholder="usuario@exemplo.com"
                                    required
                                />
                                <div class="form-text small">
                                    Se o usuário já possuir conta no Motirõ, será vinculado imediatamente. Caso contrário, um convite será gerado.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Função / Nível de Acesso *</label>
                                <select v-model="inviteForm.role" class="form-select" required>
                                    <option value="collaborator">Colaborador (Acesso aos hackathons autorizados)</option>
                                    <option value="manager">Gestor de Hackathon (Gerencia hackathons designados)</option>
                                    <option value="admin">Administrador da Instituição (Acesso total)</option>
                                </select>
                            </div>

                            
                            <div class="p-3 bg-light rounded-3 small text-muted border">
                                <div v-if="inviteForm.role === 'admin'">
                                    <strong class="text-primary d-block mb-1">Administrador da Instituição:</strong>
                                    Pode gerenciar a instituição, convidar/remover usuários, criar e editar todos os hackathons e definir responsáveis.
                                </div>
                                <div v-else-if="inviteForm.role === 'manager'">
                                    <strong class="text-success d-block mb-1">Gestor de Hackathon:</strong>
                                    Pode gerenciar hackathons aos quais foi vinculado, gerenciar etapas, desafios, equipes e submissões. Não administra toda a instituição.
                                </div>
                                <div v-else>
                                    <strong class="text-info d-block mb-1">Colaborador:</strong>
                                    Acesso restrito aos hackathons e funcionalidades explicitamente atribuídos a ele pela equipe.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="showInviteModal = false">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="inviteForm.processing">
                                {{ inviteForm.processing ? 'Enviando...' : 'Enviar Convite' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        
        <div v-if="editingMember" class="modal fade show d-block bg-dark bg-opacity-50" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitUpdateRole">
                        <div class="modal-header border-bottom">
                            <div>
                                <h5 class="modal-title fw-bold">Alterar Função do Usuário</h5>
                                <span class="small text-muted">{{ editingMember.name }} ({{ editingMember.email }})</span>
                            </div>
                            <button type="button" class="btn-close" @click="editingMember = null"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nova Função</label>
                                <select v-model="roleForm.role" class="form-select" required>
                                    <option value="collaborator">Colaborador</option>
                                    <option value="manager">Gestor de Hackathon</option>
                                    <option value="admin">Administrador da Instituição</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer border-top bg-light rounded-bottom-4">
                            <button type="button" class="btn btn-secondary" @click="editingMember = null">Cancelar</button>
                            <button type="submit" class="btn btn-primary" :disabled="roleForm.processing">
                                Salvar Alteração
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</template>
