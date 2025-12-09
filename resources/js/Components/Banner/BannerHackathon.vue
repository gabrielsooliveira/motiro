<script setup>
import TabsPills from "@/js/Components/Tabs/TabsPills.vue";
import Banner from "@/js/Components/Banner/Banner.vue";

defineProps({
    item: {
        type: Object,
        required: true,
    },
});

const myTabs = [
    {
        id: "home",
        label: "Home",
        icon: "fa-solid fa-house",
        content: "Conteúdo da Home aqui...",
    },
    {
        id: "profile",
        label: "Perfil",
        icon: "fa-solid fa-user",
        content: "Informações do Perfil...",
    },
    {
        id: "advanced",
        label: "Avançado",
        icon: "fa-solid fa-gear",
        content: "Informações do Perfil...",
    },
    {
        id: "teste",
        label: "teste",
        icon: "fa-solid fa-gear",
        content: "Informações do Perfil...",
    },
    {
        id: "test",
        label: "test",
        icon: "fa-solid fa-gear",
        content: "Informações do Perfil...",
    },
    {
        id: "aa",
        label: "aa",
        icon: "fa-solid fa-gear",
        content: "Informações do Perfil...",
    },
];
</script>

<template>
    <Banner :item="item">
        <TabsPills :tabs="myTabs" activeTab="home" />
    </Banner>
</template>
