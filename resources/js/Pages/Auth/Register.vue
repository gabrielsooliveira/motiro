<script setup>
import { useForm, Head, Link } from "@inertiajs/vue3";
import { onMounted } from "vue";

const props = defineProps({
    tipos: Array,
});

const form = useForm({
    nome: "",
    email: "",
    senha: "",
    senha_confirmation: "",
    tipo_usuario_id: null,
});

onMounted(() => {
    if (props.tipos.length > 0) {
        form.tipo_usuario_id = props.tipos[0].id;
    }
});
</script>

<template>
    <Head title="Registro" />

    <!-- Cabeçalho -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Registre-se agora mesmo!</h2>
        <p class="text-muted small">Crie sua conta para continuar</p>
    </div>

    <!-- Formulário -->
    <form @submit.prevent="form.post(route('register_store'))">
        <!-- Nome -->
        <div class="mb-2">
            <label class="form-label fw-semibold" for="nome">Nome</label>
            <input
                id="name"
                type="text"
                v-model="form.nome"
                class="form-control"
                required
                autocomplete="nome"
            />
            <span v-if="form.errors.nome" class="text-danger small">{{
                form.errors.nome
            }}</span>
        </div>

        <!-- Email -->
        <div class="mb-2">
            <label class="form-label fw-semibold" for="email">Email</label>
            <input
                id="email"
                type="email"
                v-model="form.email"
                class="form-control"
                required
                autocomplete="email"
            />
            <span v-if="form.errors.email" class="text-danger small">{{
                form.errors.email
            }}</span>
        </div>

        <div class="mb-2">
            <label for="tipousuario" class="form-label fw-semibold"
                >Tipo de Usuário</label
            >
            <div>
                <div
                    class="form-check form-check-inline"
                    v-for="tipo in props.tipos"
                    :key="tipo.id"
                >
                    <input
                        class="form-check-input"
                        type="radio"
                        name="tipousuario"
                        :id="'tipo' + tipo.nome"
                        :value="tipo.id"
                        v-model="form.tipo_usuario_id"
                    />

                    <label
                        class="form-check-label text-capitalize"
                        :for="'tipo' + tipo.nome"
                    >
                        {{ tipo.nome }}
                    </label>
                </div>
            </div>
        </div>

        <!-- Senha -->
        <div class="mb-2">
            <label class="form-label fw-semibold" for="senha">Senha</label>
            <input
                id="senha"
                type="password"
                v-model="form.senha"
                class="form-control"
                required
            />
            <span v-if="form.errors.senha" class="text-danger small">{{
                form.errors.senha
            }}</span>
        </div>
        <!-- Confirmar Senha -->
        <div class="mb-2">
            <label class="form-label fw-semibold" for="senha_confirmation"
                >Confirme sua senha</label
            >
            <input
                id="senha_confirmation"
                type="password"
                v-model="form.senha_confirmation"
                class="form-control"
                required
            />
            <span
                v-if="form.errors.senha_confirmation"
                class="text-danger small"
            >
                {{ form.errors.senha_confirmation }}
            </span>
        </div>

        <!-- Termos -->
        <div class="form-check mb-3">
            <input
                type="checkbox"
                class="form-check-input"
                id="term"
                required
            />
            <label class="form-check-label small text-muted" for="term">
                Li e concordo com os
                <a href="#" target="_blank">Termos de Uso</a> e
                <a href="#" target="_blank">Política de Privacidade</a>
            </label>
        </div>

        <!-- Botão -->
        <div class="d-grid mb-3">
            <button
                type="submit"
                class="btn btn-primary fw-semibold py-2 text-white"
                :disabled="form.processing"
            >
                Registre-se
            </button>
        </div>
    </form>

    <!-- Separador -->
    <div class="d-flex align-items-center my-3">
        <hr class="flex-grow-1" />
        <span class="mx-2 text-muted small">ou registre-se com</span>
        <hr class="flex-grow-1" />
    </div>

    <!-- Redes Sociais -->
    <div class="d-flex justify-content-center gap-3 mb-3">
        <a
            href="/auth/google/redirect"
            class="btn btn-light border d-flex align-items-center gap-2 px-3 py-2 shadow-sm disabled"
        >
            <font-awesome-icon
                icon="fa-brands fa-google"
                class="text-danger fs-5"
            />
            <span class="d-none d-sm-inline fw-semibold text-dark">Google</span>
        </a>
    </div>

    <!-- Links extras -->
    <div class="text-center small">
        <Link :href="route('login')" class="text-decoration-none fw-semibold">
            Já tem uma conta?
            <span class="text-dark">Faça login.</span>
        </Link>
        <br />
        <Link :href="route('home')" class="text-decoration-none fw-semibold">
            <font-awesome-icon icon="fa-solid fa-door-open" class="me-1" />
            Voltar para a Home
        </Link>
    </div>
</template>
