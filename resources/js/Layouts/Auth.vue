<script setup>
import Toast from "@/js/Components/Toast.vue";
</script>

<template>
    <div class="min-vh-100 d-flex flex-column flex-lg-row">
        <div
            class="d-flex flex-column justify-content-center align-items-center p-5"
        >
            <img
                :src="ImageMobile"
                alt="Logo"
                class="img-fluid mb-4 d-block d-lg-none"
                width="400"
            />
            <div class="w-100">
                <slot />
            </div>
        </div>

        <div
            class="text-light d-flex flex-column justify-content-center align-items-center flex-grow-1 text-center d-none d-lg-flex"
        >
            <img
                :src="ImageMain"
                alt="Logo"
                class="img-fluid mb-4"
                width="400"
            />
            <h2 class="fw-bold mb-3">Bem-vindo de volta!</h2>
            <p class="lead mb-0">Acesse sua conta e continue sua jornada.</p>
        </div>
    </div>

    <Toast />
</template>
