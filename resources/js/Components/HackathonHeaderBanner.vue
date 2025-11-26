<script setup>
import { defineProps } from "vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faTag } from "@fortawesome/free-solid-svg-icons";

defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="position-relative mb-1">
        <!-- Banner de fundo -->
        <div
            class="overflow-hidden rounded-5 rounded-bottom-0"
            style="height: 260px"
        >
            <img
                :src="hackathon.banner"
                alt="Hackathon Banner"
                class="w-100 h-100 object-fit-cover"
            />
        </div>

        <!-- Conteúdo sobre o banner -->
        <div
            class="position-absolute bottom-0 start-0 mb-3 ms-3 d-flex align-items-center gap-3 px-3"
        >
            <!-- Logo/Avatar -->

            <img
                :src="hackathon.logo"
                alt="Hackathon Logo"
                class="object-fit-contain bg-white rounded-circle border"
                height="95"
            />

            <!-- Título + tags -->
            <div class="text-white ms-3">
                <h1 class="fw-bold mb-1">{{ hackathon.title }}</h1>
                <div class="d-flex flex-wrap gap-2">
                    <span
                        v-for="tag in hackathon.tags"
                        :key="tag"
                        class="badge bg-primary bg-opacity-75 d-flex align-items-center"
                    >
                        <FontAwesomeIcon :icon="faTag" class="me-1" />
                        {{ tag }}
                    </span>
                </div>
                <p class="mt-2">{{ hackathon.subtitle }}</p>
            </div>
        </div>
    </div>
</template>
