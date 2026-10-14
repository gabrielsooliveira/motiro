<script setup>
import Navbar from "@/js/Components/Navbar/NavbarSite.vue";
</script>

<template>
    <Navbar />
    <div class="pt-5 pt-xl-0 pb-3 min-vh-100">
        <slot />
    </div>
</template>
