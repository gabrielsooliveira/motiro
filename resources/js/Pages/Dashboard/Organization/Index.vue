<script setup>
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import CreateOrganizationOffCanvas from "@/js/Components/Offcanvas/Organization/CreateOrganizationOffCanvas.vue";

// Lista mockada — você pode trocar pela API
const projects = ref([
    {
        id: 1,
        title: "Material UI XD Version",
        description: "A modern UI kit built for easy prototyping.",
        progress: 60,
        image: "https://images.unsplash.com/photo-1522075469751-3a6694fb2f61",
        members: [
            "https://i.pravatar.cc/40?img=1",
            "https://i.pravatar.cc/40?img=2",
            "https://i.pravatar.cc/40?img=3",
        ],
    },
]);
</script>
<template>
    <Head>
        <title>Organizações</title>
    </Head>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold m-0 p-0">Organizaçõs</h3>
                <p>Organizações que você participa</p>
            </div>

            <CreateOrganizationOffCanvas />
        </div>

        <div class="row g-4">
            <div
                v-for="project in projects"
                :key="project.id"
                class="col-xl-4 col-lg-6 col-md-6 col-sm-12"
            >
                <div class="card shadow-sm border-0 h-100">
                    <img
                        :src="project.image"
                        class="card-img-top"
                        style="height: 180px; object-fit: cover"
                    />

                    <div class="card-body">
                        <div
                            class="d-flex justify-content-between align-items-start"
                        >
                            <h5 class="fw-bold mb-0 pb-0">
                                {{ project.title }}
                            </h5>

                            <button class="btn btn-sm btn-light border">
                                <font-awesome-icon
                                    icon="fa-solid fa-ellipsis"
                                ></font-awesome-icon>
                            </button>
                        </div>

                        <p class="text-muted small mt-0">
                            {{ project.description }}
                        </p>

                        <div class="d-flex mb-3">
                            <div
                                v-for="(img, i) in project.members"
                                :key="i"
                                class="me-2"
                            >
                                <img
                                    :src="img"
                                    class="rounded-circle border"
                                    width="34"
                                    height="34"
                                    style="object-fit: cover"
                                />
                            </div>
                        </div>

                        <button class="btn btn-outline-primary w-100">
                            Ver Organização
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
