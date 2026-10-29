<script setup>
import Sidebar from "@/js/Components/Sidebar.vue";
import Toast from "@/js/Components/Toast.vue";
import DashboardHeader from "@/js/Components/Header/DashboardHeader.vue";

defineProps({
    title: {
        type: String,
        default: "",
    },
    titleClass: {
        type: String,
        default: "h2 fw-bold text-dark mb-0",
    },
    description: {
        type: String,
        default: "",
    },
    subtitle: {
        type: String,
        default: "",
    },
    badge: {
        type: String,
        default: "",
    },
    badgeClass: {
        type: String,
        default: "",
    },
    badgeVariant: {
        type: String,
        default: "primary-subtle",
    },
    backUrl: {
        type: String,
        default: "",
    },
    backRoute: {
        type: String,
        default: "",
    },
    backLabel: {
        type: String,
        default: "Voltar",
    },
});
</script>

<template>
    <div class="dashboard-wrapper">
        <Sidebar />
        <main class="dashboard-main py-5 px-4 p-lg-3">
            <slot name="header">
                <div id="dashboard-header-portal">
                    <DashboardHeader
                        v-if="title || $slots['header-actions'] || $slots['header-title'] || $slots['header-badge']"
                        :title="title"
                        :title-class="titleClass"
                        :description="description || subtitle"
                        :badge="badge"
                        :badge-class="badgeClass"
                        :badge-variant="badgeVariant"
                        :back-url="backUrl"
                        :back-route="backRoute"
                        :back-label="backLabel"
                    >
                        <template #title v-if="$slots['header-title']">
                            <slot name="header-title" />
                        </template>
                        <template #badge v-if="$slots['header-badge']">
                            <slot name="header-badge" />
                        </template>
                        <template #actions v-if="$slots['header-actions']">
                            <slot name="header-actions" />
                        </template>
                    </DashboardHeader>
                </div>
            </slot>

            <slot></slot>
            <Toast />
        </main>
    </div>
</template>
