<script setup>
import { computed } from "vue";
import { formatCurrencyBRL } from "@/js/utils";

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: [String, Array],
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    type: {
        type: String,
        default: "number",
    },
    formatter: {
        type: Function,
        default: null,
    },
});

const formattedValue = computed(() => {
    if (props.formatter) return props.formatter(props.value);

    switch (props.type) {
        case "money":
            return formatCurrencyBRL(props.value);
        case "percent":
            return `${props.value}%`;
        default:
            return props.value;
    }
});
</script>

<template>
    <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <div class="flex-grow-1 pe-2">
                <span class="text-muted small text-uppercase fw-semibold">{{ title }}</span>
                <h3 class="h4 fw-bold mb-0 mt-1 text-dark">{{ formattedValue }}</h3>
            </div>
            <div class="card-simple-icon bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                <font-awesome-icon :icon="props.icon" class="fs-5" />
            </div>
        </div>
    </div>
</template>
