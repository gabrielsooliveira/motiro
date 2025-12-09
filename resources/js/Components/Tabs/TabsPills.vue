<script setup>
import { ref } from "vue";

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
    activeTab: {
        type: String,
        default: null,
    },
});

const currentTab = ref(props.activeTab ?? props.tabs[0]?.id);
</script>

<template>
    <ul
        class="nav mb-3 w-100 d-flex flex-nowrap overflow-x-auto justify-content-md-end"
        role="tablist"
    >
        <li
            v-for="tab in props.tabs"
            :key="tab.id"
            class="nav-item"
            role="presentation"
        >
            <button
                class="nav-link fw-semibold text-nowrap"
                :class="currentTab === tab.id ? 'link-dark' : 'link-primary'"
                type="button"
                role="tab"
                :id="`tab-${tab.id}`"
                @click="currentTab = tab.id"
            >
                <font-awesome-icon :icon="tab.icon" />
                {{ tab.label }}
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div
            v-for="tab in props.tabs"
            :key="tab.id"
            class="tab-pane fade"
            :class="{
                'show active': currentTab === tab.id,
            }"
            role="tabpanel"
            :aria-labelledby="`tab-${tab.id}`"
        >
            <div v-if="typeof tab.content === 'string'">
                {{ tab.content }}
            </div>

            <component v-else :is="tab.content" />
        </div>
    </div>
</template>
