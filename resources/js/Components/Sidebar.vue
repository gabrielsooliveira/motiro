<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { ref, onUnmounted, onMounted, onBeforeUnmount, computed } from "vue";
import ContextSwitcher from "@/js/Components/Navbar/ContextSwitcher.vue";

const page = usePage();
const user = computed(() => page.props.user || page.props.auth?.user);
const context = computed(() => page.props.context || {});
const isOrgContext = computed(() => context.value.type === "organization");

const isMenuOpen = ref(false);
const isScrolled = ref(false);
const isMdUp = ref(window.innerWidth >= 992);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 5;
};

const handleResize = () => {
    isMdUp.value = window.innerWidth >= 992;
};

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

const handleClickOutside = (event) => {
    const modalMenu = document.querySelector(".mobile-menu");
    const toggler = document.querySelector(".navbar-toggler");

    if (
        isMenuOpen.value &&
        modalMenu &&
        !modalMenu.contains(event.target) &&
        toggler &&
        !toggler.contains(event.target)
    ) {
        closeMenu();
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    window.addEventListener("scroll", handleScroll);
    window.addEventListener("resize", handleResize);
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
    window.removeEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});

const navbarClass = computed(() => {
    const baseClasses = "navbar navbar-expand-lg fixed-top z-1 px-3";
    const bgClass =
        !isMdUp.value || isScrolled.value
            ? "bg-light shadow-sm"
            : "bg-transparent";

    return `${baseClasses} ${bgClass}`;
});
</script>

