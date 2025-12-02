<script setup>
import Navbar from "@/js/Components/Navbar/NavbarSite.vue";
</script>

<template>
    <Navbar />
    <div class="container-fluid mt-2 py-5">
        <slot />
    </div>
</template>
