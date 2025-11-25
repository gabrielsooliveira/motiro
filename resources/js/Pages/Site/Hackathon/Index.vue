<script setup>
import { Head, Link } from "@inertiajs/vue3";
import HackathonCard from "@/js/Components/Cards/CardProfile.vue";

const hackathons = [
    {
        id: 1,
        title: "Motirõ Social Impact Hackathon",
        description:
            "Crie soluções que transformam vidas através da tecnologia colaborativa.",
        banner: "https://dummyimage.com/600x200/139A74&text=+",
        logo: "https://dummyimage.com/200x200/0d6efd/ffffff&text=Logo",
        participants: 355,
        projects: 61,
        tags: ["Inovação", "Impacto Social", "IA", "Web", "Comunidade"],
        etapa: "Demoday",
    },
    {
        id: 2,
        title: "Hackathon Educação 4.0",
        description: "Soluções para o futuro da educação no Brasil.",
        banner: "https://dummyimage.com/600x200/f39c12&text=+",
        logo: "https://dummyimage.com/200x200/8e44ad/ffffff&text=Logo",
        participants: 220,
        projects: 48,
        tags: ["Educação", "Tecnologia", "Startups"],
        etapa: "Finalizado",
    },
    {
        id: 3,
        title: "Hackathon Educação 5.0",
        description: "Soluções para o futuro da educação no Brasil.",
        banner: "https://dummyimage.com/600x200/a1b5a8&text=+",
        logo: "https://dummyimage.com/200x200/bb2005/ffffff&text=Logo",
        participants: 220,
        projects: 48,
        tags: ["Educação", "Tecnologia", "Startups"],
        etapa: "Inscrição",
    },
    {
        id: 4,
        title: "Hackathon Educação 6.0",
        description: "Soluções para o futuro da educação no Brasil.",
        banner: "https://dummyimage.com/600x200/a1b5a8&text=+",
        logo: "https://dummyimage.com/200x200/bb2005/ffffff&text=Logo",
        participants: 220,
        projects: 48,
        tags: ["Educação", "Tecnologia", "Startups"],
        etapa: "Inscrição",
    },
];
</script>

<template>
    <Head>
        <title>Painel de hackathons</title>
    </Head>

    <div class="container mt-5">
        <div class="row">
            <div class="col-xxl-6 offset-xxl-3 col-md-8 offset-md-2 col-12">
                <div class="text-center mb-3">
                    <div class="d-flex flex-column gap-3">
                        <h1 class="mb-0 title-page">
                            Entre em algum dos
                            <span class="text-primary">hackathons</span>
                            que transformam o mundo
                        </h1>

                        <form class="form-inline px-xl-8 mx-xl-8">
                            <div class="d-flex align-items-center">
                                <label
                                    for="hackathonssearch"
                                    class="visually-hidden"
                                    >Work Email</label
                                >
                                <span class="position-absolute ps-3">
                                    <font-awesome-icon
                                        icon="fas fa-search"
                                    ></font-awesome-icon>
                                </span>
                                <input
                                    type="search"
                                    class="form-control ps-5 form-control-lg"
                                    id="hackathonssearch"
                                    name="hackathons"
                                    placeholder="Buscar hackathon"
                                />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="my-4">
        <hr />
    </div>

    <div class="container">
        <div class="row g-4">
            <div
                class="col-12 col-md-6 col-lg-4"
                v-for="hackathon in hackathons"
                :key="hackathon.id"
            >
                <Link
                    :href="route('hackathons.show', hackathon.id)"
                    class="text-decoration-none"
                >
                    <HackathonCard :hackathon="hackathon" />
                </Link>
            </div>
        </div>
    </div>
</template>
