<script setup>
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useListPage } from "@/shared/composables/useListPage.js";
import { useApiRequest } from "@/shared/composables/useApiRequest.js";
import { useDelete } from "@/shared/composables/useDelete.js";
import { useForm } from "@/shared/composables/useForm.js";
import AppButton from "@/shared/components/AppButton.vue";
import AppIconButton from "@/shared/components/AppIconButton.vue";
import AppInput from "@/shared/components/AppInput.vue";
import AppSearchInput from "@/shared/components/AppSearchInput.vue";
import AppTextarea from "@/shared/components/AppTextarea.vue";
import AppModal from "@/shared/components/AppModal.vue";
import AppModalFooter from "@/shared/components/AppModalFooter.vue";
import AppPagination from "@/shared/components/AppPagination.vue";
import AppNoData from "@/shared/components/AppNoData.vue";
import AppLink from "@/shared/components/AppLink.vue";
import { Plus, Pencil, Trash2, Eye } from "lucide-vue-next";
import { toast } from "vue-sonner";
import { required, email as emailValidator } from "@/shared/utils/validators.js";

const { t } = useI18n();

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

// --- Create ---
const showCreate = ref(false);
const newContact = ref(emptyForm());
const { errors: createErrors, validate: validateCreate, clearErrors: clearCreate, setErrors: setCreateErrors } = useForm();
const { loading: createLoading, request: createRequest } = useApiRequest();

function emptyForm() {
    return { firstName: "", lastName: "", email: "", phone: "", company: "", notes: "" };
}

function openCreate() {
    newContact.value = emptyForm();
    clearCreate();
    showCreate.value = true;
}

async function submitCreate() {
    if (!validateCreate({
        firstName: () => required(t("admin.crm.contacts.errors.first_name_required"))(newContact.value.firstName),
        lastName: () => required(t("admin.crm.contacts.errors.last_name_required"))(newContact.value.lastName),
        email: () => newContact.value.email ? emailValidator(t("admin.crm.contacts.errors.email_invalid"))(newContact.value.email) : null,
    })) return;

    const data = await createRequest(props.createPath, newContact.value);
    if (!data) return;
    if (data.success) { showCreate.value = false; toast.success(t('admin.crm.contacts.created')); reset(); }
    else setCreateErrors(data.errors ?? {});
}

// --- Edit ---
const showEdit = ref(false);
const editingContact = ref(null);
const editForm = ref(emptyForm());
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

function openEdit(contact) {
    editingContact.value = contact;
    editForm.value = {
        firstName: contact.firstName,
        lastName: contact.lastName,
        email: contact.email ?? "",
        phone: contact.phone ?? "",
        company: contact.company ?? "",
        notes: contact.notes ?? "",
    };
    clearEdit();
    showEdit.value = true;
}

async function submitEdit() {
    if (!validateEdit({
        firstName: () => required(t("admin.crm.contacts.errors.first_name_required"))(editForm.value.firstName),
        lastName: () => required(t("admin.crm.contacts.errors.last_name_required"))(editForm.value.lastName),
        email: () => editForm.value.email ? emailValidator(t("admin.crm.contacts.errors.email_invalid"))(editForm.value.email) : null,
    })) return;

    const url = props.updatePath.replace("__id__", editingContact.value.id);
    const data = await editRequest(url, editForm.value);
    if (!data) return;
    if (data.success) { showEdit.value = false; toast.success(t('admin.crm.contacts.updated')); reset(); }
    else setEditErrors(data.errors ?? {});
}

// --- Delete ---
const { pendingDelete, loading: deleteLoading, confirm: confirmDelete, submit: doDelete } = useDelete(
    props.deletePath, () => reset(), 'admin.crm.contacts.deleted',
);

const avatar = (contact) => (contact.firstName?.[0] ?? "") + (contact.lastName?.[0] ?? "");
</script>