<template>
    <!-- Mobile Top Navigation Bar -->
    <nav :class="navbarClass" class="d-lg-none">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <button
                class="btn border-0 navbar-toggler p-1"
                type="button"
                @click="toggleMenu"
            >
                <font-awesome-icon :icon="['fas', 'bars']" size="lg" />
            </button>

            <div class="flex-grow-1 mx-3" style="max-width: 240px;">
                <ContextSwitcher />
            </div>

            <Link :href="route('home')" class="text-decoration-none fw-bold text-dark fs-5">
                Motirõ
            </Link>
        </div>
    </nav>

    <!-- Desktop Sidebar (Section 13 & 15) -->
    <aside class="sidebar d-none d-lg-flex flex-column justify-content-between z-5">
        <div class="sidebar-top">
            <!-- Brand / Logo -->
            <div class="px-3 pt-3 pb-2 border-bottom">
                <Link :href="route('home')" class="d-flex align-items-center gap-2 text-decoration-none text-dark">
                    <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 36px; height: 36px;">
                        M
                    </div>
                    <span class="fs-4 fw-bold tracking-tight">Motirõ</span>
                </Link>
            </div>

            <!-- Context Switcher (Section 13) -->
            <div class="p-3 border-bottom bg-light bg-opacity-50">
                <ContextSwitcher />
            </div>

            <!-- Navigation Links -->
            <nav class="sidebar-nav py-3 px-3">
                <!-- ORGANIZATION CONTEXT MENU (Section 15) -->
                <div v-if="isOrgContext" class="d-flex flex-column gap-3">
                    <div>
                        <div class="nav-section-title px-2 mb-1 text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            Geral
                        </div>
                        <ul class="nav flex-column gap-1">
                            <li class="nav-item">
                                <Link
                                    class="nav-link-custom d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                                    :href="route('organization.dashboard')"
                                    :class="{ active: $page.url === '/organization/dashboard' }"
                                >
                                    <font-awesome-icon :icon="['fas', 'chart-line']" class="nav-icon" />
                                    <span>Dashboard</span>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link
                                    class="nav-link-custom d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                                    :href="route('organization.dashboard')"
                                    :class="{ active: $page.url.startsWith('/organization/dashboard') && $page.url !== '/organization/dashboard' }"
                                >
                                    <font-awesome-icon :icon="['fas', 'trophy']" class="nav-icon" />
                                    <span>Hackathons</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <div class="nav-section-title px-2 mb-1 text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            Gestão Institucional
                        </div>
                        <ul class="nav flex-column gap-1">
                            <li class="nav-item">
                                <Link
                                    class="nav-link-custom d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                                    :href="route('organization.users.index')"
                                    :class="{ active: $page.url.startsWith('/organization/users') }"
                                >
                                    <font-awesome-icon :icon="['fas', 'users-gear']" class="nav-icon" />
                                    <span>Usuários da Instituição</span>
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <div class="nav-section-title px-2 mb-1 text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            Explorar
                        </div>
                        <ul class="nav flex-column gap-1">
                            <li class="nav-item">
                                <Link
                                    class="nav-link-custom d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                                    :href="route('hackathon.index')"
                                >
                                    <font-awesome-icon :icon="['fas', 'globe']" class="nav-icon" />
                                    <span>Explorar Hackathons</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- COMPETITOR CONTEXT MENU (Section 1.1) -->
                <div v-else class="d-flex flex-column gap-3">
                    <div>
                        <div class="nav-section-title px-2 mb-1 text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">
                            Área do Participante
                        </div>
                        <ul class="nav flex-column gap-1">
                            <li class="nav-item">
                                <Link
                                    class="nav-link-custom d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                                    :href="route('dashboard.index')"
                                    :class="{ active: $page.url === '/dashboard' || $page.url === '/' }"
                                >
                                    <font-awesome-icon :icon="['fas', 'house']" class="nav-icon" />
                                    <span>Meu Painel</span>
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link
                                    class="nav-link-custom d-flex align-items-center gap-2 px-3 py-2 rounded-3 text-decoration-none"
                                    :href="route('hackathon.index')"
                                >
                                    <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="nav-icon" />
                                    <span>Buscar Hackathons</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </div>

        <!-- User Profile & Action Menu -->
        <div class="sidebar-footer p-3 border-top bg-light bg-opacity-25">
            <div v-if="user" class="d-flex align-items-center gap-2 mb-3 px-1">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                    {{ user.name?.charAt(0).toUpperCase() }}
                </div>
                <div class="d-flex flex-column text-truncate">
                    <span class="fw-semibold text-dark text-truncate" style="font-size: 0.85rem;">{{ user.name }}</span>
                    <span class="text-muted text-truncate" style="font-size: 0.72rem;">{{ user.email }}</span>
                </div>
            </div>

            <Link :href="route('logout')" method="post" as="button" class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center gap-2 py-2 w-100">
                <font-awesome-icon :icon="['fas', 'right-from-bracket']" />
                <span>Sair</span>
            </Link>
        </div>
    </aside>

    <!-- Mobile Navigation Drawer -->
    <transition name="fade">
        <div
            v-if="isMenuOpen"
            class="mobile-menu position-fixed top-0 start-0 w-100 h-100 bg-body d-flex flex-column p-4 z-3 overflow-y-auto"
        >
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-dark fw-bold mb-0">Menu</h5>
                <button class="btn btn-link link-dark fs-4" @click="closeMenu">
                    <font-awesome-icon :icon="['fas', 'xmark']" />
                </button>
            </div>

            <div class="mb-4">
                <ContextSwitcher />
            </div>

            <ul class="nav flex-column fw-semibold gap-2">
                <li v-if="isOrgContext" class="nav-item">
                    <Link
                        class="nav-link py-2"
                        :href="route('organization.dashboard')"
                        @click="closeMenu"
                    >
                        <font-awesome-icon :icon="['fas', 'chart-line']" class="me-2" />
                        Dashboard da Organização
                    </Link>
                </li>
                <li v-if="isOrgContext" class="nav-item">
                    <Link
                        class="nav-link py-2"
                        :href="route('organization.users.index')"
                        @click="closeMenu"
                    >
                        <font-awesome-icon :icon="['fas', 'users-gear']" class="me-2" />
                        Usuários da Instituição
                    </Link>
                </li>
                <li v-if="!isOrgContext" class="nav-item">
                    <Link
                        class="nav-link py-2"
                        :href="route('dashboard.index')"
                        @click="closeMenu"
                    >
                        <font-awesome-icon :icon="['fas', 'house']" class="me-2" />
                        Área do Participante
                    </Link>
                </li>
                <li class="nav-item">
                    <Link
                        class="nav-link py-2"
                        :href="route('hackathon.index')"
                        @click="closeMenu"
                    >
                        <font-awesome-icon :icon="['fas', 'globe']" class="me-2" />
                        Explorar Hackathons
                    </Link>
                </li>
                <li><hr class="my-2" /></li>
                <li class="nav-item">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="nav-link link-danger py-2 w-100 text-start"
                    >
                        <font-awesome-icon :icon="['fas', 'right-from-bracket']" class="me-2" />
                        Logout
                    </Link>
                </li>
            </ul>
        </div>
    </transition>
</template>

<style scoped>
.nav-link-custom {
    color: #495057;
    font-size: 0.9rem;
    font-weight: 500;
    transition: all 0.15s ease-in-out;
}
.nav-link-custom:hover {
    background-color: #f1f3f5;
    color: #0d6efd;
}
.nav-link-custom.active {
    background-color: #0d6efd;
    color: #ffffff !important;
}
.nav-link-custom.active .nav-icon {
    color: #ffffff;
}
.nav-icon {
    width: 18px;
    color: #6c757d;
}
</style>