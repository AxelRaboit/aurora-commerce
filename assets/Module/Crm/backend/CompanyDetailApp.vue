<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { usePrivileges } from "@/shared/composables/usePrivileges.js";
import { useApiRequest } from "@/shared/composables/api/useApiRequest.js";
import { useDetailDelete } from "@/shared/composables/form/useDetailDelete.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { useDateFormat } from "@/shared/composables/format/useDateFormat.js";
import AppButton from "@/shared/components/action/AppButton.vue";
import AppIconButton from "@/shared/components/action/AppIconButton.vue";
import AppInput from "@/shared/components/form/AppInput.vue";
import AppTextarea from "@/shared/components/form/AppTextarea.vue";
import AppLink from "@/shared/components/nav/AppLink.vue";
import AppAvatar from "@/shared/components/display/AppAvatar.vue";
import AppModal from "@/shared/components/overlay/AppModal.vue";
import AppModalFooter from "@/shared/components/overlay/AppModalFooter.vue";
import { Pencil, Trash2, Plus, Save, X } from "lucide-vue-next";
import { required, url, email as emailValidator } from "@/shared/utils/validation/validators.js";
import { toast } from "vue-sonner";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

const { t } = useI18n();
const { can } = usePrivileges();
const { formatDateShort } = useDateFormat();

const props = defineProps({
    company: { type: Object, required: true },
    contacts: { type: Array, default: () => [] },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    backPath: { type: String, required: true },
    contactsListPath: { type: String, required: true },
    createContactPath: { type: String, default: "" },
    companyId: { type: Number, default: null },
});

// Local reactive copies so save responses can update the UI without full page reload.
const company = ref({ ...props.company });
const contacts = ref([...props.contacts]);

// Edit
const showEdit = ref(false);
const editForm = ref({
    name: company.value.name,
    industry: company.value.industry ?? "",
    website: company.value.website ?? "",
    phone: company.value.phone ?? "",
    address: company.value.address ?? "",
    notes: company.value.notes ?? "",
});
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

async function submitEdit() {
    if (!validateEdit({
        name: () => required(t("backend.crm.companies.errors.name_required"))(editForm.value.name),
        website: () => url(t("backend.crm.companies.errors.website_invalid"))(editForm.value.website),
    })) return;
    const data = await editRequest(props.updatePath, editForm.value);
    if (!data) return;
    if (data.success) {
        company.value = { ...company.value, ...(data.company ?? editForm.value) };
        showEdit.value = false;
        toast.success(t("shared.common.saved"));
    } else {
        setEditErrors(translateServerErrors(t, data.errors));
    }
}

// Delete
const { showDelete, loading: deleteLoading, submit: doDelete } = useDetailDelete(props.deletePath, props.backPath);

