<script setup>
import { defineProps } from "vue";

defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="card shadow-sm h-100 border-1 border-light">
        <div class="position-relative">
            <img
                :src="item.banner"
                class="card-img-top object-fit-cover"
                alt="Banner"
                height="160"
            />
            <img
                :src="item.logo"
                class="position-absolute top-50 start-0 rounded border object-fit-cover bg-white ms-2"
                height="90"
                loading="lazy"
            />
        </div>

        <div class="card-body d-flex flex-column bg-white">
            <slot />
        </div>
    </div>
</template>
