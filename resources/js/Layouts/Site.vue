<script setup>
import Navbar from "@/js/Components/Navbar.vue";
</script>

<template>
    <Navbar />
    <div class="container-fluid mt-5 py-5">
        <slot />
    </div>
</template>
