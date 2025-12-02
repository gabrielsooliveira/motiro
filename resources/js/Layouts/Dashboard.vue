<script setup>
import Toast from "@/js/Components/Toast.vue";
import Sidebar from "@/js/Components/Sidebar.vue";
</script>

<template>
    <Sidebar>
        <slot />
    </Sidebar>

    <Toast />
</template>
