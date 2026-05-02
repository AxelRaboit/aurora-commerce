<script setup>
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import { FormFieldType } from "@editorial/utils/enums/formFieldType.js";

const { t } = useI18n();

const props = defineProps({
    submitPath: { type: String, required: true },
    formTitle: { type: String, default: "" },
    formDescription: { type: String, default: null },
    fields: { type: Array, default: () => [] },
});

const submitting = ref(false);
const submitted = ref(false);
const errors = reactive({});
const formData = reactive({});

for (const field of props.fields) {
    formData[field.id] = field.type === FormFieldType.Checkbox ? [] : "";
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

async function handleSubmit() {
    submitting.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        const response = await fetch(props.submitPath, {
            method: HttpMethod.Post,
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ ...formData }),
        });
        const data = await response.json();
        if (data.success) {
            submitted.value = true;
        } else if (data.errors) {
            Object.assign(errors, data.errors);
        }
    } catch {
        errors["_global"] = t("shared.form.error");
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <div v-if="submitted" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-5 py-4 text-emerald-400 text-sm font-medium">
            {{ t("shared.form.success") }}
        </div>

        <template v-else>
            <div v-if="formTitle || formDescription" class="space-y-1">
                <h1 v-if="formTitle" class="text-2xl font-bold text-primary">{{ formTitle }}</h1>
                <p v-if="formDescription" class="text-secondary text-sm">{{ formDescription }}</p>
            </div>

            <p v-if="errors['_global']" class="text-sm text-rose-400">{{ errors["_global"] }}</p>

            <form class="space-y-5" v-on:submit.prevent="handleSubmit">
                <div v-for="field in fields" :key="field.id" class="flex flex-col gap-1.5">
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
                        :required="field.required"
                    />

                    <select
                        v-else-if="field.type === 'select'"
                        v-model="formData[field.id]"
                        class="w-full px-3 py-2 rounded-lg border text-sm text-primary bg-white dark:bg-surface-2 focus:outline-none focus:ring-2 focus:ring-[--th-accent]"
                        :class="errors[field.id] ? 'border-rose-400' : 'border-line/60'"
                        :required="field.required"
                    >
                        <option value="">{{ t("shared.form.selectPlaceholder") }}</option>
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
                        :required="field.required"
                    >

                    <p v-if="errors[field.id]" class="text-xs text-rose-400">{{ errors[field.id] }}</p>
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-lg text-sm font-medium text-white transition-colors focus:outline-none disabled:opacity-50"
                    style="background-color: var(--th-accent);"
                    :disabled="submitting"
                >
                    {{ submitting ? t("shared.form.submitting") : t("shared.form.submit") }}
                </button>
            </form>
        </template>
    </div>
</template>
