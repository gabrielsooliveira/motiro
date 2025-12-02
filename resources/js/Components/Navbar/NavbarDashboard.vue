<script setup>
import { Link } from "@inertiajs/vue3";
import CreateOrganizationOffCanvas from "@/js/Components/Offcanvas/Organization/CreateOrganizationOffCanvas.vue";

const emit = defineEmits(["toggleSidebar"]);
</script>

<template>
    <div
        class="d-flex justify-content-between align-items-center sticky-top container-fluid"
    >
        <Link
            class="navbar-brand fw-semibold text-primary title-page"
            :href="route('dashboard.index')"
            >Motirõ</Link
        >

        <div class="d-none d-md-block">
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
    </div>
</template>
