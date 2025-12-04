<script setup>
import { computed } from "vue";

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
});

const unreadCount = computed(
    () => props.notifications.filter((n) => !n.read).length
);
</script>

<template>
    <div class="dropdown">
        <button
            class="btn border-0 position-relative"
            type="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            <font-awesome-icon icon="fas fa-bell" class="fs-5" />

            <span
                v-if="unreadCount > 0"
                class="position-absolute top-0 start-100 translate-middle badge bg-danger"
            >
                {{ unreadCount }}
            </span>
        </button>

        <div class="dropdown-menu dropdown-menu-end p-2 shadow">
            <h6 class="dropdown-header">Notificações</h6>

            <div v-if="notifications.length === 0" class="text-center py-2">
                <small class="text-muted">Sem notificações</small>
            </div>

            <div v-else>
                <div
                    v-for="(n, i) in notifications"
                    :key="i"
                    class="dropdown-item d-flex gap-2 align-items-start border-bottom py-2"
                >
                    <i
                        class="bi bi-circle-fill text-primary mt-1"
                        style="font-size: 8px"
                    ></i>
                    <div>
                        <div class="fw-semibold">{{ n.title }}</div>
                        <small class="text-muted">{{ n.message }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
