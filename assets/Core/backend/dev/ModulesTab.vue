<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import AppToggle from "@/shared/components/form/AppToggle.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Lock } from "lucide-vue-next";
import { useModules } from "@core/backend/dev/composables/useModules.js";

const { t } = useI18n();

const props = defineProps({
    modulesPath: { type: String, required: true },
    moduleUpdatePath: { type: String, required: true },
    moduleVerifyPasswordPath: { type: String, required: true },
    initialData: { type: Object, default: null },
});

const modules = useModules(
    props.modulesPath,
    props.moduleUpdatePath,
    props.moduleVerifyPasswordPath,
    props.initialData,
);

onMounted(() => {
    if (!modules.parameters.value.length) modules.load();
});
</script>

<template>
    <div class="space-y-3">
        <p class="text-sm text-secondary">{{ t("backend.settings.modulesIntro") }}</p>
        <AppSearchInput
            v-model="modules.searchInput.value"
            :placeholder="t('backend.settings.modulesSearchPlaceholder')"
        />

        <div class="bg-surface border border-line rounded-xl p-6 space-y-6">
            <AppNoData v-if="!modules.filteredParameters.value.length" :message="t('backend.settings.modulesEmpty')" />

            <div
                v-for="parameter in modules.filteredParameters.value"
                :key="parameter.key"
                class="space-y-3"
            >
                <!-- Parent module row -->
                <div
                    class="flex items-center justify-between gap-4"
                    :class="{ 'opacity-60': modules.isLocked(parameter) }"
                >
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-primary flex items-center gap-1.5">
                            {{ parameter.label }}
                            <Lock v-if="modules.isLocked(parameter)" class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                        </p>
                        <p v-if="parameter.description" class="text-xs text-muted mt-0.5">{{ parameter.description }}</p>
                        <p v-if="modules.isLocked(parameter)" class="text-xs text-warning mt-0.5">{{ modules.lockReason(parameter) }}</p>
                        <div v-if="parameter.navItems?.length && !parameter.subModules?.length" class="flex flex-wrap gap-1 mt-1.5">
                            <span
                                v-for="item in parameter.navItems"
                                :key="item.labelKey"
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-xs text-muted bg-surface-alt border border-line"
                            >{{ t(item.labelKey) }}</span>
                        </div>
                    </div>
                    <AppToggle
                        :model-value="!modules.isLocked(parameter) && modules.fieldValues[parameter.key] === '1'"
                        :disabled="modules.isLocked(parameter) || modules.saving.value"
                        v-on:update:model-value="modules.onToggle(parameter, $event)"
                    />
                </div>

                <!-- Sub-modules -->
                <div
                    v-if="parameter.subModules?.length"
                    class="ml-4 pl-4 border-l border-line space-y-3"
                >
                    <div
                        v-for="sub in parameter.subModules"
                        :key="sub.key"
                        class="flex items-center justify-between gap-4"
                        :class="{ 'opacity-60': modules.isLocked(sub) }"
                    >
                        <div class="min-w-0">
                            <p class="text-sm text-primary flex items-center gap-1.5">
                                {{ sub.label }}
                                <Lock v-if="modules.isLocked(sub)" class="w-3.5 h-3.5 text-muted" :stroke-width="2" />
                            </p>
                            <p v-if="sub.description" class="text-xs text-muted mt-0.5">{{ sub.description }}</p>
                            <p v-if="modules.isLocked(sub)" class="text-xs text-warning mt-0.5">{{ modules.lockReason(sub) }}</p>
                        </div>
                        <AppToggle
                            :model-value="!modules.isLocked(sub) && modules.fieldValues[sub.key] === '1'"
                            :disabled="modules.isLocked(sub) || modules.saving.value"
                            v-on:update:model-value="modules.onToggle(sub, $event)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <AppModal
            :show="modules.showPasswordModal.value"
            :title="t('backend.settings.confirmPassword')"
            max-width="sm"
            v-on:close="modules.showPasswordModal.value = false"
        >
            <p class="text-sm text-muted">{{ t("backend.settings.confirmPasswordDescription") }}</p>
            <AppInput
                v-model="modules.password.value"
                type="password"
                :placeholder="t('backend.settings.confirmPasswordPlaceholder')"
                :error="modules.passwordError.value"
                v-on:keydown.enter="modules.confirmPassword"
            />
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="modules.showPasswordModal.value = false">
                        {{ t("shared.common.cancel") }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :loading="modules.verifying.value" v-on:click="modules.confirmPassword">
                        {{ t("backend.settings.confirmPasswordConfirm") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
