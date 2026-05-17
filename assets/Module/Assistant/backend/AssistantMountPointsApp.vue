<script setup>
import { computed, reactive, ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { Plus, Pencil, Trash2, FolderKey } from "lucide-vue-next";
import AppButton from "@shared/components/action/AppButton.vue";
import AppInput from "@shared/components/form/input/AppInput.vue";
import AppSelect from "@shared/components/form/select/AppSelect.vue";
import AppToggle from "@shared/components/form/toggle/AppToggle.vue";
import { useRequest } from "@shared/composables/http/backend/useRequest.js";
import { HttpMethod } from "@/shared/utils/http/httpMethod.js";

const props = defineProps({
    mountPoints: { type: Array, required: true },
    listPath: { type: String, required: true },
    createPath: { type: String, required: true },
    updatePath: { type: String, required: true },
    deletePath: { type: String, required: true },
});

const { t } = useI18n();
const { request } = useRequest();

const items = ref([...props.mountPoints]);
const editingId = ref(null);
const form = reactive({
    name: "",
    path: "",
    access: "read_only",
    active: true,
});
const errors = reactive({ name: "", path: "" });

const accessOptions = computed(() => [
    { value: "read_only", label: t("assistant.mount_point.access.read_only") },
    { value: "read_write", label: t("assistant.mount_point.access.read_write") },
]);

const isEditing = computed(() => editingId.value !== null);

function resetForm() {
    form.name = "";
    form.path = "";
    form.access = "read_only";
    form.active = true;
    errors.name = "";
    errors.path = "";
    editingId.value = null;
}

function startEdit(item) {
    editingId.value = item.id;
    form.name = item.name;
    form.path = item.path;
    form.access = item.access;
    form.active = item.active;
    errors.name = "";
    errors.path = "";
}

async function refresh() {
    const res = await request(props.listPath, null, HttpMethod.Get);
    if (res?.success) {
        items.value = res.data.mountPoints;
    }
}

async function save() {
    errors.name = "";
    errors.path = "";
    if (!form.name.trim()) errors.name = t("assistant.mount_point.errors.name_required");
    if (!form.path.trim()) errors.path = t("assistant.mount_point.errors.path_required");
    else if (!form.path.startsWith("/")) errors.path = t("assistant.mount_point.errors.path_absolute");
    if (errors.name || errors.path) return;

    const url = isEditing.value
        ? props.updatePath.replace("__id__", String(editingId.value))
        : props.createPath;

    const res = await request(url, {
        name: form.name,
        path: form.path,
        access: form.access,
        active: form.active,
    });

    if (res?.success) {
        resetForm();
        await refresh();
    } else if (res?.errors) {
        if (res.errors.name) errors.name = res.errors.name[0];
        if (res.errors.path) errors.path = res.errors.path[0];
    } else {
        toast.error("Save failed");
    }
}

async function remove(item) {
    if (!window.confirm(t("assistant.mount_point.delete_confirm"))) return;
    const res = await request(props.deletePath.replace("__id__", String(item.id)));
    if (res?.success) {
        if (editingId.value === item.id) resetForm();
        await refresh();
    }
}
</script>

<template>
    <div class="space-y-6">
        <header class="flex items-center gap-3">
            <FolderKey class="w-6 h-6 text-accent-500" :stroke-width="1.5" />
            <div>
                <h1 class="text-lg font-semibold text-primary">{{ t('assistant.mount_point.title') }}</h1>
                <p class="text-sm text-secondary">{{ t('assistant.mount_point.description') }}</p>
            </div>
        </header>

        <section class="rounded-xl border border-line bg-surface p-4">
            <h2 class="text-sm font-semibold text-primary mb-3">
                {{ isEditing ? t('assistant.mount_point.edit') : t('assistant.mount_point.add') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <AppInput
                    v-model="form.name"
                    :label="t('assistant.mount_point.fields.name')"
                    :placeholder="t('assistant.mount_point.placeholder.name')"
                    :error="errors.name"
                    required
                />
                <AppInput
                    v-model="form.path"
                    :label="t('assistant.mount_point.fields.path')"
                    :placeholder="t('assistant.mount_point.placeholder.path')"
                    :error="errors.path"
                    required
                />
                <AppSelect
                    v-model="form.access"
                    :label="t('assistant.mount_point.fields.access')"
                    :options="accessOptions"
                />
                <div class="flex items-end gap-3 pb-1">
                    <label class="flex items-center gap-2 text-sm text-secondary">
                        <AppToggle v-model="form.active" />
                        {{ t('assistant.mount_point.fields.active') }}
                    </label>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <AppButton variant="primary" size="sm" v-on:click="save">
                    <Plus class="w-4 h-4" :stroke-width="2" />
                    {{ isEditing ? t('assistant.mount_point.edit') : t('assistant.mount_point.add') }}
                </AppButton>
                <AppButton v-if="isEditing" variant="ghost" size="sm" v-on:click="resetForm">
                    ✕
                </AppButton>
            </div>
        </section>

        <section v-if="items.length === 0" class="text-sm text-muted italic">
            {{ t('assistant.mount_point.empty') }}
        </section>

        <section v-else class="rounded-xl border border-line bg-surface overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-surface-2 text-xs uppercase tracking-wide text-muted">
                    <tr>
                        <th class="text-left px-3 py-2">{{ t('assistant.mount_point.fields.name') }}</th>
                        <th class="text-left px-3 py-2">{{ t('assistant.mount_point.fields.path') }}</th>
                        <th class="text-left px-3 py-2">{{ t('assistant.mount_point.fields.access') }}</th>
                        <th class="text-left px-3 py-2">{{ t('assistant.mount_point.fields.active') }}</th>
                        <th class="px-3 py-2" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="item in items" :key="item.id">
                        <td class="px-3 py-2 font-medium text-primary">{{ item.name }}</td>
                        <td class="px-3 py-2 font-mono text-xs text-secondary">{{ item.path }}</td>
                        <td class="px-3 py-2 text-secondary">{{ t(`assistant.mount_point.access.${item.access}`) }}</td>
                        <td class="px-3 py-2">
                            <span
                                class="inline-block w-2 h-2 rounded-full"
                                :class="item.active ? 'bg-emerald-500' : 'bg-line'"
                            />
                        </td>
                        <td class="px-3 py-2 text-right">
                            <div class="inline-flex gap-1">
                                <button
                                    type="button"
                                    class="text-muted hover:text-accent-500"
                                    :title="t('assistant.mount_point.edit')"
                                    v-on:click="startEdit(item)"
                                >
                                    <Pencil class="w-4 h-4" :stroke-width="1.5" />
                                </button>
                                <button
                                    type="button"
                                    class="text-muted hover:text-red-500"
                                    :title="t('assistant.mount_point.delete')"
                                    v-on:click="remove(item)"
                                >
                                    <Trash2 class="w-4 h-4" :stroke-width="1.5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>
