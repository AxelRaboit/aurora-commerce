<script setup>
import { ref } from "vue";

const props = defineProps({
    accept: { type: String, default: "" },
});

const emit = defineEmits(["change"]);

const inputRef = ref(null);

function trigger() {
    inputRef.value?.click();
}

function onChange(event) {
    const file = event.target.files?.[0];
    event.target.value = "";
    if (file) emit("change", file);
}
</script>

<template>
    <input
        ref="inputRef"
        type="file"
        :accept="accept"
        class="hidden"
        v-on:change="onChange"
    >
    <slot :trigger="trigger" />
</template>
