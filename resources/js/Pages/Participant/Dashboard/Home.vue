<script setup>
import { Head, usePage } from "@inertiajs/vue3";

const page = usePage();
const { user } = page.props;
</script>

<template>
    <Head>
        <title>Área do Participante | Início</title>
    </Head>

    <section class="pt-2">      
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom">
            <div>
                <h1 class="h2 fw-bold text-capitalize text-dark mb-0">Olá, {{ user.name }}</h1>
                <p class="text-muted small mb-0 mt-1">Aqui está o resumo dos seus hackathons, guilds e projetos no Motirõ.</p>
            </div>
        </div>
    </section>
</template>
