<script setup>
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/list/useListPage.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import { useContactsCreate } from "@crm/backend/contacts/composables/useContactsCreate.js";
import { useContactsEdit } from "@crm/backend/contacts/composables/useContactsEdit.js";
import { useContactsShow, contactSourceColor } from "@crm/backend/contacts/composables/useContactsShow.js";
import { useContactsTags } from "@crm/backend/contacts/composables/useContactsTags.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppBadge from "@/shared/components/feedback/AppBadge.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppMultiselect from "@/shared/components/form/AppMultiselect.vue";
import AppSearchInput from "@/shared/components/form/AppSearchInput.vue";
import AppListToolbar from "@/shared/components/list/AppListToolbar.vue";
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
    activityPath: { type: String, default: "" },
    tagsPath: { type: String, required: true },
});

const { formatDateTime } = useDateFormat();

const { items, page, totalPages, search: searchInput, onSearch, goToPage, reload: reset } = useListPage(
    props.listPath,
    { initialSearch: props.search, initialData: props.contacts },
);

const { showCreate, newContact, createErrors, createLoading, openCreate, submitCreate } = useContactsCreate(props.createPath, reset);
const { showEdit, editingContact, editForm, editErrors, editLoading, openEdit, submitEdit } = useContactsEdit(props.updatePath, reset);
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(props.deletePath, () => reset(), "backend.crm.contacts.deleted");

const { showShow, showingContact, activity, activityLoading, openShow, closeShow, activityActionLabel } = useContactsShow(props.activityPath);
const { flatTags } = useContactsTags(props.tagsPath);
</script>

