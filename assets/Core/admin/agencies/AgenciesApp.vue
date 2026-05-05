<script setup>
import { useI18n } from "vue-i18n";
import { Plus, Save, Pencil, Trash2 } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { useAgenciesList } from "@core/admin/agencies/composables/useAgenciesList.js";
import { useAgenciesEdit } from "@core/admin/agencies/composables/useAgenciesEdit.js";
import { useAgenciesDelete } from "@core/admin/agencies/composables/useAgenciesDelete.js";

const { t } = useI18n();

const props = defineProps({
    agencies: { type: Array, default: () => [] },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { agencyList } = useAgenciesList(props.agencies);
const { editModal, editForm, openCreate, openEdit, submitEdit } = useAgenciesEdit(agencyList, props.createPath, props.updatePath);
const { deletingAgency, confirmDelete } = useAgenciesDelete(agencyList, props.deletePath);
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-primary">{{ t("admin.agencies.title") }}</h1>
            <AppButton variant="primary" size="md" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t("admin.agencies.new") }}
            </AppButton>
        </div>

        <div class="bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!agencyList.length" :message="t('admin.agencies.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t("admin.agencies.name") }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t("shared.common.actions") }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="agency in agencyList" :key="agency.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-4 py-3 font-medium text-primary">{{ agency.name }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(agency)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="deletingAgency = agency">
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
                {{ editModal.agency ? t("admin.agencies.edit_title", { name: editModal.agency.name }) : t("admin.agencies.new") }}
            </h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('admin.agencies.name')"
                    :placeholder="t('admin.agencies.namePlaceholder')"
                    :error="editModal.errors.name ?? ''"
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

        <AppModal :show="!!deletingAgency" max-width="sm" v-on:close="deletingAgency = null">
            <p class="text-sm text-primary">{{ t("admin.agencies.deleteConfirm", { name: deletingAgency?.name ?? "" }) }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="deletingAgency = null">{{ t("shared.common.cancel") }}</AppButton>
                <AppButton variant="danger" size="md" v-on:click="confirmDelete">{{ t("shared.common.delete") }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
