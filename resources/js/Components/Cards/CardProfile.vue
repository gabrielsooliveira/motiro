<script setup>
defineProps({
    hackathon: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="card shadow-sm h-100 card-animated border-1 border-light">
        <div class="position-relative">
            <img
                :src="hackathon.banner"
                class="card-img-top object-fit-cover"
                alt="Banner"
            />
            <img
                :src="hackathon.logo"
                class="position-absolute top-50 start-0 rounded border object-fit-cover bg-white ms-2 mt-4"
                height="60"
                loading="lazy"
            />
            <div>
                <span
                    class="position-absolute top-0 end-0 mt-3 me-3 bg-dark rounded-pill py-1 px-3 text-white fw-semibold"
                >
                    {{ hackathon.etapa }}
                </span>
            </div>
        </div>

        <div class="card-body d-flex flex-column pt-5 bg-white">
            <h5 class="card-title text-primary fw-bold fs-3 text-truncate">
                {{ hackathon.title }}
            </h5>

            <p class="text-muted text-truncate small mb-2">
                {{ hackathon.description }}
            </p>

            <div class="d-flex flex-wrap gap-2 mb-3">
                <span
                    v-for="tag in hackathon.tags"
                    :key="tag"
                    class="badge bg-primary bg-opacity-10 text-primary fw-semibold"
                >
                    {{ tag }}
                </span>
            </div>

            <ul class="list-inline small text-muted">
                <li class="list-inline-item me-3">
                    <font-awesome-icon icon="fas fa-users" class="me-1" />
                    {{ hackathon.participants }}
                </li>

                <li class="list-inline-item me-3">
                    <font-awesome-icon icon="fas fa-folder-tree" class="me-1" />
                    {{ hackathon.projects }}
                </li>
            </ul>
        </div>
    </div>
</template>
