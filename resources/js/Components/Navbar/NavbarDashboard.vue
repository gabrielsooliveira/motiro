<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import NotificationButton from "@/js/Components/Notifications/NotificationButton.vue";

const emit = defineEmits(["toggleSidebar"]);
const page = usePage();
const user = computed(() => page.props.user || page.props.auth?.user);
const context = computed(() => page.props.context || {});
const notifications = computed(() => page.props.notifications || []);
const isOrgContext = computed(() => context.value.type === "organization");
const activeOrg = computed(() => context.value.active_organization);
</script>

<template>
    <nav class="navbar sticky-top container-fluid py-2 border-bottom bg-white">
        <div class="justify-content-between d-none d-md-flex align-items-center w-100">
            <div class="d-flex align-items-center gap-2">
                <!-- Context Indicator -->
                <div v-if="isOrgContext && activeOrg" class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill small fw-semibold">
                        <font-awesome-icon :icon="['fas', 'building']" class="me-1" />
                        {{ activeOrg.name }}
                    </span>
                    <Link
                        :href="route('organization.select')"
                        class="btn btn-sm btn-link text-muted text-decoration-none p-0 ms-1 small"
                        title="Trocar organização"
                    >
                        <font-awesome-icon :icon="['fas', 'arrow-right-arrow-left']" class="me-1" />
                        Trocar
                    </Link>
                </div>
                <div v-else class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 rounded-pill small fw-semibold">
                        <font-awesome-icon :icon="['fas', 'user-astronaut']" class="me-1" />
                        Área do Participante
                    </span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <Link
                    :href="route('context.select')"
                    class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 rounded-pill px-3"
                    title="Alternar entre Participante e Organização"
                >
                    <font-awesome-icon :icon="['fas', 'repeat']" class="small" />
                    <span>Alternar Contexto</span>
                </Link>

                <NotificationButton :notifications="notifications" />

                <div v-if="user" class="d-flex align-items-center gap-2 border-start ps-3">
                    <span class="fw-semibold text-dark fs-7">{{ user.name }}</span>
                </div>
            </div>
        </div>

        <div class="d-md-none d-flex justify-content-between align-items-center w-100">
            <Link
                class="navbar-brand fw-semibold title-page m-0"
                :href="route('home')"
            >
                Motirõ
            </Link>
            <div class="d-flex align-items-center gap-2">
                <Link
                    :href="route('context.select')"
                    class="btn btn-sm btn-outline-secondary p-1 px-2"
                    title="Alternar Contexto"
                >
                    <font-awesome-icon :icon="['fas', 'repeat']" />
                </Link>
                <NotificationButton :notifications="notifications" />
                <button
                    class="btn btn-primary text-white btn-sm"
                    @click="emit('toggleSidebar')"
                    aria-label="Abrir menu"
                >
                    <font-awesome-icon :icon="['fas', 'bars']" />
                </button>
            </div>
        </div>
    </nav>
</template>
