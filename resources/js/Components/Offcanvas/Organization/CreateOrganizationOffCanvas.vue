<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Offcanvas from "@/js/Components/Offcanvas/Default.vue";

const open = ref(false);

const form = useForm({
    name: "",
    email: "",
    password: "",
    website: "",
    description: "",
});

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
    <button class="btn btn-sm border-0" @click="open = true">
        <font-awesome-icon icon="fas fa-suitcase" class="fs-5" />
    </button>

    <Offcanvas
        id="menu-create-organization"
        title="Solicitar criação de organização"
        placement="end"
        v-model="open"
    >
        <form @submit.prevent="storeOrganization">
            <div class="mb-3">
                <label for="name" class="form-label">Nome da Empresa</label>
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
                <label for="email" class="form-label">E-mail Corporativo</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    v-model="form.email"
                    required
                />
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    v-model="form.password"
                    required
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
                ></textarea>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">
                    Fazer solicitação
                </button>
            </div>
        </form>
    </Offcanvas>
</template>
