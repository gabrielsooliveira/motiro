<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { ref, onUnmounted, onMounted, onBeforeUnmount, computed } from "vue";

const page = usePage();
const user = computed(() => page.props.user);

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
    const baseClasses = "navbar navbar-expand-lg fixed-top z-1 px-2";
    const bgClass =
        !isMdUp.value || isScrolled.value
            ? "bg-light shadow-sm"
            : "bg-transparent";

    return `${baseClasses} ${bgClass}`;
});
</script>

<template>
    <nav :class="navbarClass">
        <div class="container-fluid">
            <Link
                class="navbar-brand fw-bold fs-4 title-page"
                :href="route('home')"
            >
                Motirõ
            </Link>

            <button
                class="btn d-lg-none border-0"
                type="button"
                @click="toggleMenu"
                aria-label="Abrir Menu"
            >
                <font-awesome-icon icon="fas fa-bars" size="lg" />
            </button>

            <!-- Desktop Menu -->
            <div
                class="collapse navbar-collapse d-none d-lg-flex align-items-center justify-content-between"
            >
                <ul class="nav justify-content-center fw-semibold">
                    <li class="nav-item">
                        <Link
                            :href="route('hackathon.index')"
                            class="nav-link link-dark"
                        >
                            Hackathons
                        </Link>
                    </li>
                </ul>

                <template v-if="user">
                    <div class="d-flex align-items-center gap-3">
                        <Link
                            :href="route('dashboard.index')"
                            class="btn btn-primary fw-semibold"
                        >
                            <span class="me-2">Meu Dashboard</span>
                            <font-awesome-icon icon="fa-solid fa-right-to-bracket" />
                        </Link>
                    </div>
                </template>

                <template v-else>
                    <div class="d-flex align-items-center gap-2">
                        <Link
                            :href="route('login')"
                            class="btn btn-link link-dark fw-semibold text-decoration-none"
                        >
                            Entrar
                        </Link>
                        <Link
                            :href="route('register', { type: 'competitor' })"
                            class="btn btn-outline-primary fw-semibold btn-sm"
                        >
                            Quero Participar
                        </Link>
                        <Link
                            :href="route('register', { type: 'organization' })"
                            class="btn btn-primary fw-semibold btn-sm"
                        >
                            Organizar Hackathon
                        </Link>
                    </div>
                </template>
            </div>
        </div>
    </nav>

    <!-- Responsive Mobile Menu -->
    <transition name="fade">
        <div
            v-if="isMenuOpen"
            class="position-fixed top-0 start-0 w-100 h-100 bg-body d-flex flex-column p-4 z-3 overflow-y-auto mobile-menu"
        >
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="text-dark fw-bold mb-0">Menu</h4>
                <button class="btn btn-link link-dark fs-4" @click="closeMenu">
                    <font-awesome-icon icon="fas fa-xmark" />
                </button>
            </div>

            <ul class="nav flex-column fw-semibold fs-5 gap-2 mb-4">
                <li class="nav-item">
                    <Link
                        :href="route('home')"
                        class="nav-link link-dark"
                        @click="closeMenu"
                    >
                        Início
                    </Link>
                </li>
                <li class="nav-item">
                    <Link
                        :href="route('hackathon.index')"
                        class="nav-link link-dark"
                        @click="closeMenu"
                    >
                        Hackathons
                    </Link>
                </li>
            </ul>

            <div class="mt-auto d-grid gap-2">
                <template v-if="user">
                    <Link
                        :href="route('dashboard.index')"
                        class="btn btn-primary btn-lg fw-semibold"
                        @click="closeMenu"
                    >
                        Meu Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="btn btn-outline-secondary btn-lg fw-semibold"
                        @click="closeMenu"
                    >
                        Entrar na Conta
                    </Link>
                    <Link
                        :href="route('register', { type: 'competitor' })"
                        class="btn btn-outline-primary btn-lg fw-semibold"
                        @click="closeMenu"
                    >
                        Quero Participar (Competidor)
                    </Link>
                    <Link
                        :href="route('register', { type: 'organization' })"
                        class="btn btn-primary btn-lg fw-semibold"
                        @click="closeMenu"
                    >
                        Quero Organizar um Hackathon
                    </Link>
                </template>
            </div>
        </div>
    </transition>
</template>
