<script setup>
import { ref } from "vue";
import Offcanvas from "@/js/components/Offcanvas/Default.vue";

const open = ref(false);
</script>

<template>
    <button class="btn btn-primary btn-sm" @click="open = true">
        <font-awesome-icon icon="fas fa-plus" /> Quero criar um hackathon
    </button>

    <Offcanvas
        id="menu-create-organization"
        title="Solicitar criação de organização"
        placement="end"
        v-model="open"
    >
        <form @submit.prevent="form.post(route('organization.store'))">
            <div class="mb-3">
                <label for="name" class="form-label">Nome da Empresa</label>
                <input
                    type="text"
                    class="form-control"
                    id="name"
                    name="name"
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
                    placeholder="https://..."
                />
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Descrição</label>
                <textarea
                    class="form-control"
                    id="description"
                    name="description"
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
