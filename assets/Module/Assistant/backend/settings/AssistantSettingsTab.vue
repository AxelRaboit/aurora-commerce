<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Save } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppMultiselect from "@/shared/components/form/select/AppMultiselect.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppLoader from "@/shared/components/feedback/AppLoader.vue";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

const MODELS_ENDPOINT = "/backend/assistant/settings/models";

const props = defineProps({
    groups: { type: Object, default: () => ({}) },
    updatePath: { type: String, default: "" },
});

const { t } = useI18n();
const { request, loading } = useRequest();

// ── Build initial field values from SSR data ──────────────────────────
const fields = computed(() => props.groups?.assistant ?? []);
const fieldByKey = computed(() =>
    Object.fromEntries(fields.value.map((f) => [f.key, f])),
);

// Initialise synchronously so fields are pre-populated before the first render.
const values = reactive(
    Object.fromEntries(
        (props.groups?.assistant ?? []).map((f) => [
            f.key,
            f.value ?? f.defaultValue ?? "",
        ]),
    ),
);

// ── Model list ────────────────────────────────────────────────────────
const availableModels = ref([]);
const modelsLoading = ref(false);

async function loadModels(provider) {
    modelsLoading.value = true;
    availableModels.value = [];
    try {
        const res = await request(
            `${MODELS_ENDPOINT}?provider=${provider}`,
            null,
            HttpMethod.Get,
        );
        if (res?.success) {
            availableModels.value = (res.models ?? []).map((m) => ({
                value: m,
                label: m,
            }));
        }
    } finally {
        modelsLoading.value = false;
    }
}

onMounted(() => loadModels(values["assistant_provider"] || "ollama"));

// When provider changes: save it immediately + reload model list + clear current model
watch(
    () => values["assistant_provider"],
    async (newProvider) => {
        if (!newProvider) return;
        await saveSingle("assistant_provider", newProvider);
        values["assistant_chat_model"] = "";
        await loadModels(newProvider);
    },
);

// ── Save ──────────────────────────────────────────────────────────────
async function saveSingle(key, value) {
    await request(props.updatePath, { key, value });
}

async function saveAll() {
    const keys = fields.value
        .map((f) => f.key)
        .filter((k) => k !== "assistant_provider"); // already auto-saved on change

    try {
        for (const key of keys) {
            await request(
                props.updatePath,
                { key, value: values[key] ?? "" },
                { noGuard: true },
            );
        }
        toast.success(t("backend.settings.saved"));
    } catch {
        toast.error(t("shared.common.error"));
    }
}

// Provider select options from the field descriptor
const providerOptions = computed(
    () => fieldByKey.value["assistant_provider"]?.options ?? [],
);
</script>

<template>
    <div class="space-y-6">
        <!-- Provider -->
        <AppSelect
            v-model="values['assistant_provider']"
            :label="t('backend.parameters.assistant_provider.label')"
            :options="providerOptions"
            :placeholder="t('backend.parameters.assistant_provider.label')"
        />
        <p class="text-xs text-muted -mt-4">{{ t('backend.parameters.assistant_provider.description') }}</p>

        <!-- Chat model — dynamic select -->
        <div class="relative space-y-1">
            <AppLoader :active="modelsLoading" />
            <label class="block text-sm font-medium text-secondary">{{ t('backend.parameters.assistant_chat_model.label') }}</label>
            <AppMultiselect
                v-if="availableModels.length"
                :model-value="values['assistant_chat_model']"
                :options="availableModels"
                track-by="value"
                option-label="label"
                :searchable="true"
                :placeholder="t('backend.parameters.assistant_chat_model.label')"
                v-on:update:model-value="values['assistant_chat_model'] = $event"
            />
            <AppInput
                v-else
                v-model="values['assistant_chat_model']"
                :placeholder="modelsLoading ? '…' : t('backend.parameters.assistant_chat_model.label')"
            />
            <p class="text-xs text-muted">{{ t('backend.parameters.assistant_chat_model.description') }}</p>
        </div>

        <!-- Vision model — Ollama only -->
        <div v-if="values['assistant_provider'] !== 'anthropic'" class="relative space-y-1">
            <AppLoader :active="modelsLoading" />
            <label class="block text-sm font-medium text-secondary">{{ t('backend.parameters.assistant_vision_model.label') }}</label>
            <AppMultiselect
                v-if="availableModels.length"
                :model-value="values['assistant_vision_model']"
                :options="availableModels"
                track-by="value"
                option-label="label"
                :searchable="true"
                :placeholder="t('backend.parameters.assistant_vision_model.label')"
                v-on:update:model-value="values['assistant_vision_model'] = $event"
            />
            <AppInput
                v-else
                v-model="values['assistant_vision_model']"
                :placeholder="modelsLoading ? '…' : 'qwen2.5vl:3b'"
            />
            <p class="text-xs text-muted">{{ t('backend.parameters.assistant_vision_model.description') }}</p>
        </div>

        <!-- Timeout + num_ctx (Ollama) ou max_tokens (Anthropic) -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <AppInput
                    v-model="values['assistant_http_timeout']"
                    type="number"
                    :label="t('backend.parameters.assistant_http_timeout.label')"
                    placeholder="300"
                />
                <p class="text-xs text-muted">{{ t('backend.parameters.assistant_http_timeout.description') }}</p>
            </div>
            <div class="space-y-1">
                <AppInput
                    v-if="values['assistant_provider'] !== 'anthropic'"
                    v-model="values['assistant_num_ctx']"
                    type="number"
                    :label="t('backend.parameters.assistant_num_ctx.label')"
                    placeholder="8192"
                />
                <AppInput
                    v-else
                    v-model="values['assistant_num_ctx']"
                    type="number"
                    :label="t('backend.parameters.assistant_max_tokens.label')"
                    placeholder="4096"
                />
            </div>
        </div>

        <!-- System prompt -->
        <div class="space-y-1">
            <label class="block text-sm font-medium text-secondary">{{ t('backend.parameters.assistant_system_prompt.label') }}</label>
            <textarea
                v-model="values['assistant_system_prompt']"
                rows="10"
                class="block w-full rounded-md border border-line bg-surface px-3 py-2 text-sm text-primary resize-y focus:border-accent-500 focus:ring-1 focus:ring-accent-500 transition"
                :placeholder="fieldByKey['assistant_system_prompt']?.defaultValue ?? ''"
            />
            <p class="text-xs text-muted">{{ t('backend.parameters.assistant_system_prompt.description') }}</p>
        </div>

        <!-- Save -->
        <div class="flex justify-end">
            <AppButton variant="primary" size="md" :loading="loading" v-on:click="saveAll">
                <Save class="w-4 h-4" :stroke-width="2" />
                {{ t('shared.common.save') }}
            </AppButton>
        </div>
    </div>
</template>
