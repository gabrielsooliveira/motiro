<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { ref, onUnmounted, onMounted, onBeforeUnmount, computed, provide } from "vue";

const props = defineProps({
    badgeLabel: {
        type: String,
        default: "",
    },
    badgeClass: {
        type: String,
        default: "brand-badge",
    },
});

const page = usePage();
const user = computed(() => page.props.user || page.props.auth?.user);

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

provide("closeSidebarMenu", closeMenu);

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
                aria-label="Abrir menu lateral"
            >
                <font-awesome-icon :icon="['fas', 'bars']" size="lg" />
            </button>

            <Link href="#" class="text-decoration-none fw-bold text-dark fs-5">
                Motirõ
            </Link>
        </div>
    </nav>

    <!-- Desktop Sidebar Container -->
    <aside class="sidebar d-none d-lg-flex flex-column justify-content-between z-5">
        <div class="sidebar-top">
            <div class="px-3 pt-3 pb-2 border-bottom d-flex align-items-center justify-content-between">
                <Link href="#" class="d-flex align-items-center gap-2 text-decoration-none">
                    <span class="fs-4 fw-bold tracking-tight sidebar-brand">Motirõ</span>
                </Link>
                <span v-if="badgeLabel" class="badge px-2 py-1 rounded-pill" :class="badgeClass">
                    {{ badgeLabel }}
                </span>
            </div>

            <nav class="sidebar-nav py-3 px-3 fs-8">
                <slot :close-menu="closeMenu"></slot>
            </nav>
        </div>

        <div class="sidebar-footer p-3 border-top">
            <div v-if="user" class="d-flex align-items-center gap-2 mb-3 px-1">
                <div class="d-flex flex-column text-truncate">
                    <span class="fw-semibold text-dark text-truncate fs-7">{{ user.name }}</span>
                    <span class="text-muted text-truncate fs-8">{{ user.email }}</span>
                </div>
            </div>

            <slot name="footer-actions">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center gap-2 py-2 w-100"
                >
                    <font-awesome-icon :icon="['fas', 'right-from-bracket']" />
                    <span>Sair</span>
                </Link>
            </slot>
        </div>
    </aside>

    <!-- Mobile Navigation Drawer -->
    <transition name="fade">
        <div
            v-if="isMenuOpen"
            class="mobile-menu position-fixed top-0 start-0 w-100 h-100 bg-white d-flex flex-column p-4 z-3 overflow-y-auto"
        >
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="text-dark fw-bold mb-0">Menu de Navegação</h5>
                    <span v-if="badgeLabel" class="badge px-2 py-1 rounded-pill" :class="badgeClass">
                        {{ badgeLabel }}
                    </span>
                </div>
                <button class="btn btn-link link-dark fs-4 p-0" @click="closeMenu" aria-label="Fechar menu">
                    <font-awesome-icon :icon="['fas', 'xmark']" />
                </button>
            </div>

            <ul class="nav flex-column gap-2 mb-auto list-unstyled">
                <slot name="mobile" :close-menu="closeMenu">
                    <slot :close-menu="closeMenu"></slot>
                </slot>
            </ul>

            <div class="pt-3 border-top mt-4">
                <slot name="mobile-footer" :close-menu="closeMenu">
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center gap-2 py-2 w-100"
                    >
                        <font-awesome-icon :icon="['fas', 'right-from-bracket']" />
                        <span>Sair</span>
                    </Link>
                </slot>
            </div>
        </div>
    </transition>
</template>
