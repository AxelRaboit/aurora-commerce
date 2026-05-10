<script setup>
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useContactsCreate } from "@crm/backend/contacts/composables/useContactsCreate.js";
import { useContactsEdit } from "@crm/backend/contacts/composables/useContactsEdit.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import AppPagination from "@/shared/components/nav/AppPagination.vue";
import AppNoData from "@/shared/components/feedback/AppNoData.vue";
import AppLink from "@/shared/components/nav/AppLink.vue";
import AppAvatar from "@/shared/components/display/AppAvatar.vue";
import { Plus, Pencil, Trash2, Eye, Save, X, Users } from "lucide-vue-next";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";

const { t } = useI18n();
const { can } = usePrivileges();

const props = defineProps({
    contacts: { type: Object, default: () => ({}) },
    search: { type: String, default: "" },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    listPath: { type: String, required: true },
    showPath: { type: String, default: "" },
});

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.contacts },
);

const { showCreate, newContact, createErrors, createLoading, openCreate, submitCreate } = useContactsCreate(props.createPath, reset);
const { showEdit, editingContact, editForm, editErrors, editLoading, openEdit, submitEdit } = useContactsEdit(props.updatePath, reset);
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(props.deletePath, () => reset(), "backend.crm.contacts.deleted");
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.crm.contacts.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton
                v-if="can('crm.contacts.create')"
                variant="primary"
                size="md"
                class="w-full sm:w-auto"
                v-on:click="openCreate"
            >
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('backend.crm.contacts.add') }}
            </AppButton>
        </div>

        <div class="sm:hidden space-y-3">
            <div v-for="contact in items" :key="contact.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <AppAvatar
                        :first-name="contact.firstName"
                        :last-name="contact.lastName"
                        :name="contact.fullName"
                        :email="contact.email"
                        size="lg"
                    />
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary">{{ contact.fullName }}</p>
                        <p v-if="contact.company" class="text-xs text-muted mt-0.5">{{ contact.company }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <p class="text-xs text-muted truncate">{{ contact.email ?? contact.phone ?? '—' }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: contact.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('crm.contacts.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(contact)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton v-if="can('crm.contacts.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(contact)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-surface-2/50 border-b border-line/40">
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.contacts.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.contacts.email') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.crm.contacts.company') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('backend.crm.contacts.phone') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/40">
                    <tr v-for="contact in items" :key="contact.id" class="group hover:bg-surface-2/40 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <AppAvatar
                                    :first-name="contact.firstName"
                                    :last-name="contact.lastName"
                                    :name="contact.fullName"
                                    :email="contact.email"
                                    size="md"
                                />
                                <span class="font-medium text-primary">{{ contact.fullName }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary">{{ contact.email ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ contact.company ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ contact.phone ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="showPath" color="sky" :href="buildPath(showPath, { id: contact.id })"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('crm.contacts.edit')" color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(contact)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton v-if="can('crm.contacts.delete')" color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(contact)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!items?.length">
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t('backend.crm.contacts.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <AppModal
            :show="showCreate"
            :title="t('backend.crm.contacts.create')"
            :icon="Users"
            :closeable="false"
            v-on:close="showCreate = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="newContact.firstName"
                        :label="t('backend.crm.contacts.firstName')"
                        :placeholder="t('backend.crm.contacts.firstNamePlaceholder')"
                        :error="createErrors.firstName"
                        required
                    />
                    <AppInput
                        v-model="newContact.lastName"
                        :label="t('backend.crm.contacts.lastName')"
                        :placeholder="t('backend.crm.contacts.lastNamePlaceholder')"
                        :error="createErrors.lastName"
                        required
                    />
                </div>
                <AppInput
                    v-model="newContact.email"
                    type="email"
                    :label="t('backend.crm.contacts.email')"
                    :placeholder="t('backend.crm.contacts.emailPlaceholder')"
                    :error="createErrors.email"
                />
                <AppInput v-model="newContact.phone" :label="t('backend.crm.contacts.phone')" :placeholder="t('backend.crm.contacts.phonePlaceholder')" />
                <AppInput v-model="newContact.company" :label="t('backend.crm.contacts.company')" :placeholder="t('backend.crm.contacts.companyPlaceholder')" />
                <AppTextarea v-model="newContact.notes" :rows="3" :placeholder="t('backend.crm.contacts.notesPlaceholder')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="showEdit"
            :title="t('backend.crm.contacts.edit', { name: editingContact?.fullName ?? '' })"
            :icon="Pencil"
            :closeable="false"
            v-on:close="showEdit = false"
        >
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <AppInput
                        v-model="editForm.firstName"
                        :label="t('backend.crm.contacts.firstName')"
                        :placeholder="t('backend.crm.contacts.firstNamePlaceholder')"
                        :error="editErrors.firstName"
                        required
                    />
                    <AppInput
                        v-model="editForm.lastName"
                        :label="t('backend.crm.contacts.lastName')"
                        :placeholder="t('backend.crm.contacts.lastNamePlaceholder')"
                        :error="editErrors.lastName"
                        required
                    />
                </div>
                <AppInput
                    v-model="editForm.email"
                    type="email"
                    :label="t('backend.crm.contacts.email')"
                    :placeholder="t('backend.crm.contacts.emailPlaceholder')"
                    :error="editErrors.email"
                />
                <AppInput v-model="editForm.phone" :label="t('backend.crm.contacts.phone')" :placeholder="t('backend.crm.contacts.phonePlaceholder')" />
                <AppInput v-model="editForm.company" :label="t('backend.crm.contacts.company')" :placeholder="t('backend.crm.contacts.companyPlaceholder')" />
                <AppTextarea v-model="editForm.notes" :rows="3" :placeholder="t('backend.crm.contacts.notesPlaceholder')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal :show="!!pendingDelete" max-width="sm" :closeable="false" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('backend.crm.contacts.deleteConfirm', { name: pendingDelete?.fullName ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.crm.contacts.deleteWarning') }}</p>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>
    </div>
</template>
