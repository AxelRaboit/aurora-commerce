<script setup>
import { ref, watch } from "vue";
import { Search, X } from "lucide-vue-next";
import AppInput from "@/shared/components/form/AppInput.vue";
import { useDebounce } from "@/shared/composables/useDebounce.js";

const props = defineProps({
    modelValue: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    debounce: { type: Number, default: 300 },
    clearable: { type: Boolean, default: true },
});

const emit = defineEmits(["update:modelValue", "search"]);

const localValue = ref(props.modelValue);

watch(() => props.modelValue, (value) => { localValue.value = value; });

const emitSearch = useDebounce((value) => emit("search", value), props.debounce);

function onInput(value) {
    localValue.value = value;
    emit("update:modelValue", value);
    emitSearch(value);
}

function clear() {
    localValue.value = "";
    emit("update:modelValue", "");
    emit("search", "");
}

const inputRef = ref(null);
defineExpose({
    focus: () => inputRef.value?.focus(),
    select: () => inputRef.value?.select(),
    blur: () => inputRef.value?.blur(),
});
</script>

<template>
    <div class="relative">
        <AppInput
            ref="inputRef"
            :model-value="localValue"
            :placeholder="placeholder"
            v-on:update:model-value="onInput"
        >
            <template #prefix>
                <Search class="w-4 h-4" :stroke-width="2" />
            </template>
        </AppInput>
        <button
            v-if="clearable && localValue"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition"
            v-on:click="clear"
        >
            <X class="w-4 h-4" :stroke-width="2" />
        </button>
    </div>
</template>
