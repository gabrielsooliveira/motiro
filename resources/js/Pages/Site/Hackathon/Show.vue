<script setup>
import { Link } from "@inertiajs/vue3";
import HackathonHeaderBanner from "@/js/Components/HackathonHeaderBanner.vue";

const hackathon = {
    banner: "https://dummyimage.com/1200x400/ccc/ffffff&text=+",
    logo: "https://dummyimage.com/200x200/abc555/ffffff&text=Logo",
    title: "Motirõ For Hackers",
    subtitle:
        "Crie soluções que transformam vidas através da tecnologia colaborativa.",
    tags: ["Impacto Social", "Web3", "Comunidade"],
};
</script>

<template>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center my-4">
            <div class="mt-3">
                <Link
                    class="text-decoration-none text-light fw-semibold"
                    :href="route('hackathon.index')"
                >
                    <font-awesome-icon
                        icon="fa-solid fa-circle-left"
                    ></font-awesome-icon>
                    Voltar
                </Link>
            </div>

            <div class="mt-3">
                <Link class="btn btn-primary btn-sm fw-semibold shadow-sm">
                    <font-awesome-icon
                        icon="fa-solid fa-user-plus"
                        class="me-2"
                    ></font-awesome-icon>
                    Inscreva-se
                </Link>
            </div>
        </div>

        <HackathonHeaderBanner :hackathon="hackathon" />
    </div>
</template>
