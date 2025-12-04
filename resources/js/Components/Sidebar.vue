<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Link } from "@inertiajs/vue3";
import Navbar from "@/js/Components/Navbar/NavbarDashboard.vue";

const isSidebarOpen = ref(false);
const isMobile = ref(false);
const sidebar = ref(null);

defineProps({ user: Object });
const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const checkMobile = () => {
    isMobile.value = window.innerWidth < 768;
    if (!isMobile.value && isSidebarOpen.value) {
        isSidebarOpen.value = false;
    }
};

const handleOutsideClick = (event) => {
    if (isSidebarOpen.value && isMobile.value && sidebar.value) {
        const isClickInsideSidebar = sidebar.value.contains(event.target);
        const isClickOnToggleBtn = event.target.closest(".btn-primary");

        if (!isClickInsideSidebar && !isClickOnToggleBtn) {
            isSidebarOpen.value = false;
        }
    }
};

onMounted(() => {
    checkMobile();
    window.addEventListener("resize", checkMobile);
    document.addEventListener("click", handleOutsideClick);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", checkMobile);
    document.removeEventListener("click", handleOutsideClick);
});
</script>

<template>
    <div class="d-flex min-vh-100">
        <aside
            ref="sidebar"
            class="bg-primary-dark d-md-block position-fixed vh-100 d-xl-flex flex-column p-3 shadow-lg vh-100"
            :class="['sidebar', { open: isSidebarOpen }]"
        >
            <ul class="nav nav-pills flex-column mb-auto fw-bold">
                <li class="nav-item">
                    <Link
                        :href="route('dashboard.index')"
                        class="nav-link link-light d-flex align-items-center px-3 py-2 rounded mb-1"
                        ><font-awesome-icon
                            icon="fa-solid fa-layer-group"
                            class="me-2"
                        />
                        Inicio</Link
                    >
                </li>
            </ul>

            <hr class="mt-auto text-light" />
            <div class="d-grid gap-2">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="btn btn-danger text-light d-flex align-items-center gap-3 px-3 fw-semibold"
                    ><font-awesome-icon icon="fa-solid fa-right-from-bracket" />
                    Sair</Link
                >
            </div>
        </aside>

        <div
            v-if="isMobile && isSidebarOpen"
            class="overlay"
            @click="toggleSidebar"
        ></div>

        <div class="w-100" :class="{ 'ms-md-260': !isMobile }">
            <main class="container py-3">
                <Navbar @toggleSidebar="toggleSidebar" />
                <slot />
            </main>
        </div>
    </div>
</template>
