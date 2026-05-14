<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { Plus, Pencil, Trash2, Save, X, Users, Eye, UserCheck } from "lucide-vue-next";
import AppAvatar from "@/shared/components/display/AppAvatar.vue";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppFieldLabel from "@/shared/components/form/AppFieldLabel.vue";
import AppDatePicker from "@/shared/components/form/AppDatePicker.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import { useEmployeeForm } from "./composables/useEmployeeForm.js";
import { useEmployeeFormOptions } from "./composables/useEmployeeFormOptions.js";

const props = defineProps({
    initialData: { type: Object, default: null },
    search: { type: String, default: "" },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    usersSelectablePath: { type: String, required: true },
    servicesSelectablePath: { type: String, required: true },
    agenciesSelectablePath: { type: String, required: true },
    extraFields: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const { can } = usePrivileges();

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.initialData },
);

// View modal
const viewingEmployee = ref(null);

function openView(employee) {
    viewingEmployee.value = employee;
}

const {
    modal,
    loading,
    form,
    errors,
    openCreate,
    openEdit,
    close,
    submit,
} = useEmployeeForm(props.createPath, props.updatePath, { extraFields: props.extraFields, onSuccess: reload });

const {
    pendingDelete,
    loading: deleteLoading,
    confirm: confirmDelete,
    submit: doDelete,
} = useDelete(props.deletePath, () => reload(), "backend.employees.toast.deleted");

