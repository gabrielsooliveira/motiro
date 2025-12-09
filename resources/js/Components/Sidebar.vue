<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";
import { Link } from "@inertiajs/vue3";
import Navbar from "@/js/Components/Navbar/NavbarDashboard.vue";

const isSidebarOpen = ref(false);
const isMobile = ref(false);
const sidebar = ref(null);

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
            class="bg-secondary d-md-block position-fixed vh-100 d-xl-flex flex-column p-3 shadow-lg vh-100 border-end border-secondary"
            :class="['sidebar', { open: isSidebarOpen }]"
        >
            <Link
                :href="route('dashboard.index')"
                class="text-decoration-none fw-semibold text-light fs-3 title-page"
            >
                Motirõ
            </Link>
            <hr class="text-light" />
            <ul class="nav nav-pills flex-column mb-auto fw-bold">
                <li class="nav-item">
                    <Link
                        :href="route('dashboard.index')"
                        class="nav-link link-light d-flex align-items-center px-3 py-2 rounded mb-1"
                        :class="[
                            $page.url === '/dashboard' ? 'bg-primary' : '',
                        ]"
                        ><font-awesome-icon
                            icon="fa-solid fa-layer-group"
                            class="me-2"
                        />
                        Dashboard</Link
                    >
                </li>
                <li class="nav-item">
                    <Link
                        :href="route('dashboard.index')"
                        class="nav-link link-light d-flex align-items-center px-3 py-2 rounded mb-1"
                        :class="[
                            $page.url === '/hackathons' ? 'bg-primary' : '',
                        ]"
                        ><font-awesome-icon
                            icon="fa-regular fa-calendar"
                            class="me-2"
                        />
                        Meus Hackathons</Link
                    >
                </li>
                <li class="nav-item">
                    <Link
                        :href="route('dashboard.index')"
                        class="nav-link link-light d-flex align-items-center px-3 py-2 rounded mb-1"
                        :class="[$page.url === '/actives' ? 'bg-primary' : '']"
                        ><font-awesome-icon
                            icon="fa-solid fa-list-check"
                            class="me-2"
                        />
                        Atividades</Link
                    >
                </li>
                <li class="nav-item">
                    <Link
                        :href="route('organization.index')"
                        class="nav-link link-light d-flex align-items-center px-3 py-2 rounded mb-1"
                        :class="[$page.url === '/actives' ? 'bg-primary' : '']"
                        ><font-awesome-icon
                            icon="fa-solid fa-building-user"
                            class="me-2"
                        />
                        Organizações</Link
                    >
                </li>
            </ul>

            <hr class="mt-auto text-light" />
            <div class="d-grid gap-2">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="btn link-danger d-flex align-items-center gap-3 px-3 fw-semibold border-0"
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
