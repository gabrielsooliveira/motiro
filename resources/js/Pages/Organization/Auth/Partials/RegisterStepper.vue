<script setup>
defineProps({
    currentStep: {
        type: Number,
        required: true,
    },
});

const steps = [
    { number: 1, label: "Sua Conta" },
    { number: 2, label: "Organização" },
    { number: 3, label: "Revisar" },
];
</script>

<template>
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between position-relative px-2">
            <div class="position-absolute top-50 start-0 end-0 translate-middle-y bg-secondary bg-opacity-25 step-progress-line"></div>
            <div
                class="position-absolute top-50 start-0 translate-middle-y bg-primary step-progress-line"
                :class="currentStep === 1 ? 'step-progress-0' : currentStep === 2 ? 'step-progress-50' : 'step-progress-100'"
            ></div>

            <div
                v-for="step in steps"
                :key="step.number"
                class="d-flex flex-column align-items-center position-relative step-indicator-wrapper"
            >
                <div
                    class="rounded-circle d-flex align-items-center justify-content-center fw-bold small step-circle"
                    :class="[
                        currentStep > step.number
                            ? 'bg-success text-white'
                            : currentStep === step.number
                            ? 'bg-primary text-white shadow'
                            : 'bg-dark border border-secondary text-white'
                    ]"
                >
                    <font-awesome-icon v-if="currentStep > step.number" :icon="['fas', 'check']" class="small" />
                    <span v-else>{{ step.number }}</span>
                </div>
                <span
                    class="small mt-1 text-nowrap"
                    :class="currentStep === step.number ? 'text-primary fw-bold' : 'text-white'"
                >
                    {{ step.label }}
                </span>
            </div>
        </div>
    </div>
</template>
