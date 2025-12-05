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
    <div class="card border-2 border-primary rounded shadow">
        <div
            class="card-body text-bg-primary d-flex justify-content-between align-items-center"
        >
            <div class="col-10">
                <h6 class="text-light fw-bold mt-0">
                    {{ title }}
                </h6>
                <h5 class="fs-4">{{ formattedValue }}</h5>
            </div>
            <div
                class="col-2 bg-secondary p-3 rounded d-flex align-items-center justify-content-center"
            >
                <font-awesome-icon
                    :icon="props.icon"
                    class="fa-1x text-light"
                />
            </div>
        </div>
    </div>
</template>
