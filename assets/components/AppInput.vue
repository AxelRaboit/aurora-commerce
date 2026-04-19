<script setup>
import { ref, computed } from "vue";
import { Eye, EyeOff } from "lucide-vue-next";

const props = defineProps({
    modelValue: { type: String, default: '' },
    type: { type: String, default: 'text' },
    name: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    label: { type: String, default: '' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    toggleable: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const revealed = ref(false);
const inputType = computed(() => {
    if (props.toggleable) return revealed.value ? 'text' : 'password';
    return props.type;
});
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" class="block text-xs text-secondary uppercase tracking-wide">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        <div :class="toggleable ? 'relative' : ''">
            <input
                :type="inputType"
                :name="name || undefined"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary placeholder-muted focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                :class="[{ 'border-red-500 focus:border-red-500 focus:ring-red-500': error }, toggleable ? 'pr-10' : '']"
                v-on:input="$emit('update:modelValue', $event.target.value)"
            >
            <button
                v-if="toggleable"
                type="button"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-secondary transition"
                v-on:click="revealed = !revealed"
            >
                <Eye v-if="!revealed" class="w-4 h-4" :stroke-width="2" />
                <EyeOff v-else class="w-4 h-4" :stroke-width="2" />
            </button>
        </div>
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>