// Create contact
const showCreateContact = ref(false);
const newContact = ref({ firstName: "", lastName: "", email: "", phone: "", notes: "" });
const { errors: contactErrors, validate: validateContact, clearErrors: clearContact, setErrors: setContactErrors } = useForm();
const { loading: contactLoading, request: contactRequest } = useApiRequest();
function openCreateContact() { newContact.value = { firstName: "", lastName: "", email: "", phone: "", notes: "" }; clearContact(); showCreateContact.value = true; }
async function submitContact() {
    if (!validateContact({
        firstName: () => required(t("backend.crm.contacts.errors.first_name_required"))(newContact.value.firstName),
        lastName: () => required(t("backend.crm.contacts.errors.last_name_required"))(newContact.value.lastName),
        email: () => newContact.value.email ? emailValidator(t("backend.crm.contacts.errors.email_invalid"))(newContact.value.email) : null,
    })) return;
    const data = await contactRequest(props.createContactPath, { ...newContact.value, companyId: props.companyId });
    if (!data) return;
    if (data.success) {
        showCreateContact.value = false;
        toast.success(t("backend.crm.contacts.created"));
        if (data.contact) contacts.value = [data.contact, ...contacts.value];
    } else {
        setContactErrors(translateServerErrors(t, data.errors));
    }
}
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-4">
            <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ company.name }}</h2>
                        <p v-if="company.industry" class="text-sm text-secondary truncate">{{ company.industry }}</p>
                    </div>
                    <div class="flex items-center gap-1 sm:gap-2 sm:shrink-0 self-end sm:self-auto">
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="showEdit = true"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDelete = true"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>

                <dl class="space-y-3">
                    <div v-if="company.website">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.companies.website') }}</dt>
                        <dd><AppLink :href="company.website" target="_blank" rel="noopener" class="text-accent-400 hover:underline text-sm break-all">{{ company.website }}</AppLink></dd>
                    </div>
                    <div v-if="company.phone">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.companies.phone') }}</dt>
                        <dd class="text-primary text-sm">{{ company.phone }}</dd>
                    </div>
                    <div v-if="company.address">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.companies.address') }}</dt>
                        <dd class="text-secondary text-sm">{{ company.address }}</dd>
                    </div>
                    <div v-if="company.notes">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-0.5">{{ t('backend.crm.contacts.notes') }}</dt>
                        <dd class="text-secondary text-sm whitespace-pre-wrap">{{ company.notes }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">
                    {{ t('backend.nav.contacts') }} ({{ contacts.length }})
                </h3>
                <AppButton
                    v-if="createContactPath && can('crm.contacts.create')"
                    variant="primary"
                    size="md"
                    class="w-full sm:w-auto"
                    v-on:click="openCreateContact"
                >
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ t('backend.crm.contacts.add') }}
                </AppButton>
            </div>

            <div v-if="!contacts.length" class="text-sm text-muted py-4">
                {{ t('backend.crm.companies.noContacts') }}
            </div>

            <template v-else>
                <div class="sm:hidden space-y-3">
                    <div v-for="contact in contacts" :key="contact.id" class="bg-surface border border-line rounded-lg p-4 space-y-2">
                        <div class="flex items-center gap-3">
                            <AppAvatar
                                :first-name="contact.firstName"
                                :last-name="contact.lastName"
                                :email="contact.email"
                                size="lg"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-primary truncate">{{ contact.firstName }} {{ contact.lastName }}</p>
                                <AppLink v-if="contact.email" :href="`mailto:${contact.email}`" class="text-xs text-muted hover:text-accent-400 transition-colors break-all">{{ contact.email }}</AppLink>
                                <p v-else-if="contact.phone" class="text-xs text-muted">{{ contact.phone }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="hidden sm:block bg-surface border border-line rounded-lg overflow-x-auto scrollbar-thin">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-surface-2/50 border-b border-line/40">
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.contacts.name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted">{{ t('backend.crm.contacts.email') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted hidden md:table-cell">{{ t('backend.crm.contacts.phone') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/40">
                            <tr v-for="contact in contacts" :key="contact.id" class="group hover:bg-surface-2/40 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <AppAvatar
                                            :first-name="contact.firstName"
                                            :last-name="contact.lastName"
                                            :email="contact.email"
                                            size="sm"
                                        />
                                        <span class="font-medium text-primary truncate">{{ contact.firstName }} {{ contact.lastName }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-secondary">
                                    <AppLink v-if="contact.email" :href="`mailto:${contact.email}`" class="hover:text-accent-400 transition-colors break-all">{{ contact.email }}</AppLink>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3 text-secondary hidden md:table-cell">{{ contact.phone ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>
        </div>

        <AppModal :show="showEdit" :title="t('backend.crm.companies.edit', { name: company.name })" v-on:close="showEdit = false">
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <AppInput
                    v-model="editForm.name"
                    :label="t('backend.crm.companies.name')"
                    :placeholder="t('backend.crm.companies.namePlaceholder')"
                    :error="editErrors.name"
                    required
                />
                <AppInput v-model="editForm.industry" :label="t('backend.crm.companies.industry')" :placeholder="t('backend.crm.companies.industryPlaceholder')" />
                <AppInput v-model="editForm.website" :label="t('backend.crm.companies.website')" :placeholder="t('backend.crm.companies.websitePlaceholder')" :error="editErrors.website" />
                <AppInput v-model="editForm.phone" :label="t('backend.crm.companies.phone')" :placeholder="t('backend.crm.companies.phonePlaceholder')" />
                <AppInput v-model="editForm.address" :label="t('backend.crm.companies.address')" :placeholder="t('backend.crm.companies.addressPlaceholder')" />
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="showDelete" max-width="sm" v-on:close="showDelete = false">
            <p class="text-sm text-primary">{{ t('backend.crm.companies.deleteConfirm', { name: company.name }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.crm.companies.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDelete = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
    <AppModal :show="showCreateContact" :title="t('backend.crm.contacts.create')" v-on:close="showCreateContact = false">
        <form class="space-y-4" v-on:submit.prevent="submitContact">
            <div class="grid grid-cols-2 gap-3">
                <AppInput
                    v-model="newContact.firstName"
                    :label="t('backend.crm.contacts.firstName')"
                    :placeholder="t('backend.crm.contacts.firstNamePlaceholder')"
                    :error="contactErrors.firstName"
                    required
                />
                <AppInput
                    v-model="newContact.lastName"
                    :label="t('backend.crm.contacts.lastName')"
                    :placeholder="t('backend.crm.contacts.lastNamePlaceholder')"
                    :error="contactErrors.lastName"
                    required
                />
            </div>
            <AppInput
                v-model="newContact.email"
                type="email"
                :label="t('backend.crm.contacts.email')"
                :placeholder="t('backend.crm.contacts.emailPlaceholder')"
                :error="contactErrors.email"
            />
            <AppInput v-model="newContact.phone" :label="t('backend.crm.contacts.phone')" :placeholder="t('backend.crm.contacts.phonePlaceholder')" />
            <AppTextarea v-model="newContact.notes" :rows="2" :placeholder="t('backend.crm.contacts.notesPlaceholder')" />
            <p class="text-xs text-muted">{{ t('backend.crm.companies.contactLinked', { name: company.name }) }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" type="button" v-on:click="showCreateContact = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="primary" size="md" type="submit" :loading="contactLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
            </AppModalFooter>
        </form>
    </AppModal>
</template>
