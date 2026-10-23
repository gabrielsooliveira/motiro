<script setup>
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import ModalDialog from "@/js/Components/Modals/ModalDialog.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "submitted"]);

const imagePreview = ref(null);
const fileInput = ref(null);

const form = useForm({
    name: "",
    description: "",
    image: null,
    invite_emails: "",
    set_as_leader: true,
});

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (event) => {
            imagePreview.value = event.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.image = null;
        imagePreview.value = null;
    }
};

const clearImage = () => {
    form.image = null;
    imagePreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

const closeModal = () => {
    emit("close");
};

const submitForm = () => {
    const targetRoute = route().has("participant.guilds.store")
        ? route("participant.guilds.store")
        : route("participant.teams.store");
    form.post(targetRoute, {
        forceFormData: true,
        onSuccess: () => {
            emit("submitted");
            closeModal();
            form.reset();
            imagePreview.value = null;
        },
    });
};
</script>

<template>
    <ModalDialog
        :is-visible="show"
        title="Criar Nova Guild"
        size="lg"
        @close="closeModal"
    >
        <form @submit.prevent="submitForm">
            <!-- Explicação de Domínio Rápida -->
            <div class="alert alert-primary bg-primary bg-opacity-10 border-0 text-primary-emphasis d-flex align-items-start gap-2 p-3 rounded-3 mb-4 small">
                <font-awesome-icon :icon="['fas', 'circle-info']" class="mt-1 flex-shrink-0" />
                <div>
                    <strong>Guild Permanente:</strong> Cria uma equipe fixa que pertence ao seu perfil no Motirõ. Integrantes convidados não são inscritos automaticamente em hackathons.
                </div>
            </div>

            <!-- Nome da Guild -->
            <div class="mb-3">
                <label class="form-label fw-bold text-dark small mb-1">
                    Nome da Guild <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <font-awesome-icon :icon="['fas', 'users']" />
                    </span>
                    <input
                        v-model="form.name"
                        type="text"
                        class="form-control border-start-0"
                        :class="{ 'is-invalid': form.errors.name }"
                        placeholder="Ex: ByteCraft, CodeKnights, InovaTech"
                        required
                        maxlength="100"
                    />
                </div>
                <div v-if="form.errors.name" class="invalid-feedback d-block small mt-1">
                    {{ form.errors.name }}
                </div>
            </div>

            <!-- Descrição da Guild -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label fw-bold text-dark small mb-0">Descrição da Guild</label>
                    <span class="text-muted small">{{ form.description?.length || 0 }}/500</span>
                </div>
                <textarea
                    v-model="form.description"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.description }"
                    rows="3"
                    maxlength="500"
                    placeholder="Conte sobre o propósito da guild, competências principais ou objetivos em conjunto..."
                ></textarea>
                <div v-if="form.errors.description" class="invalid-feedback d-block small mt-1">
                    {{ form.errors.description }}
                </div>
            </div>

            <!-- Upload de Imagem / Logo da Guild -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark small mb-1">
                    Imagem ou Capa da Guild (opcional)
                </label>
                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                    <div
                        class="rounded-3 overflow-hidden border bg-white flex-shrink-0 d-flex align-items-center justify-content-center guild-avatar"
                    >
                        <img
                            v-if="imagePreview"
                            :src="imagePreview"
                            alt="Prévia"
                            class="w-100 h-100 object-fit-cover"
                        />
                        <font-awesome-icon
                            v-else
                            :icon="['fas', 'image']"
                            class="text-muted fs-4"
                        />
                    </div>
                    <div class="flex-grow-1">
                        <input
                            ref="fileInput"
                            type="file"
                            class="form-control form-control-sm"
                            accept="image/png,image/jpeg,image/jpg,image/webp,image/gif"
                            @change="handleImageChange"
                        />
                        <div class="text-muted small mt-1">
                            Formatos recomendados: PNG, JPG ou WEBP (máx. 2MB).
                        </div>
                    </div>
                    <button
                        v-if="imagePreview"
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        title="Remover imagem selecionada"
                        @click="clearImage"
                    >
                        <font-awesome-icon :icon="['fas', 'trash-can']" />
                    </button>
                </div>
                <div v-if="form.errors.image" class="invalid-feedback d-block small mt-1">
                    {{ form.errors.image }}
                </div>
            </div>

            <!-- Convites por E-mail -->
            <div class="mb-4">
                <label class="form-label fw-bold text-dark small mb-1">
                    Convidar Membros por E-mail (opcional)
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted">
                        <font-awesome-icon :icon="['fas', 'envelope']" />
                    </span>
                    <input
                        v-model="form.invite_emails"
                        type="text"
                        class="form-control border-start-0"
                        :class="{ 'is-invalid': form.errors.invite_emails }"
                        placeholder="dev1@empresa.com, colega@faculdade.com"
                    />
                </div>
                <div class="text-muted small mt-1">
                    Separe múltiplos e-mails por vírgula. Cada participante receberá um convite por e-mail com link exclusivo para aceitar.
                </div>
                <div v-if="form.errors.invite_emails" class="invalid-feedback d-block small mt-1">
                    {{ form.errors.invite_emails }}
                </div>
            </div>

            <!-- Opção de Liderança -->
            <div class="p-3 bg-light rounded-3 border mb-4">
                <div class="form-check mb-0">
                    <input
                        id="modal_create_set_as_leader"
                        v-model="form.set_as_leader"
                        class="form-check-input"
                        type="checkbox"
                    />
                    <label class="form-check-label fw-bold small text-dark" for="modal_create_set_as_leader">
                        Definir-me como líder ativo desta guild
                    </label>
                    <p class="text-muted small mb-0 mt-1">
                        Você poderá transferir ou delegar a liderança para qualquer integrante aceito a qualquer momento nas configurações da guild.
                    </p>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" @click="closeModal">
                    Cancelar
                </button>
                <button
                    type="submit"
                    class="btn btn-primary rounded-pill px-4 d-inline-flex align-items-center gap-2 shadow-sm"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="spinner-border spinner-border-sm" role="status"></span>
                    <font-awesome-icon v-else :icon="['fas', 'plus']" />
                    <span>{{ form.processing ? 'Criando Guild...' : 'Criar Guild' }}</span>
                </button>
            </div>
        </form>
    </ModalDialog>
</template>
