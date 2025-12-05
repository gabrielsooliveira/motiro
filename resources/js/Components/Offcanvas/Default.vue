<script setup>
import { onMounted, ref, watch } from "vue";
import { Offcanvas } from "bootstrap";

const props = defineProps({
    id: { type: String, required: true },
    title: { type: String, default: "" },
    subtitle: { type: String, default: "" },
    placement: { type: String, default: "start" },
    modelValue: { type: Boolean, default: false },
});

const emits = defineEmits(["update:modelValue"]);

const offcanvasRef = ref(null);
let instance = null;

onMounted(() => {
    instance = new Offcanvas(offcanvasRef.value);

    offcanvasRef.value.addEventListener("hidden.bs.offcanvas", () => {
        emits("update:modelValue", false);
    });
});

watch(
    () => props.modelValue,
    (value) => {
        if (!instance) return;
        value ? instance.show() : instance.hide();
    }
);
</script>

<template>
    <div
        class="offcanvas"
        :class="`offcanvas-${placement}`"
        tabindex="-1"
        :id="id"
        :aria-labelledby="`${id}-label`"
        ref="offcanvasRef"
    >
        <div class="offcanvas-header">
            <div>
                <h5 class="offcanvas-title" :id="`${id}-label`">
                    {{ title }}
                </h5>
            </div>
            <button
                class="btn border-0 ms-auto"
                data-bs-dismiss="offcanvas"
                @click="emits('update:modelValue', false)"
            >
                <font-awesome-icon icon="fa-solid fa-xmark" class="fs-4 pt-2" />
            </button>
        </div>

        <div class="offcanvas-body">
            <slot />
        </div>
    </div>
</template>
