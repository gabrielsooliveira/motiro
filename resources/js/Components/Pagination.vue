<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    links: {
        type: Array,
        required: true,
    },
});

const sanitizeLabel = (label) => {
    if (!label) return "";
    return label
        .replace("&laquo; Anterior", "Anterior")
        .replace("Próximo &raquo;", "Próximo")
        .replace("&laquo;", "«")
        .replace("&raquo;", "»");
};
</script>

<template>
    <nav v-if="links && links.length > 3" aria-label="Navegação de páginas" class="pagination-container">
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="pagination-link is-disabled"
                aria-disabled="true"
                v-html="sanitizeLabel(link.label)"
            ></span>

            <Link
                v-else
                :href="link.url"
                class="pagination-link"
                :class="{ 'is-active': link.active }"
                :aria-current="link.active ? 'page' : undefined"
                preserve-scroll
                preserve-state
                v-html="sanitizeLabel(link.label)"
            />
        </template>
    </nav>
</template>
