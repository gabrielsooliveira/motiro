<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    totalCount: {
        type: Number,
        default: 0,
    },
});

const search = ref(props.filters.search || "");
const modality = ref(props.filters.modality || "");
const status = ref(props.filters.status || "");
const registrationType = ref(props.filters.registration_type || "");
const myStatus = ref(props.filters.my_status || "");
const sort = ref(props.filters.sort || "latest");

let debounceTimeout = null;

function applyFilters() {
    const params = {};

    if (search.value.trim()) params.search = search.value.trim();
    if (modality.value) params.modality = modality.value;
    if (status.value) params.status = status.value;
    if (registrationType.value) params.registration_type = registrationType.value;
    if (myStatus.value) params.my_status = myStatus.value;
    if (sort.value && sort.value !== "latest") params.sort = sort.value;

    router.get(route("participant.hackathons"), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function onSearchInput() {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
}

function clearFilters() {
    search.value = "";
    modality.value = "";
    status.value = "";
    registrationType.value = "";
    myStatus.value = "";
    sort.value = "latest";
    applyFilters();
}

const hasActiveFilters = () => {
    return Boolean(
        search.value ||
        modality.value ||
        status.value ||
        registrationType.value ||
        myStatus.value ||
        (sort.value && sort.value !== "latest")
    );
};
</script>

<template>
    <div class="discovery-filters-bar">
        <form @submit.prevent="applyFilters">
            <!-- Row 1: Search and Primary Actions -->
            <div class="row g-3 align-items-center mb-3">
                <div class="col-12 col-md-7 col-lg-8">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <font-awesome-icon :icon="['fas', 'magnifying-glass']" class="text-muted" />
                        </span>
                        <input
                            v-model="search"
                            type="search"
                            class="form-control border-start-0 ps-0"
                            placeholder="Buscar por título, categoria, descrição ou organização..."
                            @input="onSearchInput"
                        />
                    </div>
                </div>

                <div class="col-12 col-md-5 col-lg-4 d-flex justify-content-md-end align-items-center gap-2">
                    <button
                        v-if="hasActiveFilters()"
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        @click="clearFilters"
                    >
                        <font-awesome-icon :icon="['fas', 'xmark']" class="me-1" />
                        Limpar filtros
                    </button>

                    <div class="text-muted small">
                        <strong>{{ totalCount }}</strong> {{ totalCount === 1 ? 'hackathon' : 'hackathons' }}
                    </div>
                </div>
            </div>

            <!-- Row 2: Secondary Filters & Sorting -->
            <div class="row g-2 align-items-center">
                <!-- Status Filter -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select
                        v-model="status"
                        class="form-select form-select-sm"
                        @change="applyFilters"
                    >
                        <option value="">Todos os status</option>
                        <option value="open">Inscrições Abertas</option>
                        <option value="upcoming">Em Breve</option>
                        <option value="in_progress">Em Andamento</option>
                        <option value="finished">Encerrados</option>
                    </select>
                </div>

                <!-- Modality Filter -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Modalidade</label>
                    <select
                        v-model="modality"
                        class="form-select form-select-sm"
                        @change="applyFilters"
                    >
                        <option value="">Todas modalidades</option>
                        <option value="online">Online</option>
                        <option value="presential">Presencial</option>
                        <option value="hybrid">Híbrido</option>
                    </select>
                </div>

                <!-- Registration Type Filter -->
                <div class="col-6 col-md-3 col-lg-2">
                    <label class="form-label small text-muted mb-1">Participação</label>
                    <select
                        v-model="registrationType"
                        class="form-select form-select-sm"
                        @change="applyFilters"
                    >
                        <option value="">Individual e Equipe</option>
                        <option value="individual">Aceita Individual</option>
                        <option value="team_only">Apenas Equipe</option>
                    </select>
                </div>

                <!-- My Registrations Filter -->
                <div class="col-6 col-md-3 col-lg-3">
                    <label class="form-label small text-muted mb-1">Minhas inscrições</label>
                    <select
                        v-model="myStatus"
                        class="form-select form-select-sm"
                        @change="applyFilters"
                    >
                        <option value="">Todos os eventos</option>
                        <option value="registered">Apenas os que estou inscrito</option>
                        <option value="not_registered">Não inscritos</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="col-12 col-md-12 col-lg-3">
                    <label class="form-label small text-muted mb-1">Ordenar por</label>
                    <select
                        v-model="sort"
                        class="form-select form-select-sm"
                        @change="applyFilters"
                    >
                        <option value="latest">Mais recentes</option>
                        <option value="deadline">Prazo de inscrição</option>
                        <option value="title">Título (A-Z)</option>
                        <option value="oldest">Mais antigos</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
</template>
