<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { Plus, Save, Pencil, Trash2, X, FolderKey } from "lucide-vue-next";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/input/AppInput.vue";
import AppSelect from "@/shared/components/form/select/AppSelect.vue";
import AppToggle from "@/shared/components/form/toggle/AppToggle.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { useAssistantMountPointsForm } from "./composables/useAssistantMountPointsForm.js";
import { useAssistantMountPointsDelete } from "./composables/useAssistantMountPointsDelete.js";

const props = defineProps({
    mountPoints: { type: Array, required: true },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { t } = useI18n();

const mountPointList = ref([...props.mountPoints]);
const { modal, form, errors, loading, openCreate, openEdit, submit } =
    useAssistantMountPointsForm(mountPointList, props.createPath, props.updatePath);
const { deletingMountPoint, confirmDelete } = useAssistantMountPointsDelete(
    mountPointList,
    props.deletePath,
);

const accessOptions = [
    { value: "read_only", label: t("assistant.mount_point.access.read_only") },
    { value: "read_write", label: t("assistant.mount_point.access.read_write") },
];
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <p class="text-sm text-secondary self-center">{{ t('assistant.mount_point.description') }}</p>
            <template #actions>
                <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('assistant.mount_point.add') }}
                </AppButton>
            </template>
        </AppListToolbar>

        <!-- Mobile: cards -->
        <div class="sm:hidden space-y-2">
            <AppNoData v-if="!mountPointList.length" :message="t('assistant.mount_point.empty')" />
            <div
                v-for="mountPoint in mountPointList"
                :key="mountPoint.id"
                class="bg-surface border border-line/60 rounded-xl overflow-hidden shadow-sm"
            >
                <div class="flex items-start gap-3 p-4">
                    <FolderKey class="w-5 h-5 text-muted shrink-0 mt-0.5" :stroke-width="1.5" />
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-primary truncate">{{ mountPoint.name }}</p>
                        <p class="text-xs font-mono text-secondary truncate">{{ mountPoint.path }}</p>
                        <p class="text-xs text-muted">
                            {{ t(`assistant.mount_point.access.${mountPoint.access}`) }}
                            <span
                                class="inline-block w-2 h-2 rounded-full ml-1 align-middle"
                                :class="mountPoint.active ? 'bg-emerald-500' : 'bg-line'"
                            />
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-1 px-3 py-2 border-t border-line/40 bg-surface-2/40">
                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(mountPoint)">
                        <Pencil class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="deletingMountPoint = mountPoint">
                        <Trash2 class="w-4 h-4" :stroke-width="2" />
                    </AppIconButton>
                </div>
            </div>
        </div>

        <!-- Desktop: table -->
        <div class="hidden sm:block bg-surface border border-line/60 rounded-xl overflow-hidden">
            <AppNoData v-if="!mountPointList.length" :message="t('assistant.mount_point.empty')" />
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('assistant.mount_point.fields.name') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('assistant.mount_point.fields.path') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('assistant.mount_point.fields.access') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('assistant.mount_point.fields.active') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="mountPoint in mountPointList" :key="mountPoint.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-4 py-3 font-medium text-primary">{{ mountPoint.name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-secondary">{{ mountPoint.path }}</td>
                        <td class="px-4 py-3 text-secondary">{{ t(`assistant.mount_point.access.${mountPoint.access}`) }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="inline-block w-2 h-2 rounded-full"
                                :class="mountPoint.active ? 'bg-emerald-500' : 'bg-line'"
                                :title="mountPoint.active ? t('shared.common.active') : t('shared.common.inactive')"
                            />
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(mountPoint)">
                                    <Pencil class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="deletingMountPoint = mountPoint">
                                    <Trash2 class="w-4 h-4" :stroke-width="2" />
                                </AppIconButton>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create / Edit modal -->
        <AppModal
            :show="modal.open"
            max-width="md"
            :closeable="false"
            :icon="modal.entity ? Pencil : FolderKey"
            :title="modal.entity ? t('assistant.mount_point.edit_title', { name: modal.entity.name }) : t('assistant.mount_point.add')"
            v-on:close="modal.open = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submit">
                <AppInput
                    v-model="form.name"
                    :label="t('assistant.mount_point.fields.name')"
                    :placeholder="t('assistant.mount_point.placeholder.name')"
                    :error="errors.name ?? ''"
                    :required="true"
                />
                <AppInput
                    v-model="form.path"
                    :label="t('assistant.mount_point.fields.path')"
                    :placeholder="t('assistant.mount_point.placeholder.path')"
                    :error="errors.path ?? ''"
                    :required="true"
                />
                <AppSelect
                    v-model="form.access"
                    :label="t('assistant.mount_point.fields.access')"
                    :options="accessOptions"
                />
                <label class="flex items-center gap-2 text-sm text-secondary">
                    <AppToggle v-model="form.active" />
                    {{ t('assistant.mount_point.fields.active') }}
                </label>
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="modal.open = false">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton
                        type="submit"
                        variant="primary"
                        size="md"
                        :loading="loading"
                        v-on:click="submit"
                    >
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.save') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete confirmation modal -->
        <AppModal
            :show="!!deletingMountPoint"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="deletingMountPoint = null"
        >
            <p class="text-sm text-primary">
                {{ t('assistant.mount_point.delete_confirm', { name: deletingMountPoint?.name ?? '' }) }}
            </p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="deletingMountPoint = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" v-on:click="confirmDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.delete') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
