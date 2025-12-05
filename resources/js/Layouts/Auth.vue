<script setup>
import Toast from "@/js/Components/Toast.vue";
import ImageLogin from "@/assets/images/undraw/login-image.png";
import ImageRegister from "@/assets/images/undraw/register-image.png";

const content = [
    {
        image: ImageLogin,
        title: "Bem-vindo de volta!",
        subtitle: "Acesse sua conta e continue sua jornada.",
    },
    {
        image: ImageRegister,
        title: "Crie sua conta",
        subtitle: "Junte-se à nossa comunidade!",
    },
];
</script>

<template>
    <div class="min-vh-100 d-flex flex-column flex-lg-row">
        <div
            class="d-flex flex-column justify-content-center align-items-center p-5"
        >
            <div class="w-100">
                <slot />
            </div>
        </div>

        <div
            class="d-flex flex-column justify-content-center align-items-center flex-grow-1 text-center d-none d-lg-flex bg-light text-dark"
        >
            <template v-if="$page.url === '/login'">
                <img
                    :src="content[0].image"
                    alt="Imagem desktop"
                    class="img-fluid mb-4"
                    width="400"
                />

                <h2 class="fw-bold mb-3">{{ content[0].title }}</h2>
                <p class="lead mb-0">{{ content[0].subtitle }}</p>
            </template>
            <template v-else-if="$page.url === '/register'">
                <img
                    :src="content[1].image"
                    alt="Imagem desktop"
                    class="img-fluid mb-4"
                    width="400"
                />

                <h2 class="fw-bold mb-3">{{ content[1].title }}</h2>
                <p class="lead mb-0">{{ content[1].subtitle }}</p>
            </template>
        </div>
    </div>

    <Toast />
</template>
