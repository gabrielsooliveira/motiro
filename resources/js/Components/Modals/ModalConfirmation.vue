<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { Modal } from 'bootstrap'

const emit = defineEmits(['confirm', 'close'])

const modalRef = ref(null)
let modalInstance = null

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false,
  },
  message: String,
  subMessage: {
    type: String,
    default: '',
  },
  confirmText: {
    type: String,
    default: 'Confirmar',
  },
  cancelText: {
    type: String,
    default: 'Cancelar',
  }
})

const confirmAction = () => {
  emit('confirm')
}

const closeModal = () => {
  emit('close')
}

watch(() => props.isVisible, (newVal) => {
  if (modalInstance) {
    newVal ? modalInstance.show() : modalInstance.hide()
  }
})

onMounted(() => {
  if (modalRef.value) {
    modalInstance = new Modal(modalRef.value, {
      backdrop: 'static',
      keyboard: false
    })

    modalRef.value.addEventListener('hidden.bs.modal', () => {
      emit('close')
    })
  }
})

onBeforeUnmount(() => {
  if (modalInstance) {
    modalInstance.dispose()
  }
})
</script>

<template>
  <div
    ref="modalRef"
    class="modal modal-sheet fade"
    tabindex="-1"
    role="dialog"
    data-bs-backdrop="static"
    aria-hidden="true"
  >
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-3 shadow">
        <div class="modal-body p-4 text-center">
          <h5 class="mb-0">{{ message }}</h5>
          <p v-if="subMessage" class="text-muted mt-2 mb-0">{{ subMessage }}</p>
        </div>
        <div class="modal-footer flex-nowrap p-0">
          <button
            type="button"
            class="btn btn-lg btn-link text-decoration-none col-6 py-3 m-0 rounded-0 border-end"
            @click="closeModal"
          >
            <strong>{{ cancelText }}</strong>
          </button>
          <button
            type="button"
            class="btn btn-lg btn-link text-decoration-none col-6 py-3 m-0 rounded-0"
            @click="confirmAction"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>