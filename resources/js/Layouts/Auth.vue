<script setup>
import Toast from "@/js/Components/Toast.vue";
</script>

<template>
    <div
        class="d-flex justify-content-center align-items-center bg-primary bg-gradient py-3 min-vh-100"
    >
        <div class="card p-5 shadow-lg rounded-4">
            <slot />
        </div>
    </div>

    <Toast />
</template>
