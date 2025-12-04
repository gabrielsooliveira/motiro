<script setup>
import Navbar from "@/js/Components/Navbar/NavbarSite.vue";
</script>

<template>
    <Navbar />
    <div class="container-fluid py-5 min-vh-100">
        <slot />
    </div>
</template>
