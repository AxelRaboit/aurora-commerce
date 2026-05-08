<script setup>
import { ref } from "vue";
import { useI18n } from "vue-i18n";
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
import { Pencil, Trash2, Save, X } from "lucide-vue-next";
import { required, email as emailValidator } from "@/shared/utils/validation/validators.js";
import { toast } from "vue-sonner";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

const { t } = useI18n();
const { formatDateTime } = useDateFormat();

const props = defineProps({
    contact: { type: Object, required: true },
    activity: { type: Array, default: () => [] },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
    backPath: { type: String, required: true },
});

const contact = ref({ ...props.contact });

// Edit
const showEdit = ref(false);
const editForm = ref({
    firstName: contact.value.firstName,
    lastName: contact.value.lastName,
    email: contact.value.email ?? "",
    phone: contact.value.phone ?? "",
    company: contact.value.company ?? "",
    notes: contact.value.notes ?? "",
});
const { errors: editErrors, validate: validateEdit, clearErrors: clearEdit, setErrors: setEditErrors } = useForm();
const { loading: editLoading, request: editRequest } = useApiRequest();

async function submitEdit() {
    if (!validateEdit({
        firstName: () => required(t("backend.crm.contacts.errors.first_name_required"))(editForm.value.firstName),
        lastName: () => required(t("backend.crm.contacts.errors.last_name_required"))(editForm.value.lastName),
        email: () => editForm.value.email ? emailValidator(t("backend.crm.contacts.errors.email_invalid"))(editForm.value.email) : null,
    })) return;

    const data = await editRequest(props.updatePath, editForm.value);
    if (!data) return;
    if (data.success) {
        contact.value = { ...contact.value, ...(data.contact ?? editForm.value) };
        showEdit.value = false;
        toast.success(t("shared.common.saved"));
    } else {
        setEditErrors(translateServerErrors(t, data.errors));
    }
}

// Delete
const { showDelete, loading: deleteLoading, submit: doDelete } = useDetailDelete(props.deletePath, props.backPath);

const actionLabel = (action) => {
    const map = { 'contact.created': t('backend.crm.activity.created'), 'contact.updated': t('backend.crm.activity.updated'), 'contact.deleted': t('backend.crm.activity.deleted') };
    return map[action] ?? action;
};
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-surface border border-line rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-6">
                    <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                        <AppAvatar
                            :first-name="contact.firstName"
                            :last-name="contact.lastName"
                            :name="contact.fullName"
                            :email="contact.email"
                            size="xl"
                            class="sm:w-14 sm:h-14 sm:text-xl"
                        />
                        <div class="min-w-0">
                            <h2 class="text-lg sm:text-xl font-bold text-primary break-words">{{ contact.fullName }}</h2>
                            <p v-if="contact.company" class="text-sm text-secondary truncate">{{ contact.company }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 sm:gap-2 sm:shrink-0 self-end sm:self-auto">
                        <AppIconButton color="accent" :title="t('shared.common.edit')" v-on:click="showEdit = true"><Pencil class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                        <AppIconButton color="rose" :title="t('shared.common.delete')" v-on:click="showDelete = true"><Trash2 class="w-4 h-4" :stroke-width="2" /></AppIconButton>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div v-if="contact.email" class="min-w-0">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.crm.contacts.email') }}</dt>
                        <dd><AppLink :href="`mailto:${contact.email}`" class="text-accent-400 hover:underline break-all">{{ contact.email }}</AppLink></dd>
                    </div>
                    <div v-if="contact.phone">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.crm.contacts.phone') }}</dt>
                        <dd class="text-primary">{{ contact.phone }}</dd>
                    </div>
                    <div v-if="contact.company">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.crm.contacts.company') }}</dt>
                        <dd class="text-primary">{{ contact.company }}</dd>
                    </div>
                    <div v-if="contact.notes" class="sm:col-span-2">
                        <dt class="text-xs text-muted uppercase tracking-wide mb-1">{{ t('backend.crm.contacts.notes') }}</dt>
                        <dd class="text-secondary text-sm whitespace-pre-wrap break-words">{{ contact.notes }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="text-sm font-semibold text-primary uppercase tracking-wide">{{ t('backend.crm.activity.title') }}</h3>

            <div v-if="!activity.length" class="text-sm text-muted">{{ t('backend.crm.activity.empty') }}</div>

            <ol v-else class="relative border-l border-line ml-3 space-y-6">
                <li v-for="event in activity" :key="event.id" class="ml-4">
                    <div class="absolute w-2.5 h-2.5 bg-accent-600 rounded-full -left-1.5 border-2 border-bg" />
                    <p class="text-sm font-medium text-primary">{{ actionLabel(event.action) }}</p>
                    <p class="text-xs text-secondary">
                        <span v-if="event.userName">{{ event.userName }}</span>
                        <span v-if="event.userName && event.userEmail" class="text-muted"> · </span>
                        <span v-if="event.userEmail" class="text-muted">{{ event.userEmail }}</span>
                    </p>
                    <time class="text-xs text-muted">{{ formatDateTime(event.createdAt) }}</time>
                </li>
            </ol>
        </div>

        <AppModal :show="showEdit" :title="t('backend.crm.contacts.edit', { name: contact.fullName })" v-on:close="showEdit = false">
            <form class="space-y-4" v-on:submit.prevent="submitEdit">
                <div class="grid grid-cols-2 gap-3">
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
                <AppModalFooter>
                    <AppButton variant="ghost" size="md" type="button" v-on:click="showEdit = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                    <AppButton variant="primary" size="md" type="submit" :loading="editLoading"><Save class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.save') }}</AppButton>
                </AppModalFooter>
            </form>
        </AppModal>

        <AppModal :show="showDelete" max-width="sm" v-on:close="showDelete = false">
            <p class="text-sm text-primary">{{ t('backend.crm.contacts.deleteConfirm', { name: contact.fullName }) }}</p>
            <p class="text-sm text-secondary">{{ t('backend.crm.contacts.deleteWarning') }}</p>
            <AppModalFooter>
                <AppButton variant="ghost" size="md" v-on:click="showDelete = false"><X class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.cancel') }}</AppButton>
                <AppButton variant="danger" size="md" :loading="deleteLoading" v-on:click="doDelete"><Trash2 class="w-3.5 h-3.5" :stroke-width="2" /> {{ t('shared.common.delete') }}</AppButton>
            </AppModalFooter>
        </AppModal>
    </div>
</template>
