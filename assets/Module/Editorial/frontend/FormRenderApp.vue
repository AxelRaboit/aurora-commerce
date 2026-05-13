<script setup>
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { computed, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { FormFieldType } from "@editorial/shared/enums/formFieldType.js";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    submitPath: { type: String, required: true },
    formTitle: { type: String, default: "" },
    formDescription: { type: String, default: null },
    fields: { type: Array, default: () => [] },
    steps: { type: Array, default: () => [] },
});

const submitting = ref(false);
const submitted = ref(false);
const errors = reactive({});
const formData = reactive({});

for (const field of props.fields) {
    formData[field.id] = field.type === FormFieldType.Checkbox ? [] : "";
}

// ── Conditional logic ────────────────────────────────────────────────────────

function evaluateCondition(condition) {
    const value = formData[condition.fieldId];
    const strValue = Array.isArray(value) ? value.join(",") : String(value ?? "");
    switch (condition.operator) {
        case "eq":        return strValue === String(condition.value ?? "");
        case "neq":       return strValue !== String(condition.value ?? "");
        case "contains":  return strValue.includes(String(condition.value ?? ""));
        case "not_empty": return strValue.trim() !== "";
        case "empty":     return strValue.trim() === "";
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

const visibleFields = computed(() => props.fields.filter(isFieldVisible));

// ── Multi-step ───────────────────────────────────────────────────────────────

const isMultiStep = computed(() => props.steps?.length > 0);
const currentStep = ref(0);
const totalSteps = computed(() => props.steps?.length ?? 1);

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
    Object.keys(errors).forEach((key) => delete errors[key]);
    if (!validateStep(currentStepFields.value)) return;
    if (currentStep.value < totalSteps.value - 1) currentStep.value++;
}

function prevStep() {
    if (currentStep.value > 0) currentStep.value--;
}

// ── Submission ───────────────────────────────────────────────────────────────

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

async function handleSubmit() {
    Object.keys(errors).forEach((key) => delete errors[key]);
    if (!validateStep(currentStepFields.value)) return;
    submitting.value = true;

    const payload = {};
    for (const field of visibleFields.value) {
        payload[field.id] = formData[field.id];
    }

    try {
        const response = await fetch(props.submitPath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        const data = await response.json();
        if (data.success) {
            submitted.value = true;
        } else if (data.errors) {
            Object.assign(errors, data.errors);
            if (isMultiStep.value) {
                for (let s = 0; s < totalSteps.value; s++) {
                    if (fieldsForStep(s).some((f) => errors[f.id])) {
                        currentStep.value = s;
                        break;
                    }
                }
            }
        }
    } catch {
        errors["_global"] = t("shared.form.error");
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="max-w-2xl mx-auto space-y-6">
        <div v-if="submitted" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-emerald-400 text-sm font-medium">
            {{ t("shared.form.success") }}
        </div>

        <template v-else>
            <div v-if="formTitle || formDescription" class="space-y-1">
                <h1 v-if="formTitle" class="text-2xl font-bold text-primary">{{ formTitle }}</h1>
                <p v-if="formDescription" class="text-secondary text-sm">{{ formDescription }}</p>
            </div>

            <!-- Multi-step progress -->
            <div v-if="isMultiStep" class="space-y-2">
                <div class="flex items-center justify-between text-xs text-muted">
                    <span>{{ steps[currentStep] }}</span>
                    <span>{{ currentStep + 1 }} / {{ totalSteps }}</span>
                </div>
                <div class="h-1.5 bg-surface-2 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        style="background-color: var(--th-accent);"
                        :style="{ width: `${((currentStep + 1) / totalSteps) * 100}%` }"
                    />
                </div>
            </div>

            <p v-if="errors['_global']" class="text-sm text-rose-400">{{ errors["_global"] }}</p>

            <form class="space-y-5" v-on:submit.prevent="isLastStep() ? handleSubmit() : nextStep()">
                <div v-for="field in currentStepFields" :key="field.id" class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium text-primary">
                        {{ field.label }}
                        <span v-if="field.required" class="text-rose-400 ml-0.5">*</span>
                    </label>

                    <textarea
                        v-if="field.type === 'textarea'"
                        v-model="formData[field.id]"
                        rows="4"
                        class="w-full px-3 py-2 rounded-lg border text-sm text-primary bg-white dark:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-[--th-accent] resize-none"
                        :class="errors[field.id] ? 'border-rose-400' : 'border-line/60'"
                        :placeholder="field.placeholder ?? ''"
                    />

                    <select
                        v-else-if="field.type === 'select'"
                        v-model="formData[field.id]"
                        class="w-full px-3 py-2 rounded-lg border text-sm text-primary bg-white dark:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-[--th-accent]"
                        :class="errors[field.id] ? 'border-rose-400' : 'border-line/60'"
                    >
                        <option value="" disabled>{{ t("shared.form.selectPlaceholder") }}</option>
                        <option v-for="option in field.options" :key="option" :value="option">{{ option }}</option>
                    </select>

                    <div v-else-if="field.type === 'radio'" class="space-y-1.5">
                        <label
                            v-for="option in field.options"
                            :key="option"
                            class="flex items-center gap-2 text-sm text-primary cursor-pointer"
                        >
                            <input
                                v-model="formData[field.id]"
                                type="radio"
                                :name="`field-${field.id}`"
                                :value="option"
                                class="text-[--th-accent] focus:ring-[--th-accent]"
                            >
                            {{ option }}
                        </label>
                    </div>

                    <div v-else-if="field.type === 'checkbox'" class="space-y-1.5">
                        <label
                            v-for="option in field.options"
                            :key="option"
                            class="flex items-center gap-2 text-sm text-primary cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="option"
                                :checked="isChecked(field.id, option)"
                                class="rounded text-[--th-accent] focus:ring-[--th-accent]"
                                v-on:change="toggleCheckbox(field.id, option)"
                            >
                            {{ option }}
                        </label>
                    </div>

                    <input
                        v-else
                        v-model="formData[field.id]"
                        :type="field.type"
                        class="w-full px-3 py-2 rounded-lg border text-sm text-primary bg-white dark:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-[--th-accent]"
                        :class="errors[field.id] ? 'border-rose-400' : 'border-line/60'"
                        :placeholder="field.placeholder ?? ''"
                    >

                    <p v-if="errors[field.id]" class="text-xs text-rose-400">{{ errors[field.id] }}</p>
                </div>

                <!-- Navigation buttons -->
                <div class="flex items-center gap-3" :class="isMultiStep && currentStep > 0 ? 'justify-between' : 'justify-end'">
                    <button
                        v-if="isMultiStep && currentStep > 0"
                        type="button"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-lg text-sm font-medium text-secondary border border-line hover:bg-surface-2 transition-colors"
                        v-on:click="prevStep"
                    >
                        <ChevronLeft class="w-4 h-4" :stroke-width="2" />
                        {{ t("shared.form.prev") }}
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg text-sm font-medium text-white transition-colors focus:outline-none disabled:opacity-50"
                        style="background-color: var(--th-accent);"
                        :disabled="submitting"
                    >
                        <template v-if="submitting">{{ t("shared.form.submitting") }}</template>
                        <template v-else-if="!isLastStep()">
                            {{ t("shared.form.next") }}
                            <ChevronRight class="w-4 h-4" :stroke-width="2" />
                        </template>
                        <template v-else>{{ t("shared.form.submit") }}</template>
                    </button>
                </div>
            </form>
        </template>
    </div>
</template>
