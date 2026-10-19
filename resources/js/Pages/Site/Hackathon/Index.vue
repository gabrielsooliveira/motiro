<script setup>
import { Head, router } from "@inertiajs/vue3";
import HackathonDiscoveryCard from "@/js/Components/Hackathon/HackathonDiscoveryCard.vue";

defineProps({
    hackathons: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const onRegister = (hackathon) => {
    router.visit(route("hackathon.show", hackathon.slug));
};
</script>

<template>
    <Head>
        <title>Explorar Hackathons</title>
    </Head>

    <div class="container py-4">
        <div class="row align-items-center gy-3 mb-4">
            <div class="col-12 col-md-6 col-lg-5">
                <h1 class="fw-bold mb-1 h2">Explorar Hackathons</h1>
                <p class="text-muted mb-0 small">Participe de desafios, colabore em equipe e crie projetos de impacto.</p>
            </div>
            <div class="col-12 col-md-6 col-lg-7">
                <form @submit.prevent class="px-md-3">
                    <div class="input-group">
                        <input 
                            class="form-control py-2 px-3 fs-7 border-end-0 rounded-start-pill shadow-none" 
                            type="search" 
                            placeholder="Pesquisar Hackathons" 
                            aria-label="Pesquisar Hackathons"
                        >
                        <button class="btn btn-primary px-4 rounded-end-pill" type="submit" aria-label="Pesquisar Hackathons">
                            <font-awesome-icon :icon="['fas', 'magnifying-glass']" />
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="!hackathons.data || hackathons.data.length === 0" class="empty-state my-5 text-center py-5">
            <div class="empty-state-icon mb-3">
                <font-awesome-icon :icon="['fas', 'folder-open']" class="fs-1 text-muted opacity-50" />
            </div>
            <h5 class="fw-bold text-dark">Nenhum Hackathon Encontrado</h5>
            <p class="text-muted small">Não há hackathons disponíveis com os filtros selecionados ou ainda não foram criados.</p>
        </div>

        <div v-else class="row g-4 mt-2">
            <div v-for="hack in hackathons.data" :key="hack.id" class="col-12 col-md-6 col-lg-4">
                <HackathonDiscoveryCard :hackathon="hack" @register="onRegister" />
            </div>
        </div>
    </div>
</template>
