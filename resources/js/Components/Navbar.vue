<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, onMounted, onBeforeUnmount } from "vue";

defineProps({ user: Object });
const isMenuOpen = ref(false);
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
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
    <nav
        class="navbar navbar-expand-lg navbar-light bg-light shadow-sm fixed-top z-1"
    >
        <div class="container-fluid">
            <!-- Logo -->
            <Link
                class="navbar-brand fw-semibold text-primary title-page"
                :href="route('index')"
                >Motirõ</Link
            >

            <!-- Botão toggle -->
            <button
                class="btn d-lg-none border-0"
                type="button"
                @click="toggleMenu"
            >
                <font-awesome-icon icon="fas fa-bars" size="lg" />
            </button>

            <!-- Navbar padrão para desktop -->
            <div
                class="collapse navbar-collapse d-none d-lg-block flex-wrap align-items-center justify-content-between"
            >
                <ul class="nav justify-content-center fw-semibold">
                    <li class="nav-item">
                        <Link
                            class="nav-link"
                            :href="route('index')"
                            :class="{
                                'link-secondary': $page.url === '/',
                            }"
                            >Inicio</Link
                        >
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Hackathons</a>
                    </li>
                </ul>
                <template v-if="user">
                    <div class="d-flex align-items-center">
                        <div class="dropdown ms-auto">
                            <a
                                class="nav-link dropdown-toggle d-flex align-items-center"
                                href="#"
                                id="userDropdown"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <img
                                    src="https://dummyimage.com/600x400/000/fff"
                                    class="rounded-circle me-2"
                                    alt="User Avatar"
                                    width="32"
                                    height="32"
                                />
                                <span>Gabriel</span>
                            </a>
                            <ul
                                class="dropdown-menu dropdown-menu-end shadow mt-3"
                                aria-labelledby="userDropdown"
                            >
                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="#"
                                        @click="closeMenu"
                                        >Perfil</a
                                    >
                                </li>
                                <li>
                                    <a
                                        class="dropdown-item"
                                        href="#"
                                        @click="closeMenu"
                                        >Configurações</a
                                    >
                                </li>
                                <li><hr class="dropdown-divider" /></li>
                                <li>
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="dropdown-item text-danger"
                                        ><font-awesome-icon
                                            icon="fa-solid fa-right-from-bracket"
                                        />
                                        Logout</Link
                                    >
                                </li>
                            </ul>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <Link
                        :href="route('login')"
                        class="btn btn-primary fw-semibold"
                    >
                        <span class="me-2">Entrar</span>
                        <font-awesome-icon
                            icon="fa-solid fa-right-to-bracket"
                        ></font-awesome-icon>
                    </Link>
                </template>
            </div>
        </div>
    </nav>

    <!-- Modal responsivo -->
    <transition name="fade">
        <div
            v-if="isMenuOpen"
            class="position-fixed top-0 start-0 w-100 h-100 bg-light d-flex flex-column p-4 z-3 overflow-y-auto"
        >
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-primary mb-0">Menu</h5>
                <button class="btn btn-link text-dark fs-4" @click="closeMenu">
                    <font-awesome-icon icon="fas fa-xmark" />
                </button>
            </div>

            <ul class="nav flex-column fw-semibold">
                <li class="nav-item">
                    <Link
                        class="nav-link"
                        :href="route('index')"
                        @click="closeMenu"
                        :class="{
                            'link-secondary': $page.url === '/',
                        }"
                        >Inicio</Link
                    >
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" @click="closeMenu"
                        >Hackathons</a
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
