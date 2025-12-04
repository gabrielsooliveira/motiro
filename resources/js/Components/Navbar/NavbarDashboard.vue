<script setup>
import { Link } from "@inertiajs/vue3";
import CreateOrganizationOffCanvas from "@/js/Components/Offcanvas/Organization/CreateOrganizationOffCanvas.vue";
import NotificationButton from "@/js/Components/Notifications/NotificationButton.vue";

const emit = defineEmits(["toggleSidebar"]);

const notifications = [
    {
        title: "Notificação 1",
        message: "Mensagem 1",
        read: false,
    },
    {
        title: "Notificação 2",
        message: "Mensagem 2",
        read: true,
    },
];
</script>

<template>
    <nav class="navbar sticky-top container-fluid align-items-center py-2">
        <Link
            :href="route('dashboard.index')"
            class="navbar-brand fw-semibold text-primary title-page"
        >
            Motirõ
        </Link>

        <div class="d-none d-md-flex align-items-center text-primary gap-3">
            <NotificationButton :notifications="notifications" />
            <CreateOrganizationOffCanvas />
        </div>

        <div class="d-md-none">
            <button
                class="btn btn-primary text-white"
                @click="emit('toggleSidebar')"
            >
                <font-awesome-icon icon="fa-solid fa-bars" />
            </button>
        </div>
    </nav>
</template>
