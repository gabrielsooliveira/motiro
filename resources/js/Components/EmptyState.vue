<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    icon: {
        type: String,
        default: "folder-open",
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
    actionText: {
        type: String,
        default: null,
    },
    actionHref: {
        type: String,
        default: null,
    },
});

defineEmits(["action"]);
</script>

<template>
    <div class="empty-state">
        <div class="empty-state-icon">
            <font-awesome-icon :icon="['fas', icon]" />
        </div>
        <h5>{{ title }}</h5>
        <p>{{ message }}</p>
        <template v-if="actionText">
            <Link v-if="actionHref" :href="actionHref" class="btn btn-primary btn-sm">
                {{ actionText }}
            </Link>
            <button v-else type="button" class="btn btn-primary btn-sm" @click="$emit('action')">
                {{ actionText }}
            </button>
        </template>
    </div>
</template>
