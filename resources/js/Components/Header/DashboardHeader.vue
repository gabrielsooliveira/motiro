<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
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
    descriptionClass: {
        type: String,
        default: "text-muted small mb-0",
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
    backIcon: {
        type: [String, Array],
        default: () => ["fas", "arrow-left"],
    },
    borderBottom: {
        type: Boolean,
        default: true,
    },
    teleport: {
        type: [Boolean, String],
        default: false,
    },
});

const effectiveDescription = computed(() => props.description || props.subtitle);

const effectiveBadgeClass = computed(() => {
    if (props.badgeClass) return props.badgeClass;
    if (props.badgeVariant === "primary-subtle" || props.badgeVariant === "primary") {
        return "badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small";
    }
    return `badge bg-${props.badgeVariant} rounded-pill px-3 py-1 small`;
});

const resolvedBackUrl = computed(() => {
    if (props.backUrl) return props.backUrl;
    if (props.backRoute) {
        try {
            if (typeof route === "function") {
                return route(props.backRoute);
            }
        } catch {
            return props.backRoute;
        }
        return props.backRoute;
    }
    return null;
});

const isTeleportEnabled = computed(() => Boolean(props.teleport));

const effectiveTeleportTarget = computed(() => {
    if (!isTeleportEnabled.value) return "body";
    if (typeof props.teleport === "string") return props.teleport;
    return "#dashboard-header-portal";
});
</script>

<template>
    <Teleport :to="effectiveTeleportTarget" :disabled="!isTeleportEnabled">
        <div
            class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 mb-4 pb-2"
            :class="{ 'border-bottom': borderBottom }"
        >
            <div>
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <slot name="title">
                        <h1 v-if="title" :class="titleClass">{{ title }}</h1>
                    </slot>

                    <slot name="badge">
                        <span v-if="badge" :class="effectiveBadgeClass">
                            {{ badge }}
                        </span>
                    </slot>

                    <slot name="title-extra" />
                </div>

                <slot name="description">
                    <p v-if="effectiveDescription" :class="descriptionClass">
                        {{ effectiveDescription }}
                    </p>
                </slot>
            </div>

            <div
                v-if="$slots.actions || resolvedBackUrl || $slots.default"
                class="d-flex gap-2 align-items-center flex-wrap"
            >
                <Link
                    v-if="resolvedBackUrl"
                    :href="resolvedBackUrl"
                    class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-3 rounded-pill"
                >
                    <font-awesome-icon :icon="backIcon" />
                    <span>{{ backLabel }}</span>
                </Link>

                <slot name="actions">
                    <slot />
                </slot>
            </div>
        </div>
    </Teleport>
</template>
