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
    <nav class="navbar sticky-top container-fluid py-2">
        <div class="justify-content-end d-none d-md-flex w-100">
            <CreateOrganizationOffCanvas />
            <NotificationButton :notifications="notifications" />
        </div>

        <div class="d-md-none d-flex justify-content-between w-100">
            <Link
                class="navbar-brand fw-semibold title-page text-light"
                :href="route('home')"
                >Motirõ</Link
            >
            <button
                class="btn btn-primary text-white"
                @click="emit('toggleSidebar')"
            >
                <font-awesome-icon icon="fa-solid fa-bars" />
            </button>
        </div>
    </nav>
</template>
