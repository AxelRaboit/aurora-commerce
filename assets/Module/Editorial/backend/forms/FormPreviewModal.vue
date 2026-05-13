<script setup>
import { computed, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { FormFieldType } from "@editorial/shared/enums/formFieldType.js";
import { ChevronLeft, ChevronRight, Eye, X } from "lucide-vue-next";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppButton from "@/shared/components/action/AppButton.vue";

const { t } = useI18n();

const props = defineProps({
    show: { type: Boolean, default: false },
    fields: { type: Array, default: () => [] },
    steps: { type: Array, default: () => [] },
    formTitle: { type: String, default: "" },
    formDescription: { type: String, default: "" },
    activeLocale: { type: String, default: "fr" },
    defaultLocale: { type: String, default: "fr" },
});

const emit = defineEmits(["close"]);

// Convert backend-format fields (with translations{}) to frontend-format (label, options…)
const previewFields = computed(() =>
    props.fields.map((field) => {
        const trans =
            field.translations?.[props.activeLocale] ??
            field.translations?.[props.defaultLocale] ??
            Object.values(field.translations ?? {})[0] ??
            {};
        return {
            id: field.id,
            type: field.type,
            required: field.required,
            step: field.step ?? null,
            conditions: field.conditions ?? [],
            conditionsLogic: field.conditionsLogic ?? "and",
            label: trans.label ?? "",
            placeholder: trans.placeholder ?? null,
            options: trans.options ?? [],
        };
    }),
);

// Resolve step labels for the current locale
const previewSteps = computed(() =>
    (props.steps ?? []).map(
        (step) =>
            step[props.activeLocale] ??
            step[props.defaultLocale] ??
            Object.values(step)[0] ??
            "",
    ),
);

const isMultiStep = computed(() => previewSteps.value.length > 0);
const totalSteps = computed(() => previewSteps.value.length || 1);
const currentStep = ref(0);
const submitted = ref(false);
const formData = reactive({});
const errors = reactive({});

function resetPreview() {
    currentStep.value = 0;
    submitted.value = false;
    Object.keys(errors).forEach((k) => delete errors[k]);
    for (const field of previewFields.value) {
        formData[field.id] = field.type === FormFieldType.Checkbox ? [] : "";
    }
}

function onClose() {
    resetPreview();
    emit("close");
}

// Conditional logic
function evaluateCondition(condition) {
    const value = formData[condition.fieldId];
    const str = Array.isArray(value) ? value.join(",") : String(value ?? "");
    switch (condition.operator) {
        case "eq":        return str === String(condition.value ?? "");
        case "neq":       return str !== String(condition.value ?? "");
        case "contains":  return str.includes(String(condition.value ?? ""));
        case "not_empty": return str.trim() !== "";
        case "empty":     return str.trim() === "";
        default:          return true;
    }
}

function isFieldVisible(field) {
    if (!field.conditions?.length) return true;
    const results = field.conditions.map(evaluateCondition);
    return field.conditionsLogic === "or"
        ? results.some(Boolean)
        : results.every(Boolean);
}

const visibleFields = computed(() => previewFields.value.filter(isFieldVisible));

function fieldsForStep(stepIndex) {
    return visibleFields.value.filter((f) => (f.step ?? null) === stepIndex);
}

const currentStepFields = computed(() =>
    isMultiStep.value ? fieldsForStep(currentStep.value) : visibleFields.value,
);

function isLastStep() {
    return !isMultiStep.value || currentStep.value === totalSteps.value - 1;
}

function validateStep(fields) {
    let valid = true;
    for (const field of fields) {
        const value = formData[field.id];
        const isEmpty = Array.isArray(value) ? value.length === 0 : String(value ?? "").trim() === "";
        if (field.required && isEmpty) {
            errors[field.id] = t("shared.form.fieldRequired");
            valid = false;
        }
    }
    return valid;
}

function nextStep() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    if (!validateStep(currentStepFields.value)) return;
    if (currentStep.value < totalSteps.value - 1) currentStep.value++;
}

function prevStep() {
    if (currentStep.value > 0) currentStep.value--;
}

function handleSubmit() {
    Object.keys(errors).forEach((k) => delete errors[k]);
    if (!validateStep(currentStepFields.value)) return;
    submitted.value = true;
}

function isChecked(fieldId, option) {
    return Array.isArray(formData[fieldId]) && formData[fieldId].includes(option);
}

function toggleCheckbox(fieldId, option) {
    if (!Array.isArray(formData[fieldId])) formData[fieldId] = [];
    const index = formData[fieldId].indexOf(option);
    if (index === -1) {
        formData[fieldId].push(option);
    } else {
        formData[fieldId].splice(index, 1);
    }
}
</script>

