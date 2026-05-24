<script setup>
import { ref } from "vue";
import AppButton from "@/shared/components/action/AppButton.vue";

defineOptions({ inheritAttrs: false });

defineProps({
    accept: { type: String, default: "" },
    multiple: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    variant: { type: String, default: "primary" },
    size: { type: String, default: "md" },
});

const emit = defineEmits(["change", "files"]);

const inputRef = ref(null);

function onChange(event) {
    emit("change", event);
    emit("files", event.target.files);
}

defineExpose({
    open: () => inputRef.value?.click(),
    reset: () => { if (inputRef.value) inputRef.value.value = ""; },
});
</script>

<template>
    <span class="contents">
        <input
            ref="inputRef"
            type="file"
            :accept="accept"
            :multiple="multiple"
            class="hidden"
            v-on:change="onChange"
        >
        <AppButton
            :variant="variant"
            :size="size"
            :loading="loading"
            :disabled="disabled"
            v-bind="$attrs"
            v-on:click="inputRef?.click()"
        >
            <slot />
        </AppButton>
    </span>
</template>
