<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { ref, onUnmounted, onMounted, onBeforeUnmount, computed } from "vue";

const page = usePage();
const user = computed(() => page.props.auth.user);

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

// Fecha se clicar fora do painel no mobile
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
    <!-- Original Mobile Top Navigation Bar -->
    <nav :class="navbarClass" class="d-lg-none">
        <div class="container-fluid">
            <button
                class="btn border-0 navbar-toggler"
                type="button"
                @click="toggleMenu"
            >
                <font-awesome-icon icon="fas fa-bars" size="lg" />
            </button>
        </div>
    </nav>

    <!-- Desktop Sidebar -->
    <aside class="sidebar d-none d-lg-flex flex-column justify-content-between z-5">
        <div class="sidebar-top">

            <!-- Navigation Links -->
            <nav class="sidebar-nav py-4 px-3">
                <ul class="nav flex-column gap-2">
                    <li class="nav-item">
                        <Link class="nav-link-custom" :href="route('dashboard.index')" :class="{ active: $page.url === '/' }">
                            <font-awesome-icon icon="fas fa-house" class="nav-icon" />
                            <span>Início</span>
                        </Link>
                    </li>
                    <!-- <li class="nav-item">
                        <Link class="nav-link-custom" :href="route('organization.index')" :class="[$page.url === '/organization' ? 'bg-primary' : '']">
                            <font-awesome-icon icon="fas fa-house" class="nav-icon" />
                            <span>Organização</span>
                        </Link>
                    </li> -->
                </ul>
            </nav>
        </div>

        <!-- User Profile & Action Menu -->
        <div class="sidebar-footer p-3 border-top">
            <Link :href="route('logout')" method="post" as="button" class="btn btn-outline-danger d-flex align-items-center gap-2 py-2 w-100 text-start">
                <font-awesome-icon icon="fa-solid fa-right-from-bracket" />
                <span>Sair</span>
            </Link>
        </div>
    </aside>

    <!-- Original Mobile Navigation Drawer (with .mobile-menu selector added) -->
    <transition name="fade">
        <div
            v-if="isMenuOpen"
            class="mobile-menu position-fixed top-0 start-0 w-100 h-100 bg-body d-flex flex-column p-4 z-3 overflow-y-auto"
        >
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="text-dark fw-bold mb-0">Menu</h5>
                <button class="btn btn-link link-dark fs-4" @click="closeMenu">
                    <font-awesome-icon icon="fas fa-xmark" />
                </button>
            </div>

            <ul class="nav flex-column fw-semibold">
                <li class="nav-item">
                    <Link
                        class="nav-link"
                        :href="route('dashboard.index')"
                        :class="{
                            'link-secondary': $page.url === '/',
                        }"
                        @click="closeMenu"
                        >Inicio</Link
                    >
                </li>
                <li><hr /></li>
                <li class="nav-item">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="nav-link link-danger"
                        ><font-awesome-icon
                            icon="fa-solid fa-right-from-bracket"
                        />
                        Logout</Link
                    >
                </li>
            </ul>
        </div>
    </transition>
</template>