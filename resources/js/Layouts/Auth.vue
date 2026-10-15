<script setup>
import Toast from "@/js/Components/Toast.vue";
import ImageLogin from "@/assets/images/undraw/login-image.svg";
import ImageRegister from "@/assets/images/undraw/register-image.svg";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();

const isRegister = computed(() => {
    return page.url.includes('register') || page.url.includes('cadastro');
});

const isOrg = computed(() => {
    return page.url.includes('organizacao') || page.url.includes('organization');
});

const currentContent = computed(() => {
    if (isRegister.value) {
        return {
            image: ImageRegister,
            title: isOrg.value ? "Cadastre sua Organização" : "Crie sua conta de Participante",
            subtitle: isOrg.value 
                ? "Lance desafios, descubra talentos e impulsione a inovação." 
                : "Conecte-se com equipes multidisciplinares e crie soluções transformadoras.",
        };
    }

    return {
        image: ImageLogin,
        title: isOrg.value ? "Área da Organização" : "Área do Participante",
        subtitle: isOrg.value 
            ? "Gerencie seus hackathons, etapas, equipes e relatórios de avaliação." 
            : "Acesse seus hackathons, gerencie suas equipes e submeta projetos.",
    };
});
</script>

<template>
    <div class="min-vh-100 d-flex flex-column flex-lg-row">
        <div
            class="d-flex flex-column justify-content-center align-items-center p-3 p-md-4 p-lg-5 bg-dark text-white min-vh-100 auth-panel"
            :class="isRegister ? 'auth-panel--register' : 'auth-panel--login'"
        >
            <div class="w-100">
                <slot />
            </div>
        </div>

        <div
            class="d-flex flex-column justify-content-center align-items-center flex-grow-1 text-center d-none d-lg-flex p-5 bg-light"
        >
            <img
                :src="currentContent.image"
                alt="Imagem ilustrativa"
                class="img-fluid mb-4 auth-illustration"
            />

            <h2 class="fw-bold mb-2 text-dark">{{ currentContent.title }}</h2>
            <p class="lead mb-0 text-muted mx-auto auth-subtitle">
                {{ currentContent.subtitle }}
            </p>
        </div>
    </div>

    <Toast />
</template>
