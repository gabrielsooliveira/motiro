<script setup>
import Navbar from "@/js/components/Navbar.vue";
</script>

<template>
    <div>
        <Navbar />
        <div class="container-fluid mt-5 pt-5">
            <slot />
        </div>
    </div>
</template>
