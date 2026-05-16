<script setup>
import { ref } from "vue";

const props = defineProps({
    accept: { type: String, default: "" },
    multiple: { type: Boolean, default: false },
});

const emit = defineEmits(["change"]);

const inputRef = ref(null);

function trigger() {
    inputRef.value?.click();
}

function onChange(event) {
    const files = Array.from(event.target.files ?? []);
    event.target.value = "";
    if (!files.length) return;
    emit("change", props.multiple ? files : files[0]);
}

defineExpose({ trigger });
</script>

<template>
    <input
        ref="inputRef"
        type="file"
        :accept="accept"
        :multiple="multiple"
        class="hidden"
        v-on:change="onChange"
    >
    <slot :trigger="trigger" />
</template>