<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-2">
            <AppSearchInput
                v-model="searchInput"
                :placeholder="t('admin.crm.contacts.searchPlaceholder')"
                v-on:search="onSearch"
            />
            <AppButton variant="primary" size="md" class="w-full sm:w-auto" v-on:click="openCreate">
                <Plus class="w-4 h-4" :stroke-width="2" />
                {{ t('admin.crm.contacts.add') }}
            </AppButton>
        </div>

        <!-- Empty -->
        <!-- Mobile cards -->
        <div class="sm:hidden space-y-3">
            <div v-for="contact in items" :key="contact.id" class="bg-surface border border-line rounded-lg p-4 space-y-3">
                <div class="flex items-start gap-3">
                    <div class="w-9 h-9 rounded-full bg-accent-600/20 text-accent-400 flex items-center justify-center text-sm font-bold shrink-0 uppercase">
                        {{ avatar(contact) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-primary">{{ contact.fullName }}</p>
                        <p v-if="contact.company" class="text-xs text-muted mt-0.5">{{ contact.company }}</p>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-line">
                    <p class="text-xs text-muted truncate">{{ contact.email ?? contact.phone ?? '—' }}</p>
                    <div class="flex items-center gap-0.5">
                        <AppIconButton v-if="showPath" color="sky" :href="showPath.replace('__id__', contact.id)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(contact)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(contact)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop table -->
        <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 border-b border-line">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.crm.contacts.name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('admin.crm.contacts.email') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('admin.crm.contacts.company') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden lg:table-cell">{{ t('admin.crm.contacts.phone') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted">{{ t('shared.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="contact in items" :key="contact.id" class="hover:bg-surface-2/50 transition-colors">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-accent-600/20 text-accent-400 flex items-center justify-center text-xs font-bold shrink-0 uppercase">
                                    {{ avatar(contact) }}
                                </div>
                                <span class="font-medium text-primary">{{ contact.fullName }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-secondary">{{ contact.email ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden md:table-cell">{{ contact.company ?? '—' }}</td>
                        <td class="px-6 py-3 text-secondary hidden lg:table-cell">{{ contact.phone ?? '—' }}</td>
                        <td class="px-6 py-3">
                            <div class="flex items-center justify-end gap-0.5">
                                <AppIconButton v-if="showPath" color="sky" :href="showPath.replace('__id__', contact.id)"><Eye class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="openEdit(contact)"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                                <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="confirmDelete(contact)"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!items?.length">
                        <td :colspan="5" class="px-6 py-8 text-center text-sm text-muted">{{ t('admin.crm.contacts.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination v-if="totalPages > 1" :page="page" :total-pages="totalPages" v-on:go-to-page="goToPage" />

        <!-- Create modal -->
        <AppModal :show="showCreate" v-on:close="showCreate = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.crm.contacts.create') }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitCreate">
                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="newContact.firstName"
                        :label="t('admin.crm.contacts.firstName')"
                        :placeholder="t('admin.crm.contacts.firstNamePlaceholder')"
                        :error="createErrors.firstName"
                        required
                    />
                    <AppInput
                        v-model="newContact.lastName"
                        :label="t('admin.crm.contacts.lastName')"
                        :placeholder="t('admin.crm.contacts.lastNamePlaceholder')"
                        :error="createErrors.lastName"
                        required
                    />
                </div>
                <AppInput
                    v-model="newContact.email"
                    type="email"
                    :label="t('admin.crm.contacts.email')"
                    :placeholder="t('admin.crm.contacts.emailPlaceholder')"
                    :error="createErrors.email"
                />
                <AppInput v-model="newContact.phone" :label="t('admin.crm.contacts.phone')" :placeholder="t('admin.crm.contacts.phonePlaceholder')" />
                <AppInput v-model="newContact.company" :label="t('admin.crm.contacts.company')" :placeholder="t('admin.crm.contacts.companyPlaceholder')" />
                <AppTextarea v-model="newContact.notes" :rows="3" :placeholder="t('admin.crm.contacts.notesPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showCreate = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="createLoading">{{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Edit modal -->
        <AppModal :show="showEdit" v-on:close="showEdit = false">
            <h3 class="text-lg font-semibold text-primary">{{ t('admin.crm.contacts.edit', { name: editingContact?.fullName ?? '' }) }}</h3>
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="editForm.firstName"
                        :label="t('admin.crm.contacts.firstName')"
                        :placeholder="t('admin.crm.contacts.firstNamePlaceholder')"
                        :error="editErrors.firstName"
                        required
                    />
                    <AppInput
                        v-model="editForm.lastName"
                        :label="t('admin.crm.contacts.lastName')"
                        :placeholder="t('admin.crm.contacts.lastNamePlaceholder')"
                        :error="editErrors.lastName"
                        required
                    />
                </div>
                <AppInput
                    v-model="editForm.email"
                    type="email"
                    :label="t('admin.crm.contacts.email')"
                    :placeholder="t('admin.crm.contacts.emailPlaceholder')"
                    :error="editErrors.email"
                />
                <AppInput v-model="editForm.phone" :label="t('admin.crm.contacts.phone')" :placeholder="t('admin.crm.contacts.phonePlaceholder')" />
                <AppInput v-model="editForm.company" :label="t('admin.crm.contacts.company')" :placeholder="t('admin.crm.contacts.companyPlaceholder')" />
                <AppTextarea v-model="editForm.notes" :rows="3" :placeholder="t('admin.crm.contacts.notesPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false">{{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading">{{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <!-- Delete confirm -->
        <AppModal :show="!!pendingDelete" max-width="sm" v-on:close="pendingDelete = null">
            <p class="text-sm text-primary">{{ t('admin.crm.contacts.deleteConfirm', { name: pendingDelete?.fullName ?? '' }) }}</p>
            <p class="text-sm text-secondary">{{ t('admin.crm.contacts.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="pendingDelete = null">{{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete">{{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
