<script setup>
defineProps({
    user: {
        type: Object,
        required: true,
    },
});

defineEmits(["logout"]);
</script>

<template>
    <div class="card bg-dark border-secondary-subtle p-2 px-3 rounded-3 mb-3 d-flex flex-row align-items-center justify-content-between shadow-sm">
        <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
            <div class="rounded-circle bg-success bg-opacity-20 text-success d-flex align-items-center justify-content-center flex-shrink-0 user-avatar--sm">
                <font-awesome-icon :icon="['fas', 'user-check']" class="small" />
            </div>
            <div class="d-flex flex-column overflow-hidden">
                <span class="small text-white fw-semibold text-truncate">{{ user.name }}</span>
                <span class="text-white small text-truncate fs-8">{{ user.email }}</span>
            </div>
        </div>
        <button
            type="button"
            class="btn btn-sm btn-outline-secondary text-light text-nowrap fs-8"
            title="Alternar para outra conta pessoal"
            @click="$emit('logout')"
        >
            Trocar conta
        </button>
    </div>
</template>
