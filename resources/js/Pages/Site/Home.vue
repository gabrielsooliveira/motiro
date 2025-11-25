<script setup>
import { Head } from "@inertiajs/vue3";
import HeroFeatures from "@/js/Components/Hero/HeroFeatures.vue";
import HeroImages from "@/js/Components/Hero/HeroImages.vue";
import AnimateText from "@/js/Components/AnimateText.vue";

const words = ["pessoas", "projetos", "equipes", "ideias"];
</script>

<template>
    <Head>
        <title>Plataforma Brasileira de Hackathons</title>
    </Head>

    <section class="container">
        <div class="row align-items-center">
            <div class="col-xxl-5 col-12 mt-5">
                <blockquote class="blockquote">
                    <span class="fw-semibold text-primary fs-6">
                        “Motirõ é a reunião de pessoas colhendo ou construindo
                        algo juntos, uns ajudando os outros.”
                        <cite class="blockquote-footer"
                            >Dicionário Tupi-Guarani</cite
                        >
                    </span>
                </blockquote>

                <h1 class="title-page mt-3 mb-5">
                    A plataforma de hackathons que une
                    <AnimateText :words="words" :color="'blue'" /> para mudar o
                    mundo
                </h1>

                <HeroFeatures />

                <div class="d-grid d-md-flex flex-row gap-2 mt-4">
                    <a href="#" class="btn btn-primary btn-sm">
                        Entrar como Participante
                    </a>
                    <a href="#" class="btn btn-secondary btn-sm">
                        Área da Organização
                    </a>
                </div>
            </div>

            <div class="col-xxl-7 col-12 mt-5 pt-5">
                <HeroImages />
            </div>
        </div>
    </section>
</template>
