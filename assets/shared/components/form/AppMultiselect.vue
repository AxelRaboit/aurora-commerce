<script setup>
import { computed } from "vue";
import Multiselect from "vue-multiselect";
import { useI18n } from "vue-i18n";
import AppFieldLabel from "@/shared/components/form/AppFieldLabel.vue";

const { t } = useI18n();

const props = defineProps({
    modelValue: { type: [String, Number, Array, Object, null], default: null },
    options: { type: Array, default: () => [] },
    label: { type: String, default: "" },
    placeholder: { type: String, default: "" },
    error: { type: String, default: "" },
    required: { type: Boolean, default: false },
    multiple: { type: Boolean, default: false },
    searchable: { type: Boolean, default: true },
    allowEmpty: { type: Boolean, default: false },
    trackBy: { type: String, default: "value" },
    optionLabel: { type: String, default: "label" },
    openDirection: { type: String, default: "bottom" },
});

const emit = defineEmits(["update:modelValue"]);

const selectedOption = computed(() => {
    if (props.multiple) {
        if (!Array.isArray(props.modelValue)) return [];
        return props.options.filter((opt) => props.modelValue.includes(opt[props.trackBy]));
    }
    return props.options.find((opt) => opt[props.trackBy] === props.modelValue) ?? null;
});

function onSelect(value) {
    if (props.multiple) {
        emit("update:modelValue", (value ?? []).map((opt) => opt[props.trackBy]));
        return;
    }
    emit("update:modelValue", value ? value[props.trackBy] : null);
}
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <AppFieldLabel :label="label" :required="required" />
        <Multiselect
            :model-value="selectedOption"
            :options="options"
            :label="optionLabel"
            :track-by="trackBy"
            :multiple="multiple"
            :searchable="searchable"
            :allow-empty="allowEmpty"
            :open-direction="openDirection"
            :placeholder="placeholder || t('shared.common.select_placeholder')"
            select-label=""
            selected-label=""
            deselect-label=""
            :class="{ 'multiselect--error': error }"
            v-on:update:model-value="onSelect"
        >
            <template #noOptions>{{ t('shared.common.no_options') }}</template>
            <template #noResult>{{ t('shared.common.no_result') }}</template>
        </Multiselect>
        <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
    </div>
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style scoped>
:deep(.multiselect__tags) {
    background-color: var(--color-surface);
    border-color: var(--color-line);
    color: var(--color-primary);
    border-radius: 0.375rem;
    min-height: 38px;
    padding: 6px 40px 0 8px;
    font-size: 0.875rem;
}
:deep(.multiselect__single),
:deep(.multiselect__input) {
    background-color: var(--color-surface);
    color: var(--color-primary);
    font-size: 0.875rem;
    margin-bottom: 4px;
    padding: 0 0 0 4px;
}
:deep(.multiselect__placeholder) {
    color: var(--color-muted);
    font-size: 0.875rem;
    margin-bottom: 4px;
    padding: 0 0 0 4px;
}
:deep(.multiselect__content-wrapper) {
    background-color: var(--color-surface);
    border-color: var(--color-line);
}
:deep(.multiselect__option) {
    color: var(--color-primary);
    font-size: 0.875rem;
    background-color: var(--color-surface);
}
:deep(.multiselect__option--highlight) {
    background: #4f46e5;
    color: #fff;
}
:deep(.multiselect__option--selected) {
    background: var(--color-surface-2);
    color: var(--color-primary);
    font-weight: 600;
}
:deep(.multiselect__option--selected.multiselect__option--highlight) {
    background: #4338ca;
    color: #fff;
}
:deep(.multiselect__tag) {
    background: #4f46e5;
}
:deep(.multiselect--active .multiselect__tags) {
    border-color: #6366f1;
    box-shadow: 0 0 0 1px #6366f1;
}
:deep(.multiselect--error .multiselect__tags) {
    border-color: rgb(239 68 68);
}
</style>
