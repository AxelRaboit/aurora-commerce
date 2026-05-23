import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";
import { buildPath } from "@/shared/utils/http/buildPath.js";

export const TEMPLATE_STATUS_BADGE = {
    draft: "gray",
    active: "emerald",
    archived: "accent",
};

function emptyForm() {
    return {
        name: "",
        description: "",
        status: "draft",
        fileId: null,
        fileName: null,
        flattenOnGenerate: false,
        requiresSignature: false,
        autoDetectFields: false,
    };
}

export function usePdfTemplatesForm(
    createPath,
    updatePath,
    deletePath,
    detectFieldsPath,
    updateFieldPath,
    reset,
) {
    const { t } = useI18n();

    const statusOptions = [
        { value: "draft", label: t("backend.welding.pdf_templates.status_draft") },
        {
            value: "active",
            label: t("backend.welding.pdf_templates.status_active"),
        },
        {
            value: "archived",
            label: t("backend.welding.pdf_templates.status_archived"),
        },
    ];

    // ── Create ────────────────────────────────────────────────────────────────
    const showCreate = ref(false);
    const newTemplate = ref(emptyForm());
    const showMediaPickerCreate = ref(false);
    const {
        errors: createErrors,
        validate: validateCreate,
        clearErrors: clearCreate,
        setErrors: setCreateErrors,
    } = useForm();
    const { loading: createLoading, request: createRequest } = useRequest();
    const { request: detectAfterCreateRequest } = useRequest();

    function openCreate() {
        newTemplate.value = emptyForm();
        clearCreate();
        showCreate.value = true;
    }

    function onFilePickedCreate(media) {
        newTemplate.value.fileId = media.id;
        newTemplate.value.fileName = media.originalName ?? media.fileName;
        showMediaPickerCreate.value = false;
    }

    async function submitCreate() {
        if (
            !validateCreate({
                name: () =>
                    required(
                        t("backend.welding.pdf_templates.errors.name_required"),
                    )(newTemplate.value.name),
            })
        )
            return;
        const autoDetect = newTemplate.value.autoDetectFields;
        const data = await createRequest(createPath, { ...newTemplate.value });
        if (!data) return;
        if (data.success) {
            showCreate.value = false;
            toast.success(t("backend.welding.pdf_templates.create"));
            reset();
            if (autoDetect && data.template?.id && newTemplate.value.fileId) {
                // Déclencher la détection automatique sur le template nouvellement créé
                const path = buildPath(detectFieldsPath, {
                    id: data.template.id,
                });
                const detectionData = await detectAfterCreateRequest(path);
                if (detectionData?.success) {
                    const count = detectionData.template?.fields?.length ?? 0;
                    toast.success(
                        count > 0
                            ? t(
                                  "backend.welding.pdf_templates.detectFieldsSuccess",
                                  { count },
                              )
                            : t("backend.welding.pdf_templates.detectFieldsEmpty"),
                    );
                    reset();
                } else if (detectionData) {
                    toast.error(
                        detectionData.error ??
                            t("backend.pdfform.pdftk.unavailable"),
                    );
                }
            }
        } else {
            setCreateErrors(translateServerErrors(data.errors ?? {}));
        }
    }

    // ── Edit ─────────────────────────────────────────────────────────────────
    const showEdit = ref(false);
    const editingTemplate = ref(null);
    const editForm = ref(emptyForm());
    const showMediaPickerEdit = ref(false);
    const {
        errors: editErrors,
        validate: validateEdit,
        clearErrors: clearEdit,
        setErrors: setEditErrors,
    } = useForm();
    const { loading: editLoading, request: editRequest } = useRequest();

    function openEdit(template) {
        editingTemplate.value = template;
        editForm.value = {
            name: template.name,
            description: template.description ?? "",
            status: template.status,
            fileId: template.fileId ?? null,
            fileName: template.fileName ?? null,
            flattenOnGenerate: template.flattenOnGenerate ?? false,
            requiresSignature: template.requiresSignature ?? false,
        };
        clearEdit();
        showEdit.value = true;
    }

    function onFilePickedEdit(media) {
        editForm.value.fileId = media.id;
        editForm.value.fileName = media.originalName ?? media.fileName;
        showMediaPickerEdit.value = false;
    }

    async function submitEdit() {
        if (
            !validateEdit({
                name: () =>
                    required(
                        t("backend.welding.pdf_templates.errors.name_required"),
                    )(editForm.value.name),
            })
        )
            return;
        const path = buildPath(updatePath, { id: editingTemplate.value.id });
        const data = await editRequest(path, { ...editForm.value });
        if (!data) return;
        if (data.success) {
            showEdit.value = false;
            toast.success(
                t("backend.welding.pdf_templates.edit", {
                    name: editingTemplate.value.name,
                }),
            );
            reset();
        } else {
            setEditErrors(translateServerErrors(data.errors ?? {}));
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    const {
        pendingDelete,
        loading: deleteLoading,
        confirm: confirmDelete,
        submit: doDelete,
    } = useDelete(
        deletePath,
        () => reset(),
        "backend.welding.pdf_templates.deleted",
    );

    // ── Detect fields ─────────────────────────────────────────────────────────
    const showFields = ref(false);
    const fieldsTemplate = ref(null);
    const { loading: detectLoading, request: detectRequest } = useRequest();
    const editingField = ref(null);
    const fieldForm = ref({});
    const showEditField = ref(false);
    const { loading: fieldEditLoading, request: fieldEditRequest } =
        useRequest();
    const {
        errors: fieldErrors,
        validate: validateField,
        clearErrors: clearField,
    } = useForm();

    const fieldTypeOptions = [
        { value: "text", label: t("backend.welding.pdf_template_fields.type_text") },
        { value: "checkbox", label: t("backend.welding.pdf_template_fields.type_checkbox") },
        { value: "radio", label: t("backend.welding.pdf_template_fields.type_radio") },
        { value: "dropdown", label: t("backend.welding.pdf_template_fields.type_dropdown") },
        { value: "date", label: t("backend.welding.pdf_template_fields.type_date") },
        {
            value: "signature",
            label: t("backend.welding.pdf_template_fields.type_signature"),
        },
    ];

    function openFields(template) {
        fieldsTemplate.value = { ...template, fields: template.fields ?? [] };
        showFields.value = true;
    }

    async function detectFields() {
        if (!fieldsTemplate.value) return;
        const path = buildPath(detectFieldsPath, {
            id: fieldsTemplate.value.id,
        });
        const data = await detectRequest(path);
        if (!data) return;
        if (data.success) {
            // Forcer un nouvel objet pour garantir la réactivité Vue
            fieldsTemplate.value = {
                ...(data.template ?? {}),
                fields: data.template?.fields ?? [],
            };
            const count = fieldsTemplate.value.fields.length;
            toast.success(
                count > 0
                    ? t("backend.welding.pdf_templates.detectFieldsSuccess", {
                          count,
                      })
                    : t("backend.welding.pdf_templates.detectFieldsEmpty"),
            );
            reset(); // Recharge la liste pour mettre à jour le compteur "Champs"
        } else {
            toast.error(data.error ?? t("backend.pdfform.pdftk.unavailable"));
        }
    }

    function openEditField(field) {
        editingField.value = field;
        fieldForm.value = {
            pdfFieldName: field.pdfFieldName,
            label: field.label,
            fieldType: field.fieldType,
            mappingKey: field.mappingKey ?? "",
            defaultValue: field.defaultValue ?? "",
            position: field.position,
        };
        clearField();
        showEditField.value = true;
    }

    async function submitFieldEdit() {
        if (
            !validateField({
                label: () =>
                    required(t("backend.welding.pdf_template_fields.errors.label_required"))(
                        fieldForm.value.label,
                    ),
            })
        )
            return;
        const path = buildPath(updateFieldPath, { id: editingField.value.id });
        const data = await fieldEditRequest(path, { ...fieldForm.value });
        if (!data) return;
        if (data.success) {
            showEditField.value = false;
            const idx = fieldsTemplate.value.fields.findIndex(
                (f) => f.id === editingField.value.id,
            );
            if (idx !== -1) fieldsTemplate.value.fields[idx] = data.field;
            reset();
        }
    }

    return {
        statusOptions,
        showCreate,
        newTemplate,
        showMediaPickerCreate,
        createErrors,
        createLoading,
        openCreate,
        onFilePickedCreate,
        submitCreate,
        showEdit,
        editingTemplate,
        editForm,
        showMediaPickerEdit,
        editErrors,
        editLoading,
        openEdit,
        onFilePickedEdit,
        submitEdit,
        pendingDelete,
        deleteLoading,
        confirmDelete,
        doDelete,
        showFields,
        fieldsTemplate,
        detectLoading,
        openFields,
        detectFields,
        fieldTypeOptions,
        editingField,
        fieldForm,
        showEditField,
        fieldErrors,
        fieldEditLoading,
        openEditField,
        submitFieldEdit,
    };
}
