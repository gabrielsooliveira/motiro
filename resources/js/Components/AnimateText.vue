<script setup>
import { ref, onMounted, onBeforeUnmount } from "vue";

const props = defineProps({
    words: Array,
    color: String || "text-primary",
});

const index = ref(0);
const currentWord = ref(props.words[index.value]);
let timer = null;

onMounted(() => {
    timer = setInterval(() => {
        index.value = (index.value + 1) % props.words.length;
        currentWord.value = props.words[index.value];
    }, 5000);
});

onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <transition name="fade" mode="out-in" appear>
        <span :key="currentWord" class="swap-word" :class="'text-' + color">{{
            currentWord
        }}</span>
    </transition>
</template>
