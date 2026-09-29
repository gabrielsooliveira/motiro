<script setup>
import Sidebar from "@/js/Components/Sidebar.vue";
import Toast from "@/js/Components/Toast.vue";
</script>

<template>
    <div class="dashboard-wrapper">
        <Sidebar />
        <main class="dashboard-main py-5 px-4 p-lg-3">
            <slot></slot>
            <Toast />
        </main>
    </div>
</template>
