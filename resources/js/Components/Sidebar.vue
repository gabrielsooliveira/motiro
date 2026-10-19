<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import OrganizationSidebar from "@/js/Components/Sidebar/OrganizationSidebar.vue";
import ParticipantSidebar from "@/js/Components/Sidebar/ParticipantSidebar.vue";

const props = defineProps({
    type: {
        type: String,
        default: "auto",
        validator: (value) => ["auto", "organization", "participant"].includes(value),
    },
});

const page = usePage();
const context = computed(() => page.props.context || {});
const isOrgContext = computed(() => context.value.type === "organization");

const effectiveType = computed(() => {
    if (props.type !== "auto") {
        return props.type;
    }
    return isOrgContext.value ? "organization" : "participant";
});
</script>

<template>
    <OrganizationSidebar v-if="effectiveType === 'organization'" />
    <ParticipantSidebar v-else />
</template>