<script setup>
import { useI18n } from "vue-i18n";
import { Plus, Save, Pencil, Trash2 } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { useServicesList } from "@core/admin/services/composables/useServicesList.js";
import { useServicesEdit } from "@core/admin/services/composables/useServicesEdit.js";
import { useServicesDelete } from "@core/admin/services/composables/useServicesDelete.js";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { isAdmin } = usePrivileges();

const props = defineProps({
    services: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { serviceList } = useServicesList(props.services);
const { editModal, editForm, openCreate, openEdit, submitEdit } = useServicesEdit(serviceList, props.createPath, props.updatePath);
const { deletingService, confirmDelete } = useServicesDelete(serviceList, props.deletePath);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-primary">{{ t("backend.services.title") }}</h1>
            <AppButton v-if="isAdmin" variant="primary" size="md" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("backend.services.new") }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!serviceList.length" :message="t('backend.services.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("backend.services.name") }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="service in serviceList" :key="service.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-4 py-3 font-medium text-primary">{{ service.name }}</td>
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

        <AppModal :show="editModal.open" max-width="sm" v-on:close="editModal.open = false">
            <h3 class="text-lg font-semibold text-primary">
                {{ editModal.service ? t("backend.services.edit_title", { name: editModal.service.name }) : t("backend.services.new") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.services.name')"
                    :placeholder="t('backend.services.namePlaceholder')"
                    :error="editModal.errors.name ?? ''"
                    :required="true"
                />
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-line/40">
                    <AppButton variant="ghost" size="md" v-on:click="editModal.open = false">{{ t("shared.common.cancel") }}</AppButton>
                    <AppButton type="submit" variant="primary" size="md" :loading="editModal.saving">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t("shared.common.save") }}
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <AppModal :show="!!deletingService" max-width="sm" v-on:close="deletingService = null">
            <p class="text-sm text-primary">{{ t("backend.services.deleteConfirm", { name: deletingService?.name ?? "" }) }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="deletingService = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDelete">{{ t("shared.common.delete") }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
