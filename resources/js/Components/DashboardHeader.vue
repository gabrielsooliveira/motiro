<script setup>
import DashboardHeader from "@/js/Components/Header/DashboardHeader.vue";
</script>

<template>
    <DashboardHeader v-bind="$attrs">
        <template v-for="(_, name) in $slots" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps || {}" />
        </template>
    </DashboardHeader>
</template>
