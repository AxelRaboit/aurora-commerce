<script setup>
import { ref, reactive, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";
import { SettingErrorCode } from "@core/utils/enums/settings/settingErrorCode.js";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import { Lock } from "lucide-vue-next";

const { t } = useI18n();

const props = defineProps({
    modulesPath: { type: String, required: true },
    moduleUpdatePath: { type: String, required: true },
    initialData: { type: Object, default: null },
});

const parameters = ref(props.initialData?.parameters ?? []);
const fieldValues = reactive({});
const initialValues = {};
const parameterByKey = {};
const saving = ref(false);

function init(params) {
    for (const p of params) {
        const value = p.value ?? "0";
        fieldValues[p.key] = value;
        initialValues[p.key] = value;
        parameterByKey[p.key] = p;
    }
}

init(parameters.value);

onMounted(async () => {
    if (parameters.value.length) return;
    const res = await fetch(props.modulesPath, { headers: { "X-Requested-With": "XMLHttpRequest" } });
    const data = await res.json();
    parameters.value = data.parameters ?? [];
    init(parameters.value);
});

function isLocked(parameter) {
    return parameter.requires ? fieldValues[parameter.requires] !== "1" : false;
}

function lockReason(parameter) {
    const parent = parameter.requires ? parameterByKey[parameter.requires] : null;
    return parent ? t("admin.settings.cascadeLocked", { parent: parent.label }) : "";
}

function onToggle(parameter, enabled) {
    fieldValues[parameter.key] = enabled ? "1" : "0";
    if (!enabled) {
        for (const child of Object.values(parameterByKey)) {
            if (child.requires === parameter.key && fieldValues[child.key] === "1") {
                onToggle(child, false);
            }
        }
    }
}

async function save() {
    saving.value = true;

    const changed = parameters.value
        .filter((p) => fieldValues[p.key] !== initialValues[p.key])
        .sort((a, b) => (a.requires ? 1 : 0) - (b.requires ? 1 : 0));

    if (!changed.length) {
        saving.value = false;
        toast.success(t("admin.settings.saved"));
        return;
    }

    try {
        for (const parameter of changed) {
            const res = await fetch(props.moduleUpdatePath.replace("__key__", parameter.key), {
                method: HttpMethod.Patch,
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ value: fieldValues[parameter.key] }),
            });

            const result = await res.json();

            if (!result.ok) {
                if (result.error === SettingErrorCode.CascadeViolation) {
                    const parent = parameterByKey[result.parentKey];
                    toast.error(t("admin.settings.cascadeLocked", { parent: parent?.label ?? result.parentKey }));
                } else {
                    toast.error(t("shared.common.error"));
                }
                return;
            }

            initialValues[parameter.key] = fieldValues[parameter.key];
        }

        toast.success(t("admin.settings.saved"));
    } catch {
        toast.error(t("shared.common.error"));
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="bg-surface border border-line rounded-xl p-6 space-y-6">
        <div
            v-for="parameter in parameters"
            :key="parameter.key"
            class="flex items-center justify-between gap-4"
            :class="{ 'opacity-60': isLocked(parameter) }"
        >
            <div class="min-w-0">
                <p class="text-sm font-medium text-primary flex items-center gap-1.5">
                    {{ parameter.label }}
                    <Lock v-if="isLocked(parameter)" class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                </p>
                <p v-if="parameter.description" class="text-xs text-muted mt-0.5">{{ parameter.description }}</p>
                <p v-if="isLocked(parameter)" class="text-xs text-warning mt-0.5">{{ lockReason(parameter) }}</p>
            </div>
            <AppToggle
                :model-value="!isLocked(parameter) && fieldValues[parameter.key] === '1'"
                :disabled="isLocked(parameter)"
                v-on:update:model-value="onToggle(parameter, $event)"
            />
        </div>

        <div class="pt-2 border-t border-line flex justify-end">
            <AppButton
                type="button"
                variant="primary"
                size="md"
                :loading="saving"
                v-on:click="save"
            >
                {{ t("admin.settings.save") }}
            </AppButton>
        </div>
    </div>
</template>
