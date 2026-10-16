<script setup>
import Navbar from "@/js/Components/Navbar/NavbarSite.vue";
import Footer from "@/js/Components/Footer.vue";
</script>

<template>
    <div class="site-layout d-flex flex-column">
        <Navbar />
        <main class="site-main flex-grow-1 min-vh-100">
            <slot />
        </main>
        <Footer />
    </div>
</template>
