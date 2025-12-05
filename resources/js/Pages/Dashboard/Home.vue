<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import CardSimple from "@/js/Components/Cards/CardSimple.vue";
const { user } = usePage().props;

const items = [
    {
        title: "Hackathons Ativos",
        value: 4,
        type: "number",
        icon: ["fas", "chess-board"],
    },
    {
        title: "Atividades Pendentes",
        value: 2,
        type: "number",
        icon: ["fas", "scroll"],
    },
    {
        title: "Crescimento",
        value: 12,
        type: "percent",
        icon: ["fas", "arrow-up"],
    },
];
</script>

<template>
    <Head>
        <title>Dashboard | Inicio</title>
    </Head>

    <section class="pt-3">
        <h1 class="fw-semibold text-capitalize">Olá, {{ user.name }}</h1>
        <p>Aqui está o resumo das suas atividades.</p>

        <div class="row justify-content-between">
            <div class="col-lg-3" v-for="(item, index) in items" :key="index">
                <CardSimple
                    :title="item.title"
                    :value="item.value"
                    :type="item.type"
                    :icon="item.icon"
                />
            </div>
        </div>
    </section>
</template>
