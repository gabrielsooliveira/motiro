<script setup>
import Navbar from "@/js/Components/Navbar/NavbarSite.vue";
</script>

<template>
    <Navbar />
    <div class="pt-5 pb-3 min-vh-100">
        <slot />
    </div>
</template>
