<script setup>
import { useI18n } from "vue-i18n";
import { Plus, Save, Pencil, Trash2, X, Building2 } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { useServicesList } from "@core/backend/services/composables/useServicesList.js";
import { useServicesForm } from "@core/backend/services/composables/useServicesForm.js";
import { useServicesDelete } from "@core/backend/services/composables/useServicesDelete.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { isAdmin } = usePrivileges();

const props = defineProps({
    services: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    /**
     * Extra fields to register on the create + edit form. Lets clients extend
     * the modal + table without forking this component.
     * Example: { code: { default: '', fromEntity: (s) => s.code ?? '' } }
     */
    extraFields: { type: Object, default: () => ({}) },
});

const { serviceList } = useServicesList(props.services);
const { modal, form, errors, loading, openCreate, openEdit, submit } = useServicesForm(serviceList, props.createPath, props.updatePath, { extraFields: props.extraFields });
const { deletingService, confirmDelete } = useServicesDelete(serviceList, props.deletePath);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-primary">{{ t("backend.services.title") }}</h1>
            <AppButton v-if="isAdmin" variant="primary" size="md" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("backend.services.add") }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!serviceList.length" :message="t('backend.services.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.services.name") }}</th>
                        <slot name="extra-headers" />
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="service in serviceList" :key="service.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-4 py-3 font-medium text-primary">{{ service.name }}</td>
                        <slot name="extra-cells" :service="service" />
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="isAdmin" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(service)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton v-if="isAdmin" color="rose" :title="t('shared.common.delete')" v-on:click="deletingService = service">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppModal
            :show="modal.open"
            max-width="sm"
            :title="modal.entity ? t('backend.services.edit_title', { name: modal.entity.name }) : t('backend.services.create')"
            :icon="modal.entity ? Pencil : Building2"
            :closeable="false"
            v-on:close="modal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submit">
                <AppInput
                    v-model="form.name"
                    :label="t('backend.services.name')"
                    :placeholder="t('backend.services.namePlaceholder')"
                    :error="errors.name ?? ''"
                    :required="true"
                />
                <slot name="extra-form-fields" :form="form" :errors="errors" :service="modal.entity" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="modal.open = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="loading">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!deletingService"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingService = null"
        >
            <p class="text-sm text-primary">{{ t("backend.services.deleteConfirm", { name: deletingService?.name ?? "" }) }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingService = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.cancel") }}</AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t("shared.common.delete") }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
