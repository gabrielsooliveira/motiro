<script setup>
import Navbar from "@/js/Components/Navbar/NavbarSite.vue";
import Footer from "@/js/Components/Footer.vue";

</script>

<template>
    <Navbar />
    <div class="pt-5 min-vh-100">
        <slot />
    </div>
    <Footer />
</template>
