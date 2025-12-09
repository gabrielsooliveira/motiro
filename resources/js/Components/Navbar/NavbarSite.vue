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

// Fecha se clicar fora do painel no mobile
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
    const baseClasses = "navbar navbar-expand-lg fixed-top z-1 p-3";
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
                class="navbar-brand fw-semibold title-page"
                :href="route('home')"
                >Motirõ</Link
            >
            <button
                class="btn d-lg-none border-0"
                type="button"
                @click="toggleMenu"
            >
                <font-awesome-icon icon="fas fa-bars" size="lg" />
            </button>

            <div
                class="collapse navbar-collapse d-none d-lg-block flex-wrap align-items-center justify-content-between"
            >
                <ul class="nav justify-content-center fw-semibold">
                    <li class="nav-item">
                        <Link
                            :href="route('home')"
                            class="nav-link"
                            :class="
                                $page.url === '/'
                                    ? 'link-thirdary'
                                    : 'link-dark'
                            "
                        >
                            Início
                        </Link>
                    </li>

                    <li class="nav-item">
                        <Link
                            :href="route('hackathon.index')"
                            class="nav-link"
                            :class="
                                route().current('hackathon.*')
                                    ? 'link-thirdary'
                                    : 'link-dark'
                            "
                        >
                            Hackathons
                        </Link>
                    </li>
                </ul>
                <template v-if="user">
                    <Link
                        :href="route('dashboard.index')"
                        class="btn btn-primary fw-semibold"
                    >
                        <span class="me-2">Dashboard</span>
                        <font-awesome-icon
                            icon="fa-solid fa-right-to-bracket"
                        ></font-awesome-icon>
                    </Link>
                </template>
                <template v-else>
                    <div class="d-flex gap-2">
                        <Link
                            :href="route('login')"
                            class="btn btn-outline-thirdary fw-semibold"
                        >
                            <span>Entrar</span>
                        </Link>
                        <Link
                            :href="route('register')"
                            class="btn btn-primary fw-semibold"
                        >
                            <span>Criar Conta</span>
                        </Link>
                    </div>
                </template>
            </div>
        </div>
    </nav>

    <!-- Modal responsivo -->
    <transition name="fade">
        <div
            v-if="isMenuOpen"
            class="position-fixed top-0 start-0 w-100 h-100 bg-body d-flex flex-column p-4 z-3 overflow-y-auto"
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
                        :href="route('home')"
                        @click="closeMenu"
                        :class="
                            $page.url === '/' ? 'link-thirdary' : 'link-dark'
                        "
                        >Inicio</Link
                    >
                </li>
                <li class="nav-item">
                    <Link
                        class="nav-link"
                        :href="route('hackathon.index')"
                        @click="closeMenu"
                        :class="
                            route().current('hackathon.*')
                                ? 'link-thirdary'
                                : 'link-dark'
                        "
                        >Hackathons</Link
                    >
                </li>
                <template v-if="user">
                    <li><hr /></li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click="closeMenu"
                            >Perfil</a
                        >
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click="closeMenu"
                            >Configurações</a
                        >
                    </li>
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
                </template>
            </ul>
        </div>
    </transition>
</template>
