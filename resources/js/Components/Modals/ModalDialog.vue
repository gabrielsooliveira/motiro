<script setup>
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue';
import { Modal } from 'bootstrap';

const props = defineProps({
    title: String,
    size: {
        type: String,
        default: 'md'
    },
    isVisible: {
        type: Boolean,
        default: false,
    }
});

const emit = defineEmits(['close']);
const modalRef = ref(null);
let modalInstance = null;

const closeModal = () => {
    emit('close');
};

watch(() => props.isVisible, (newVal) => {
    if (newVal) {
        if (modalInstance) {
            modalInstance.show();
        }
    } else {
        if (modalInstance) {
            modalInstance.hide();
        }
    }
});

onMounted(() => {
    if (modalRef.value) {
        modalInstance = new Modal(modalRef.value);
        modalRef.value.addEventListener('hidden.bs.modal', () => {
            emit('close');
        });
        if (props.isVisible) {
            modalInstance.show();
        }
    }
});

onBeforeUnmount(() => {
    if (modalInstance) {
        modalInstance.dispose();
    }
});

const sizeClass = computed(() => {
    return props.size ? `modal-${props.size}` : '';
});
</script>

<template>
    <div ref="modalRef" class="modal fade" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" :class="sizeClass">
            <div class="modal-content rounded-4 shadow">
                <div class="d-flex justify-content-between align-items-center px-3 pt-3">
                    <h4 class="modal-title fw-bold">{{ title }}</h4>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <slot></slot>
                </div>
            </div>
        </div>
    </div>
</template>