<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { usePage, router } from "@inertiajs/vue3";

const page = usePage();
const context = computed(() => page.props.context || {});
const activeOrg = computed(() => context.value.active_organization);
const userOrgs = computed(() => context.value.user_organizations || []);
const isOrgContext = computed(() => context.value.type === "organization" && activeOrg.value);

const isOpen = ref(false);
const dropdownRef = ref(null);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener("click", closeDropdown);
});

const switchContext = (type, orgId = null) => {
    isOpen.value = false;
    router.post(route("context.switch"), {
        type,
        organization_id: orgId,
    }, {
        preserveScroll: false,
    });
};

const getRoleLabel = (role) => {
    switch (role) {
        case "admin": return "Administrador";
        case "manager": return "Gestor";
        case "collaborator": return "Colaborador";
        default: return "Membro";
    }
};
</script>

<template>
    <div class="position-relative" ref="dropdownRef">
        <!-- Switcher Trigger Button -->
        <button
            type="button"
            class="btn context-switcher-btn d-flex align-items-center justify-content-between w-100 p-2 text-start rounded-3"
            :class="isOrgContext ? 'context-switcher--org' : 'context-switcher--competitor'"
            @click.stop="toggleDropdown"
            title="Alternar contexto de acesso"
        >
            <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                <div class="context-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <font-awesome-icon
                        :icon="isOrgContext ? ['fas', 'building'] : ['fas', 'trophy']"
                    />
                </div>
                <div class="d-flex flex-column overflow-hidden">
                    <span class="context-label small text-muted text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                        {{ isOrgContext ? 'Instituição' : 'Modo' }}
                    </span>
                    <span class="context-name fw-bold text-truncate" style="font-size: 0.88rem;">
                        {{ isOrgContext ? activeOrg.name : 'Competidor' }}
                    </span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                <span v-if="isOrgContext && context.role_in_organization" class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle d-none d-xl-inline" style="font-size: 0.65rem;">
                    {{ getRoleLabel(context.role_in_organization) }}
                </span>
                <font-awesome-icon :icon="['fas', 'chevron-down']" class="text-muted small ms-1" />
            </div>
        </button>

        <!-- Dropdown Menu -->
        <transition name="fade">
            <div
                v-if="isOpen"
                class="context-dropdown shadow-lg rounded-3 border bg-white position-absolute start-0 top-100 mt-2 w-100 z-3 p-2"
                style="min-width: 260px;"
            >
                <div class="px-2 py-1 text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">
                    Instituições
                </div>

                <div v-if="userOrgs.length > 0" class="d-flex flex-column gap-1 mb-2">
                    <button
                        v-for="org in userOrgs"
                        :key="org.id"
                        type="button"
                        class="btn btn-sm d-flex align-items-center justify-content-between p-2 rounded-2 text-start transition-all"
                        :class="isOrgContext && activeOrg?.id === org.id ? 'btn-primary text-white' : 'btn-light text-dark'"
                        @click="switchContext('organization', org.id)"
                    >
                        <div class="d-flex align-items-center gap-2 text-truncate me-2">
                            <font-awesome-icon :icon="['fas', 'building']" class="opacity-75 flex-shrink-0" />
                            <div class="d-flex flex-column text-truncate">
                                <span class="fw-semibold text-truncate" style="font-size: 0.85rem;">{{ org.name }}</span>
                                <span class="small opacity-75" style="font-size: 0.72rem;">{{ getRoleLabel(org.role) }}</span>
                            </div>
                        </div>
                        <font-awesome-icon
                            v-if="isOrgContext && activeOrg?.id === org.id"
                            :icon="['fas', 'check']"
                            class="flex-shrink-0"
                        />
                    </button>
                </div>
                <div v-else class="px-2 py-2 text-muted small fst-italic">
                    Nenhuma instituição vinculada
                </div>

                <hr class="my-1 border-secondary-subtle" />

                <!-- Competitor Option -->
                <div class="px-2 py-1 text-muted small fw-bold text-uppercase" style="font-size: 0.7rem;">
                    Participação
                </div>
                <button
                    type="button"
                    class="btn btn-sm d-flex align-items-center justify-content-between p-2 rounded-2 text-start w-100 mb-1"
                    :class="!isOrgContext ? 'btn-primary text-white' : 'btn-light text-dark'"
                    @click="switchContext('competitor')"
                >
                    <div class="d-flex align-items-center gap-2">
                        <font-awesome-icon :icon="['fas', 'trophy']" class="opacity-75 flex-shrink-0" />
                        <div class="d-flex flex-column">
                            <span class="fw-semibold" style="font-size: 0.85rem;">Área do Competidor</span>
                            <span class="small opacity-75" style="font-size: 0.72rem;">Hackathons, equipes e projetos</span>
                        </div>
                    </div>
                    <font-awesome-icon v-if="!isOrgContext" :icon="['fas', 'check']" class="flex-shrink-0" />
                </button>
            </div>
        </transition>
    </div>
</template>

<style scoped>
.context-switcher-btn {
    border: 1px solid rgba(0, 0, 0, 0.1);
    background-color: #f8f9fa;
    transition: all 0.2s ease-in-out;
}
.context-switcher-btn:hover {
    background-color: #f1f3f5;
    border-color: rgba(0, 0, 0, 0.18);
}
.context-switcher--org {
    border-left: 4px solid #0d6efd;
}
.context-switcher--competitor {
    border-left: 4px solid #ffc107;
}
.context-icon {
    width: 32px;
    height: 32px;
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
}
.context-switcher--competitor .context-icon {
    background: rgba(255, 193, 7, 0.15);
    color: #b28900;
}
.context-dropdown {
    animation: fadeIn 0.15s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
