import { ref, reactive, watch } from "vue";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useFormModal } from "@/shared/composables/form/useFormModal.js";
import { PostFieldType } from "@editorial/shared/enums/postFieldType.js";
import { useRequest } from "@/shared/composables/http/useRequest.js";

export function usePostTypeFields(props, selected, replacePostType) {
    const { t } = useI18n();
    const { request: deleteFieldRequest } = useRequest();
    const { request: reorderRequest } = useRequest();
    const {
        modal: fieldModal,
        openCreate: fieldModalCreate,
        openEdit: fieldModalEdit,
        submit: fieldModalSubmit,
    } = useFormModal();
    const fieldForm = reactive({
        name: "",
        label: "",
        type: "text",
        required: false,
        translatable: false,
        choicesText: "",
        referencePostTypeId: null,
        referenceMultiple: false,
    });

    function openCreateField() {
        if (!selected.value) return;
        fieldModalCreate(() =>
            Object.assign(fieldForm, {
                name: "",
                label: "",
                type: "text",
                required: false,
                translatable: false,
                choicesText: "",
                referencePostTypeId: null,
                referenceMultiple: false,
            }),
        );
    }

    function openEditField(field) {
        fieldModalEdit(field, (f) => {
            const options = f.options ?? {};
            Object.assign(fieldForm, {
                name: f.name,
                label: f.label,
                type: f.type,
                required: f.required,
                translatable: f.translatable,
                choicesText: (options.choices ?? [])
                    .map((c) => `${c.value}|${c.label}`)
                    .join("\n"),
                referencePostTypeId: options.postTypeId ?? null,
                referenceMultiple: options.multiple ?? false,
            });
        });
    }

    function buildFieldOptions() {
        if (fieldForm.type === PostFieldType.Select) {
            return {
                choices: fieldForm.choicesText
                    .split("\n")
                    .map((l) => l.trim())
                    .filter(Boolean)
                    .map((l) => {
                        const [value, ...rest] = l.split("|");
                        return {
                            value: value.trim(),
                            label: rest.join("|").trim() || value.trim(),
                        };
                    }),
            };
        }
        if (fieldForm.type === PostFieldType.Reference) {
            const options = { multiple: fieldForm.referenceMultiple };
            if (fieldForm.referencePostTypeId)
                options.postTypeId = Number(fieldForm.referencePostTypeId);
            return options;
        }
        return {};
    }

    async function submitField() {
        if (!selected.value) return;
        const url = fieldModal.editing
            ? buildPath(props.fieldEditPath, {
                  id: selected.value.id,
                  fieldId: fieldModal.editing.id,
              })
            : buildPath(props.fieldCreatePath, { id: selected.value.id });
        await fieldModalSubmit(
            url,
            {
                name: fieldForm.name,
                label: fieldForm.label,
                type: fieldForm.type,
                required: fieldForm.required,
                translatable: fieldForm.translatable,
                options: buildFieldOptions(),
            },
            (data) => replacePostType(data.postType),
        );
    }

    const deletingField = ref(null);
    async function confirmDeleteField() {
        const field = deletingField.value;
        if (!field || !selected.value) return;
        try {
            const url = buildPath(props.fieldDeletePath, {
                id: selected.value.id,
                fieldId: field.id,
            });
            const data = await deleteFieldRequest(url);
            if (!data) return;
            if (!data.success) {
                toast.error(t("shared.common.error"));
                return;
            }
            replacePostType(data.postType);
            toast.success(t("shared.common.deleted"));
        } finally {
            deletingField.value = null;
        }
    }

    const orderedFields = ref([]);
    watch(
        () => selected.value?.fields,
        (fields) => {
            orderedFields.value = [...(fields ?? [])].sort(
                (a, b) => a.position - b.position,
            );
        },
        { immediate: true, deep: true },
    );

    async function persistFieldOrder() {
        if (!selected.value) return;
        const data = await reorderRequest(
            buildPath(props.fieldReorderPath, { id: selected.value.id }),
            { orderedIds: orderedFields.value.map((f) => f.id) },
        );
        if (!data) return;
        if (!data.success) toast.error(t("shared.common.error"));
        else replacePostType(data.postType);
    }

    return {
        fieldModal,
        fieldForm,
        openCreateField,
        openEditField,
        submitField,
        deletingField,
        confirmDeleteField,
        orderedFields,
        persistFieldOrder,
    };
}
