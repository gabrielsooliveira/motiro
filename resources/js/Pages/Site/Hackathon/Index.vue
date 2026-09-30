<script setup>
import { Head, Link } from "@inertiajs/vue3";

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

const getStatusBadge = (status) => {
    const map = {
        published: "bg-success",
        registration: "bg-primary",
        submission: "bg-warning text-dark",
        voting: "bg-info text-dark",
        finished: "bg-secondary",
        draft: "bg-light text-dark border",
    };
    return map[status] || "bg-secondary";
};
</script>

<template>
    <Head>
        <title>Explorar Hackathons</title>
    </Head>

    <div class="container py-4">
        <!-- Header -->
        <div class="row align-items-center gy-2">
            <div class="col-12 col-md-6 col-lg-5">
                <h1 class="fw-bold mb-1">Explorar Hackathons</h1>
                <p class="text-muted mb-0">Participe de desafios, colabore em equipe e crie projetos de impacto.</p>
            </div>
            <div class="col-12 col-md-6 col-lg-7">
                <form @submit.prevent="handleSearch" class="px-md-3">
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

        <!-- Hackathon List Grid -->
        <div v-if="!hackathons.data || hackathons.data.length === 0" class="empty-state my-5">
            <div class="empty-state-icon">
                <font-awesome-icon :icon="['fas', 'folder-open']" />
            </div>
            <h5>Nenhum Hackathon Encontrado</h5>
            <p>Não há hackathons disponíveis com os filtros selecionados ou ainda não foram criados.</p>
        </div>

        <div v-else class="row g-4 mt-3">
            <div v-for="hack in hackathons.data" :key="hack.id" class="col-md-6 col-lg-4">
                <div class="hackathon-card card h-100 shadow-sm border-0">
                    <div class="position-relative">
                        <img
                            :src="hack.banner_url || 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=800&auto=format&fit=crop&q=60'"
                            class="card-img-top object-fit-cover"
                            alt="Banner"
                            height="160"
                        />
                        <span class="position-absolute top-0 end-0 mt-3 me-3 badge" :class="getStatusBadge(hack.status)">
                            {{ hack.status }}
                        </span>
                    </div>

                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-light text-dark border text-capitalize">{{ hack.modality }}</span>
                            <span v-if="hack.category" class="badge bg-primary bg-opacity-10 text-primary">{{ hack.category }}</span>
                        </div>

                        <h5 class="card-title fw-bold text-truncate mb-1">
                            <Link :href="route('hackathon.show', hack.slug)" class="text-decoration-none text-dark">
                                {{ hack.title }}
                            </Link>
                        </h5>

                        <p class="card-text text-muted small text-truncate-2 mb-3">
                            {{ hack.description || 'Desafio colaborativo sem descrição informada.' }}
                        </p>

                        <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <font-awesome-icon :icon="['fas', 'users']" class="me-1" />
                                {{ hack.registrations_count ?? 0 }} equipes
                            </small>

                            <Link :href="route('hackathon.show', hack.slug)" class="btn btn-sm btn-primary">
                                Ver Detalhes &rarr;
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