const { serviceOptions, agencyOptions, userOptions } = useEmployeeFormOptions(props.servicesSelectablePath, props.agenciesSelectablePath, props.usersSelectablePath);
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput v-model="searchInput" :placeholder="t('backend.employees.searchPlaceholder')" v-on:search="onSearch" />
            <template #actions>
                <AppButton
                    v-if="can('hr.employees.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreate"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.employees.add') }}
                </AppButton>
            </template>
        </AppListToolbar>

        <!-- Mobile cards -->
        <div class="sm:hidden space-y-3">
            <div v-for="employee in items" :key="employee.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-medium text-primary">{{ employee.fullName }}</p>
                        <p v-if="employee.jobTitle || employee.service || employee.agency" class="text-xs text-muted mt-0.5">
                            {{ [employee.jobTitle, employee.service?.name, employee.agency?.name].filter(Boolean).join(' · ') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-0.5 shrink-0">
                        <AppIconButton color="sky" :title="t('shared.common.view')" v-on:click="openView(employee)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <template v-if="can('hr.employees.edit') || can('hr.employees.delete')">
                            <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(employee)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(employee)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        </template>
                    </div>
                </div>
            </div>
            <AppNoData v-if="!items?.length" :message="t('backend.employees.empty')" />
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.employees.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.employees.fields.jobTitle') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.employees.fields.service') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.employees.fields.agency') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="employee in items" :key="employee.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <p class="font-medium text-primary">{{ employee.fullName }}</p>
                            <p v-if="employee.workEmail ?? employee.user?.email" class="text-xs text-muted">{{ employee.workEmail ?? employee.user?.email }}</p>
                        </td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ employee.jobTitle ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ employee.service?.name ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ employee.agency?.name ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :title="t('shared.common.view')" v-on:click="openView(employee)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <template v-if="can('hr.employees.edit') || can('hr.employees.delete')">
                                    <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(employee)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                    <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(employee)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!items?.length">
                        <td :colspan="5"><AppNoData :message="t('backend.employees.empty')" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <!-- View modal -->
        <AppModal
            :show="!!viewingEmployee"
            :title="viewingEmployee?.fullName ?? ''"
            :icon="UserCheck"
            :closeable="false"
            v-on:close="viewingEmployee = null"
        >
            <dl v-if="viewingEmployee" class="space-y-3">
                <div v-if="viewingEmployee.jobTitle" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.jobTitle') }}</dt>
                    <dd class="text-sm text-primary sm:col-span-2">{{ viewingEmployee.jobTitle }}</dd>
                </div>
                <div v-if="viewingEmployee.service" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.service') }}</dt>
                    <dd class="text-sm text-primary sm:col-span-2">{{ viewingEmployee.service.name }}</dd>
                </div>
                <div v-if="viewingEmployee.agency" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.agency') }}</dt>
                    <dd class="text-sm text-primary sm:col-span-2">{{ viewingEmployee.agency.name }}</dd>
                </div>
                <div v-if="viewingEmployee.phone" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.phone') }}</dt>
                    <dd class="text-sm text-primary sm:col-span-2">{{ viewingEmployee.phone }}</dd>
                </div>
                <div v-if="viewingEmployee.workEmail" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.workEmail') }}</dt>
                    <dd class="text-sm text-primary sm:col-span-2">{{ viewingEmployee.workEmail }}</dd>
                </div>
                <div v-if="viewingEmployee.hiredAt" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.hiredAt') }}</dt>
                    <dd class="text-sm text-primary sm:col-span-2">{{ viewingEmployee.hiredAt }}</dd>
                </div>
                <div v-if="viewingEmployee.leftAt" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.leftAt') }}</dt>
                    <dd class="text-sm text-primary sm:col-span-2">{{ viewingEmployee.leftAt }}</dd>
                </div>
                <div v-if="viewingEmployee.user" class="grid grid-cols-1 sm:grid-cols-3 gap-1">
                    <dt class="text-sm font-medium text-muted">{{ t('backend.employees.fields.user') }}</dt>
                    <dd class="sm:col-span-2">
                        <div class="flex items-center gap-3">
                            <AppAvatar
                                :name="viewingEmployee.user.name"
                                :email="viewingEmployee.user.email"
                                size="md"
                            />
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-primary">{{ viewingEmployee.user.name }}</p>
                                <p class="text-xs text-muted truncate">{{ viewingEmployee.user.email }}</p>
                            </div>
                        </div>
                    </dd>
                </div>
            </dl>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="viewingEmployee = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.close') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Edit / Create modal -->
        <AppModal
            :show="modal.open"
            :title="modal.entity ? modal.entity.fullName : t('backend.employees.add')"
            :icon="modal.entity ? Pencil : UserCheck"
            :closeable="false"
            v-on:close="close"
        >
            <form class="space-y-4" v-on:submit.prevent="submit">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <AppFieldLabel :label="t('backend.employees.fields.firstName')" required />
                        <AppInput v-model="form.firstName" :placeholder="t('backend.employees.placeholders.firstName')" :error="errors.firstName" />
                    </div>
                    <div>
                        <AppFieldLabel :label="t('backend.employees.fields.lastName')" required />
                        <AppInput v-model="form.lastName" :placeholder="t('backend.employees.placeholders.lastName')" :error="errors.lastName" />
                    </div>
                </div>
                <div>
                    <AppFieldLabel :label="t('backend.employees.fields.jobTitle')" />
                    <AppInput v-model="form.jobTitle" :placeholder="t('backend.employees.placeholders.jobTitle')" :error="errors.jobTitle" />
                </div>
                <div>
                    <AppFieldLabel :label="t('backend.employees.fields.service')" />
                    <AppMultiselect v-model="form.serviceId" :options="serviceOptions" :placeholder="t('backend.employees.placeholders.service')" />
                </div>
                <div>
                    <AppFieldLabel :label="t('backend.employees.fields.agency')" />
                    <AppMultiselect v-model="form.agencyId" :options="agencyOptions" :placeholder="t('backend.employees.placeholders.agency')" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <AppFieldLabel :label="t('backend.employees.fields.phone')" />
                        <AppInput v-model="form.phone" :placeholder="t('backend.employees.placeholders.phone')" :error="errors.phone" />
                    </div>
                    <div>
                        <AppFieldLabel :label="t('backend.employees.fields.workEmail')" />
                        <AppInput v-model="form.workEmail" type="email" :placeholder="t('backend.employees.placeholders.workEmail')" :error="errors.workEmail" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <AppFieldLabel :label="t('backend.employees.fields.hiredAt')" />
                        <AppDatePicker v-model="form.hiredAt" />
                    </div>
                    <div>
                        <AppFieldLabel :label="t('backend.employees.fields.leftAt')" />
                        <AppDatePicker v-model="form.leftAt" />
                    </div>
                </div>
                <div>
                    <AppFieldLabel :label="t('backend.employees.fields.user')" />
                    <AppMultiselect v-model="form.userId" :options="userOptions" :placeholder="t('backend.employees.placeholders.user')" open-direction="top" />
                </div>
                <slot name="extra-form-fields" :form="form" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="close">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton variant="primary" size="md" :loading="loading" v-on:click="submit">
                        <Save class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.save') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <!-- Delete confirmation modal -->
        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
            <p class="text-sm text-primary">{{ t('backend.employees.deleteConfirm', { name: pendingDelete?.fullName ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.employees.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">
                        <X class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.cancel') }}
                    </AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">
                        <Trash2 class="w-3.5 h-3.5" :stroke-width="2" />
                        {{ t('shared.common.delete') }}
                    </AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
