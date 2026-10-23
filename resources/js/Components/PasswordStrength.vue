<script setup>
import { computed } from "vue";

const props = defineProps({
    password: {
        type: String,
        default: "",
    },
    alwaysVisible: {
        type: Boolean,
        default: true,
    },
});

const passwordScore = computed(() => {
    const pwd = props.password || "";
    if (!pwd) return 0;

    let score = 0;
    if (pwd.length >= 6) score++;
    if (pwd.length >= 8) score++;
    if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd) && /[^a-zA-Z0-9]/.test(pwd)) {
        score++;
    } else if (score >= 3 && (/[0-9]/.test(pwd) || /[^a-zA-Z0-9]/.test(pwd))) {
        score++;
    }

    return Math.min(Math.max(score, 1), 4);
});

const strengthMeta = computed(() => {
    if (!props.password) {
        return { label: "", colorClass: "" };
    }

    switch (passwordScore.value) {
        case 1:
            return { label: "Senha fraca (mínimo 6 dígitos)", colorClass: "text-danger" };
        case 2:
            return { label: "Senha razoável (adicione maiúsculas e números)", colorClass: "text-warning" };
        case 3:
            return { label: "Senha boa (adicione caracteres especiais)", colorClass: "text-info" };
        case 4:
            return { label: "Senha forte", colorClass: "text-success" };
        default:
            return { label: "", colorClass: "" };
    }
});

defineExpose({
    score: passwordScore,
});
</script>

<template>
    <div v-if="alwaysVisible || password" class="password-strength-container">
        <div class="strength" data-bs-strength :data-score="passwordScore">
            <div class="strength-segment"></div>
            <div class="strength-segment"></div>
            <div class="strength-segment"></div>
            <div class="strength-segment"></div>
        </div>
        <span class="strength-text" :class="strengthMeta.colorClass">
            {{ strengthMeta.label }}
        </span>
    </div>
</template>
