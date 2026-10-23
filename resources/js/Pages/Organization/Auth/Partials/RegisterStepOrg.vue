<script setup>
import { ref, onUnmounted } from "vue";

const props = defineProps({
    orgForm: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    canGoBack: {
        type: Boolean,
        default: true,
    },
    logoPreview: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["back", "next", "update:logoPreview"]);

const step2Errors = ref({});
const logoError = ref("");

const getFieldError = (field) => {
    return step2Errors.value[field] || props.orgForm.errors[field];
};

const handleLogoUpload = (e) => {
    const file = e.target.files[0];
    logoError.value = "";

    if (file) {
        if (!file.type.startsWith("image/")) {
            logoError.value = "Por favor, selecione um arquivo de imagem válido (PNG, JPG, SVG ou WEBP).";
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            logoError.value = "O logotipo deve ter no máximo 2MB.";
            return;
        }

        if (props.logoPreview) {
            URL.revokeObjectURL(props.logoPreview);
        }

        props.orgForm.logo = file;
        emit("update:logoPreview", URL.createObjectURL(file));
    }
};

const removeLogo = () => {
    if (props.logoPreview) {
        URL.revokeObjectURL(props.logoPreview);
    }
    props.orgForm.logo = null;
    emit("update:logoPreview", null);
};

onUnmounted(() => {
    if (props.logoPreview) {
        URL.revokeObjectURL(props.logoPreview);
    }
});

const validateStep2 = () => {
    step2Errors.value = {};

    if (!props.orgForm.organization_name || !props.orgForm.organization_name.trim()) {
        step2Errors.value.organization_name = "O nome da organização é obrigatório.";
    }
    if (!props.orgForm.organization_type) {
        step2Errors.value.organization_type = "Selecione o tipo de organização.";
    }
    if (props.orgForm.organization_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(props.orgForm.organization_email)) {
        step2Errors.value.organization_email = "Informe um endereço de e-mail institucional válido.";
    }
    if (props.orgForm.website && !/^https?:\/\/.+/.test(props.orgForm.website)) {
        step2Errors.value.website = "Informe uma URL válida (ex: https://suaorganizacao.com).";
    }

    return Object.keys(step2Errors.value).length === 0;
};

const goToNext = () => {
    if (validateStep2()) {
        emit("next");
    }
};
</script>

<template>
    <div class="animate__animated animate__fadeIn">
        <div class="mb-3 text-center">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill small mb-2 fw-semibold">
                Etapa 2 de 3 &bull; Dados Institucionais
            </span>
            <h2 class="h5 fw-bold text-white mb-1">Vamos cadastrar sua organização</h2>
            <p class="text-white small mb-0">
                Agora vamos cadastrar a instituição que você representa. Os dados desta etapa são independentes das informações da sua conta pessoal.
            </p>
        </div>

        <form @submit.prevent="goToNext">
            <!-- Organization Name -->
            <div class="mb-3">
                <label class="form-label text-white small fw-semibold" for="org_name">
                    Nome da Organização *
                </label>
                <input
                    id="org_name"
                    v-model="orgForm.organization_name"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': getFieldError('organization_name') }"
                    placeholder="ex: InovaTech Labs, Universidade XPTO, Comunidade Dev"
                    required
                />
                <span v-if="getFieldError('organization_name')" class="text-danger small mt-1 d-block">
                    {{ getFieldError('organization_name') }}
                </span>
            </div>

            <!-- Organization Type -->
            <div class="mb-3">
                <label class="form-label text-white small fw-semibold" for="org_type">
                    Tipo de Organização *
                </label>
                <select
                    id="org_type"
                    v-model="orgForm.organization_type"
                    class="form-select"
                    :class="{ 'is-invalid': getFieldError('organization_type') }"
                    required
                >
                    <option v-for="cat in categories" :key="cat" :value="cat">
                        {{ cat }}
                    </option>
                </select>
                <span v-if="getFieldError('organization_type')" class="text-danger small mt-1 d-block">
                    {{ getFieldError('organization_type') }}
                </span>
            </div>

            <!-- Institutional Email -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-white small fw-semibold mb-0" for="org_email">
                        E-mail Institucional ou de Contato
                    </label>
                    <span class="text-white small">Opcional</span>
                </div>
                <input
                    id="org_email"
                    v-model="orgForm.organization_email"
                    type="email"
                    class="form-control"
                    :class="{ 'is-invalid': getFieldError('organization_email') }"
                    placeholder="contato@organizacao.com"
                />
                <div class="form-text text-white small">
                    Endereço público da organização. Não altera nem substitui o seu e-mail de login pessoal.
                </div>
                <span v-if="getFieldError('organization_email')" class="text-danger small mt-1 d-block">
                    {{ getFieldError('organization_email') }}
                </span>
            </div>

            <!-- Website -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-white small fw-semibold mb-0" for="org_web">
                        Website Oficial
                    </label>
                    <span class="text-white small">Opcional</span>
                </div>
                <input
                    id="org_web"
                    v-model="orgForm.website"
                    type="url"
                    class="form-control"
                    :class="{ 'is-invalid': getFieldError('website') }"
                    placeholder="https://suaorganizacao.com"
                />
                <span v-if="getFieldError('website')" class="text-danger small mt-1 d-block">
                    {{ getFieldError('website') }}
                </span>
            </div>

            <!-- Logo Upload -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-white small fw-semibold mb-0" for="org_logo">
                        Logotipo da Instituição
                    </label>
                    <span class="text-white small">Opcional</span>
                </div>
                <input
                    id="org_logo"
                    type="file"
                    class="form-control"
                    accept="image/*"
                    @change="handleLogoUpload"
                />
                <div class="form-text text-white small">
                    Formatos aceitos: PNG, JPG, SVG ou WEBP (máx. 2MB).
                </div>
                <span v-if="logoError" class="text-danger small mt-1 d-block">
                    {{ logoError }}
                </span>

                <div v-if="logoPreview" class="d-flex align-items-center gap-3 mt-2 p-2 bg-dark border border-secondary rounded-3">
                    <img :src="logoPreview" alt="Prévia do logotipo" class="rounded object-fit-cover logo-preview" />
                    <div class="flex-grow-1 overflow-hidden">
                        <span class="text-white small fw-semibold d-block text-truncate">Logotipo selecionado</span>
                        <span class="text-white small">Pronto para envio</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLogo">
                        Remover
                    </button>
                </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-white small fw-semibold mb-0" for="org_desc">
                        Descrição Institucional
                    </label>
                    <span class="text-white small">Opcional</span>
                </div>
                <textarea
                    id="org_desc"
                    v-model="orgForm.description"
                    class="form-control"
                    rows="3"
                    placeholder="Apresente sua instituição, área de atuação e objetivos ao realizar hackathons..."
                ></textarea>
            </div>

            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between align-items-center gap-2">
                <button
                    v-if="canGoBack"
                    type="button"
                    class="btn btn-outline-secondary text-white"
                    @click="$emit('back')"
                >
                    &larr; Voltar
                </button>
                <div v-else></div>

                <button
                    type="submit"
                    class="btn btn-primary fw-semibold px-4 py-2 d-flex align-items-center gap-2 shadow-sm"
                >
                    <span>Avançar para Revisão</span>
                    <font-awesome-icon :icon="['fas', 'arrow-right']" class="small" />
                </button>
            </div>
        </form>
    </div>
</template>
