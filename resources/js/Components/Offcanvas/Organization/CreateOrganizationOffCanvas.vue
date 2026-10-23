<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Offcanvas from "@/js/Components/Offcanvas/Default.vue";

const open = ref(false);

const form = useForm({
    name: "",
    email: "",
    type: "Empresa",
    website: "",
    description: "",
});

const categories = [
    "Empresa",
    "Universidade ou instituição de ensino",
    "Comunidade ou coletivo",
    "Organização social / Sem fins lucrativos",
    "Instituição pública",
    "Startup",
    "Outra",
];

const storeOrganization = () => {
    form.post(route("organization.store"), {
        onSuccess: () => {
            open.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <button
        class="btn btn-sm btn-outline-primary fw-semibold"
        @click="open = true"
    >
        Criar Organização
    </button>

    <Offcanvas
        id="menu-create-organization"
        title="Criar nova organização"
        placement="end"
        v-model="open"
    >
        <form @submit.prevent="storeOrganization">
            <div class="mb-3">
                <label for="name" class="form-label">Nome da Instituição *</label>
                <input
                    type="text"
                    class="form-control"
                    id="name"
                    name="name"
                    v-model="form.name"
                    required
                />
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Tipo de Organização</label>
                <select id="type" class="form-select" v-model="form.type">
                    <option v-for="cat in categories" :key="cat" :value="cat">
                        {{ cat }}
                    </option>
                </select>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail Institucional</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    v-model="form.email"
                    placeholder="contato@organizacao.com"
                />
            </div>

            <div class="mb-3">
                <label for="website" class="form-label">Website</label>
                <input
                    type="url"
                    class="form-control"
                    id="website"
                    name="website"
                    v-model="form.website"
                    placeholder="https://..."
                />
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrição</label>
                <textarea
                    class="form-control"
                    id="description"
                    name="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Breve descrição dos objetivos e atuação..."
                ></textarea>
            </div>

            <div class="d-grid gap-2">
                <button
                    type="submit"
                    class="btn btn-primary"
                    :disabled="form.processing"
                >
                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                    <span>{{ form.processing ? 'Criando...' : 'Criar Organização' }}</span>
                </button>
            </div>
        </form>
    </Offcanvas>
</template>
