<script setup>
import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import AppButton from "@/components/AppButton.vue";
import AppInput from "@/components/AppInput.vue";

const props = defineProps({
    groups: { type: Object, default: () => ({}) },
    updatePath: { type: String, default: "" },
    mediaPickerPath: { type: String, default: "" },
});

const { t } = useI18n();

const groupOrder = ["general", "reading", "localization", "branding", "seo"];

const availableGroups = groupOrder.filter((groupName) => props.groups[groupName]);

const activeTab = ref(availableGroups[0] ?? "general");

const tabLabels = {
    general: () => t("admin.settings.tabs.general"),
    reading: () => t("admin.settings.tabs.reading"),
    localization: () => t("admin.settings.tabs.localization"),
    branding: () => t("admin.settings.tabs.branding"),
    seo: () => t("admin.settings.tabs.seo"),
};

const fieldValues = reactive({});
for (const groupName of availableGroups) {
    for (const parameter of props.groups[groupName]) {
        fieldValues[parameter.key] = parameter.value ?? "";
    }
}

const savingGroups = reactive({});

async function saveGroup(groupName) {
    savingGroups[groupName] = true;
    const parameters = props.groups[groupName];

    try {
        for (const parameter of parameters) {
            const response = await fetch(props.updatePath, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ key: parameter.key, value: fieldValues[parameter.key] }),
            });

            const result = await response.json();

            if (!result.ok) {
                toast.error(t("common.error"));
                return;
            }
        }

        toast.success(t("admin.settings.saved"));
    } catch {
        toast.error(t("common.error"));
    } finally {
        savingGroups[groupName] = false;
    }
}
</script>

<template>
    <div class="flex gap-6">
        <nav class="hidden md:flex flex-col w-44 shrink-0 gap-0.5">
            <button
                v-for="groupName in availableGroups"
                :key="groupName"
                type="button"
                class="text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors"
                :class="activeTab === groupName
                    ? 'bg-indigo-600/15 text-indigo-400'
                    : 'text-secondary hover:text-primary hover:bg-surface-2'"
                v-on:click="activeTab = groupName"
            >
                {{ tabLabels[groupName]?.() ?? groupName }}
            </button>
        </nav>

        <div class="flex md:hidden gap-1 flex-wrap mb-4 w-full">
            <button
                v-for="groupName in availableGroups"
                :key="groupName"
                type="button"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors"
                :class="activeTab === groupName
                    ? 'bg-indigo-600/15 text-indigo-400'
                    : 'bg-surface-2 text-secondary hover:text-primary'"
                v-on:click="activeTab = groupName"
            >
                {{ tabLabels[groupName]?.() ?? groupName }}
            </button>
        </div>

        <div class="flex-1 min-w-0">
            <div
                v-for="groupName in availableGroups"
                v-show="activeTab === groupName"
                :key="groupName"
            >
                <div class="bg-surface border border-line rounded-xl p-6 space-y-6">
                    <div
                        v-for="parameter in groups[groupName]"
                        :key="parameter.key"
                    >
                        <template v-if="parameter.type === 'bool'">
                            <div class="flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-primary">{{ parameter.label }}</p>
                                    <p v-if="parameter.description" class="text-xs text-muted mt-0.5">{{ parameter.description }}</p>
                                </div>
                                <button
                                    type="button"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-surface"
                                    :class="fieldValues[parameter.key] === '1' ? 'bg-indigo-600' : 'bg-surface-3'"
                                    v-on:click="fieldValues[parameter.key] = fieldValues[parameter.key] === '1' ? '0' : '1'"
                                >
                                    <span
                                        class="inline-block h-4 w-4 rounded-full bg-white shadow transition-transform"
                                        :class="fieldValues[parameter.key] === '1' ? 'translate-x-6' : 'translate-x-1'"
                                    />
                                </button>
                            </div>
                        </template>

                        <template v-else-if="parameter.type === 'int' || parameter.type === 'media'">
                            <AppInput
                                type="number"
                                :label="parameter.label"
                                :placeholder="parameter.key"
                                :model-value="fieldValues[parameter.key]"
                                v-on:update:model-value="fieldValues[parameter.key] = $event"
                            />
                            <p v-if="parameter.description" class="text-xs text-muted mt-1">{{ parameter.description }}</p>
                            <p v-if="parameter.type === 'media' && props.mediaPickerPath" class="text-xs text-muted mt-1">
                                <a :href="props.mediaPickerPath" target="_blank" rel="noopener" class="text-indigo-400 hover:underline">
                                    {{ t("admin.settings.browseMedia") }}
                                </a>
                            </p>
                        </template>

                        <template v-else>
                            <AppInput
                                type="text"
                                :label="parameter.label"
                                :placeholder="parameter.key"
                                :model-value="fieldValues[parameter.key]"
                                v-on:update:model-value="fieldValues[parameter.key] = $event"
                            />
                            <p v-if="parameter.description" class="text-xs text-muted mt-1">{{ parameter.description }}</p>
                        </template>
                    </div>

                    <div class="pt-2 border-t border-line flex justify-end">
                        <AppButton
                            type="button"
                            variant="primary"
                            size="md"
                            :loading="savingGroups[groupName]"
                            v-on:click="saveGroup(groupName)"
                        >
                            {{ t("admin.settings.save") }}
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