<template>
    <AppModal
        :show="show"
        max-width="lg"
        :title="t('backend.forms.previewTitle')"
        :icon="Eye"
        :closeable="false"
        v-on:close="onClose"
    >
        <div class="space-y-5 min-h-64">
            <div v-if="submitted" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-emerald-400 text-sm font-medium text-center">
                <p>{{ t('backend.forms.previewSubmitSuccess') }}</p>
                <button class="mt-3 text-xs underline opacity-70 hover:opacity-100" v-on:click="resetPreview">
                    {{ t('backend.forms.previewReset') }}
                </button>
            </div>

            <template v-else>
                <div v-if="formTitle" class="space-y-1 pb-1 border-b border-line/40">
                    <h2 class="text-base font-semibold text-primary">{{ formTitle }}</h2>
                    <p v-if="formDescription" class="text-sm text-secondary">{{ formDescription }}</p>
                </div>

                <!-- Multi-step progress -->
                <div v-if="isMultiStep" class="space-y-2">
                    <div class="flex items-center justify-between text-xs text-muted">
                        <span class="font-medium text-primary">{{ previewSteps[currentStep] }}</span>
                        <span>{{ currentStep + 1 }} / {{ totalSteps }}</span>
                    </div>
                    <div class="h-1.5 bg-surface-2 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-accent-500 rounded-full transition-all duration-300"
                            :style="{ width: `${((currentStep + 1) / totalSteps) * 100}%` }"
                        />
                    </div>
                </div>

                <div v-if="currentStepFields.length === 0" class="py-8 text-center text-sm text-muted">
                    {{ t('backend.forms.previewNoFields') }}
                </div>

                <form v-else class="space-y-4" v-on:submit.prevent="isLastStep() ? handleSubmit() : nextStep()">
                    <div v-for="field in currentStepFields" :key="field.id" class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-primary">
                            {{ field.label || '—' }}
                            <span v-if="field.required" class="text-rose-400 ml-0.5">*</span>
                        </label>

                        <textarea
                            v-if="field.type === 'textarea'"
                            v-model="formData[field.id]"
                            rows="3"
                            class="w-full px-3 py-2 rounded-lg border text-sm text-primary bg-surface-2 focus:outline-none focus:ring-1 focus:ring-accent-500 resize-none"
                            :class="errors[field.id] ? 'border-rose-400' : 'border-line/60'"
                            :placeholder="field.placeholder ?? ''"
                        />

                        <select
                            v-else-if="field.type === 'select'"
                            v-model="formData[field.id]"
                            class="w-full px-3 py-2 rounded-lg border text-sm text-primary bg-surface-2 focus:outline-none focus:ring-1 focus:ring-accent-500"
                            :class="errors[field.id] ? 'border-rose-400' : 'border-line/60'"
                        >
                            <option value="">{{ t('shared.form.selectPlaceholder') }}</option>
                            <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                        </select>

                        <div v-else-if="field.type === 'radio'" class="space-y-1.5">
                            <label
                                v-for="opt in field.options"
                                :key="opt"
                                class="flex items-center gap-2 text-sm text-primary cursor-pointer"
                            >
                                <input
                                    v-model="formData[field.id]"
                                    type="radio"
                                    :name="`preview-${field.id}`"
                                    :value="opt"
                                    class="text-accent-600 focus:ring-accent-500"
                                >
                                {{ opt }}
                            </label>
                        </div>

                        <div v-else-if="field.type === 'checkbox'" class="space-y-1.5">
                            <label
                                v-for="opt in field.options"
                                :key="opt"
                                class="flex items-center gap-2 text-sm text-primary cursor-pointer"
                            >
                                <input
                                    type="checkbox"
                                    :value="opt"
                                    :checked="isChecked(field.id, opt)"
                                    class="rounded text-accent-600 focus:ring-accent-500"
                                    v-on:change="toggleCheckbox(field.id, opt)"
                                >
                                {{ opt }}
                            </label>
                        </div>

                        <input
                            v-else
                            v-model="formData[field.id]"
                            :type="field.type"
                            class="w-full px-3 py-2 rounded-lg border text-sm text-primary bg-surface-2 focus:outline-none focus:ring-1 focus:ring-accent-500"
                            :class="errors[field.id] ? 'border-rose-400' : 'border-line/60'"
                            :placeholder="field.placeholder ?? ''"
                        >

                        <p v-if="errors[field.id]" class="text-xs text-rose-400">{{ errors[field.id] }}</p>
                    </div>

                    <div class="flex items-center gap-3 pt-1" :class="isMultiStep && currentStep > 0 ? 'justify-between' : 'justify-end'">
                        <button
                            v-if="isMultiStep && currentStep > 0"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-medium text-secondary border border-line hover:bg-surface-2 transition-colors"
                            v-on:click="prevStep"
                        >
                            <ChevronLeft class="w-4 h-4" :stroke-width="2" />
                            {{ t('shared.form.prev') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-lg text-sm font-medium bg-accent-600 text-white hover:bg-accent-700 transition-colors"
                        >
                            <template v-if="!isLastStep()">
                                {{ t('shared.form.next') }}
                                <ChevronRight class="w-4 h-4" :stroke-width="2" />
                            </template>
                            <template v-else>{{ t('shared.form.submit') }}</template>
                        </button>
                    </div>
                </form>
            </template>
        </div>

        <template #footer>
            <AppModalFooter>
                <p class="text-xs text-muted mr-auto">{{ t('backend.forms.previewHint') }}</p>
                <AppButton variant="ghost" size="md" v-on:click="onClose">
                    <X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.close') }}
                </AppButton>
            </AppModalFooter>
        </template>
    </AppModal>
</template>
