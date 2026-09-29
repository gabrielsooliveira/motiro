<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import NotificationButton from "@/js/Components/Notifications/NotificationButton.vue";

const emit = defineEmits(["toggleSidebar"]);
const page = usePage();
const user = computed(() => page.props.user);
const notifications = computed(() => page.props.notifications || []);
</script>

<template>
    <nav class="navbar sticky-top container-fluid py-2 border-bottom bg-white">
        <div class="justify-content-end d-none d-md-flex align-items-center gap-3 w-100">
            <NotificationButton :notifications="notifications" />

            <div v-if="user" class="d-flex align-items-center gap-2 border-start ps-3">
                <span class="fw-semibold text-dark">{{ user.name }}</span>
            </div>
        </div>

        <div class="d-md-none d-flex justify-content-between w-100">
            <Link
                class="navbar-brand fw-semibold title-page"
                :href="route('home')"
                >Motirõ</Link
            >
            <div class="d-flex align-items-center gap-2">
                <NotificationButton :notifications="notifications" />
                <button
                    class="btn btn-primary text-white"
                    @click="emit('toggleSidebar')"
                >
                    <font-awesome-icon icon="fa-solid fa-bars" />
                </button>
            </div>
        </div>
    </nav>
</template>