<template>
    <div class="space-y-4">
        <AppListToolbar>
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('backend.crm.contacts.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <template #actions>
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
            </template>
        </AppListToolbar>

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
                        <div v-if="contact.source || (contact.tags && contact.tags.length)" class="flex flex-wrap gap-1 mt-1.5">
                            <AppBadge v-if="contact.source" :color="contactSourceColor(contact.source)">{{ t(`backend.crm.contacts.sources.${contact.source}`) }}</AppBadge>
                            <AppBadge
                                v-for="tag in (contact.tags ?? [])"
                                :key="tag.id"
                                :color="tag.color"
                            >
                                {{ tag.label }}
                            </AppBadge>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <p class="text-xs text-muted truncate">{{ contact.email ?? contact.phone ?? '—' }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton color="sky" :title="t('shared.common.view')" v-on:click="openShow(contact)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
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
                                <div class="min-w-0">
                                    <p class="font-medium text-primary truncate">{{ contact.fullName }}</p>
                                    <div v-if="contact.source || (contact.tags && contact.tags.length)" class="flex flex-wrap gap-1 mt-1">
                                        <AppBadge v-if="contact.source" :color="contactSourceColor(contact.source)">{{ t(`backend.crm.contacts.sources.${contact.source}`) }}</AppBadge>
                                        <AppBadge
                                            v-for="tag in (contact.tags ?? [])"
                                            :key="tag.id"
                                            :color="tag.color"
                                        >
                                            {{ tag.label }}
                                        </AppBadge>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary">{{ contact.email ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ contact.company ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ contact.phone ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton color="sky" :title="t('shared.common.view')" v-on:click="openShow(contact)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
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
            :show="showShow"
            :title="showingContact?.fullName ?? ''"
            :icon="Users"
            v-on:close="closeShow"
        >
            <div v-if="showingContact" class="space-y-4">
                <div class="flex items-center gap-3">
                    <AppAvatar
                        :first-name="showingContact.firstName"
                        :last-name="showingContact.lastName"
                        :name="showingContact.fullName"
                        :email="showingContact.email"
                        size="lg"
                    />
                    <div class="min-w-0">
                        <p class="font-medium text-primary">{{ showingContact.fullName }}</p>
                        <p v-if="showingContact.company" class="text-sm text-secondary truncate">{{ showingContact.company }}</p>
                    </div>
                </div>

                <div v-if="showingContact.source || (showingContact.tags && showingContact.tags.length)" class="flex flex-wrap gap-1.5">
                    <AppBadge v-if="showingContact.source" :color="contactSourceColor(showingContact.source)">{{ t(`backend.crm.contacts.sources.${showingContact.source}`) }}</AppBadge>
                    <AppBadge
                        v-for="tag in (showingContact.tags ?? [])"
                        :key="tag.id"
                        :color="tag.color"
                    >
                        {{ tag.label }}
                    </AppBadge>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-line/40">
                    <div v-if="showingContact.email" class="min-w-0">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.contacts.email') }}</dt>
                        <dd class="text-sm"><AppLink :href="`mailto:${showingContact.email}`" class="text-accent-400 hover:underline break-all">{{ showingContact.email }}</AppLink></dd>
                    </div>
                    <div v-if="showingContact.phone">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.contacts.phone') }}</dt>
                        <dd class="text-sm text-primary">{{ showingContact.phone }}</dd>
                    </div>
                    <div v-if="showingContact.company">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.contacts.company') }}</dt>
                        <dd class="text-sm text-primary">{{ showingContact.company }}</dd>
                    </div>
                    <div v-if="showingContact.notes" class="sm:col-span-2">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.contacts.notes') }}</dt>
                        <dd class="text-sm text-secondary whitespace-pre-wrap break-words">{{ showingContact.notes }}</dd>
                    </div>
                </dl>

                <div class="pt-3 border-t border-line/40 space-y-2">
                    <h4 class="text-xs text-muted uppercase tracking-wide">{{ t('backend.crm.activity.title') }}</h4>
                    <p v-if="activityLoading" class="text-xs text-muted">{{ t('shared.common.loading') }}</p>
                    <p v-else-if="!activity.length" class="text-xs text-muted">{{ t('backend.crm.activity.empty') }}</p>
                    <ol v-else class="relative border-l border-line ml-2 space-y-3">
                        <li v-for="event in activity" :key="event.id" class="ml-3">
                            <div class="absolute w-2 h-2 bg-accent-600 rounded-full -left-[5px] border-2 border-bg" />
                            <p class="text-sm text-primary">{{ activityActionLabel(event.action) }}</p>
                            <p class="text-xs text-secondary">
                                <span v-if="event.userName">{{ event.userName }}</span>
                                <span v-if="event.userName && event.userEmail" class="text-muted"> · </span>
                                <span v-if="event.userEmail" class="text-muted">{{ event.userEmail }}</span>
                            </p>
                            <time class="text-xs text-muted">{{ formatDateTime(event.createdAt) }}</time>
                        </li>
                    </ol>
                </div>
            </div>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="closeShow"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.close') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

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
                <AppMultiselect
                    v-model="newContact.tagIds"
                    :options="flatTags"
                    :label="t('backend.crm.contacts.tags')"
                    :placeholder="t('backend.crm.contacts.tagsPlaceholder')"
                    track-by="id"
                    multiple
                />
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
                <AppMultiselect
                    v-model="editForm.tagIds"
                    :options="flatTags"
                    :label="t('backend.crm.contacts.tags')"
                    :placeholder="t('backend.crm.contacts.tagsPlaceholder')"
                    track-by="id"
                    multiple
                />
                <div v-if="editingContact?.source" class="text-xs text-muted">
                    {{ t('backend.crm.contacts.sourceLabel') }}
                    <AppBadge :color="contactSourceColor(editingContact.source)">{{ t(`backend.crm.contacts.sources.${editingContact.source}`) }}</AppBadge>
                </div>
                <AppTextarea v-model="editForm.notes" :rows="3" :placeholder="t('backend.crm.contacts.notesPlaceholder')" />
            </form>
            <template #footer>
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </template>
        </AppModal>

        <AppModal
            :show="!!pendingDelete"
            max-width="sm"
            :closeable="false"
            :title="t('shared.common.delete')"
            :icon="Trash2"
            v-on:close="pendingDelete = null"
        >
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
