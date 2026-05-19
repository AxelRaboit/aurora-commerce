import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";
import { useForm } from "@/shared/composables/form/useForm.js";
import { useDelete } from "@/shared/composables/form/useDelete.js";
import { useDebounce } from "@/shared/composables/useDebounce.js";
import { usePdfLivePreview } from "./usePdfLivePreview.js";
import { required } from "@/shared/utils/validation/validators.js";
import { translateServerErrors } from "@/shared/utils/validation/translateServerErrors.js";

export const DOCUMENT_STATUS_BADGE = {
    draft: "gray",
    generated: "emerald",
    archived: "accent",
};

function emptyGenerateForm(template) {
    const fieldValues = {};
    for (const field of template?.fields ?? []) {
        fieldValues[field.pdfFieldName] = field.defaultValue ?? "";
    }
    return {
        templateId: template?.id ?? null,
        label: "",
        fieldValues,
    };
}

export function usePdfDocumentsForm(
    generatePath,
    deletePath,
    templateListPath,
    reset,
) {
    const { t } = useI18n();

    // ── Modale unique (step 1 = picker, step 2 = éditeur, step 3 = signature) ─
    // Une seule modale = un seul history.pushState, zéro conflit de navigation.
    const showModal = ref(false);
    const step = ref(1);
    const editorTemplate = ref(null);
    const signatureData = ref(null);

    // ── Picker ────────────────────────────────────────────────────────────────
    const pickerSearch = ref("");
    const pickerItems = ref([]);
    const pickerPage = ref(1);
    const pickerTotalPages = ref(1);
    const pickerLoading = ref(false);
    const pickerLoadingMore = ref(false);
    const pickerHasMore = computed(
        () => pickerPage.value < pickerTotalPages.value,
    );

    async function fetchPicker(page, isReset) {
        if (isReset) pickerLoading.value = true;
        else pickerLoadingMore.value = true;
        try {
            const params = new URLSearchParams({
                page: String(page),
                status: "active",
            });
            if (pickerSearch.value) params.set("search", pickerSearch.value);
            const res = await fetch(`${templateListPath}?${params}`, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            const data = await res.json();
            if (data.success) {
                if (isReset) pickerItems.value = data.items;
                else pickerItems.value.push(...data.items);
                pickerPage.value = data.page;
                pickerTotalPages.value = data.totalPages;
            }
        } finally {
            pickerLoading.value = false;
            pickerLoadingMore.value = false;
        }
    }

    const debouncedSearch = useDebounce((value) => {
        pickerSearch.value = value;
        fetchPicker(1, true);
    }, 300);

    function openModal() {
        pickerSearch.value = "";
        pickerItems.value = [];
        pickerPage.value = 1;
        step.value = 1;
        showModal.value = true;
        fetchPicker(1, true);
    }

    function loadMorePicker() {
        if (!pickerHasMore.value || pickerLoadingMore.value) return;
        fetchPicker(pickerPage.value + 1, false);
    }

    function selectTemplate(template) {
        editorTemplate.value = template;
        generateForm.value = emptyGenerateForm(template);
        signatureData.value = null;
        clearGenerate();
        resetPositions();
        step.value = 2;
        // Extraire les positions une seule fois pour les overlays
        extractPositions(
            template,
            generateForm.value.fieldValues,
            template.fields ?? [],
        );
    }

    function backToPicker() {
        step.value = 1;
    }

    function goToSignature() {
        step.value = 3;
    }

    function backToEditor() {
        step.value = 2;
    }

    // ── Positions des champs (extraites une seule fois par template) ──────────
    const {
        fieldPositions,
        render: extractPositions,
        reset: resetPositions,
    } = usePdfLivePreview();

    // ── Éditeur ───────────────────────────────────────────────────────────────
    const generateForm = ref(emptyGenerateForm(null));
    const {
        errors: generateErrors,
        validate: validateGenerate,
        clearErrors: clearGenerate,
        setErrors: setGenerateErrors,
    } = useForm();
    const { loading: generateLoading, request: generateRequest } = useRequest();

    async function submitGenerate() {
        if (
            !validateGenerate({
                templateId: () =>
                    required(
                        t("backend.pdfform.documents.errors.template_required"),
                    )(generateForm.value.templateId),
            })
        )
            return;

        const payload = { ...generateForm.value };
        if (editorTemplate.value?.requiresSignature && signatureData.value) {
            payload.fieldValues = {
                ...payload.fieldValues,
                __signature__: signatureData.value,
            };
        }

        const data = await generateRequest(generatePath, payload);
        if (!data) return;
        if (data.success) {
            showModal.value = false;
            toast.success(t("backend.pdfform.documents.generate"));
            reset();
        } else if (data.errors) {
            setGenerateErrors(translateServerErrors(data.errors));
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
        "backend.pdfform.documents.deleted",
    );

    return {
        showModal,
        step,
        openModal,
        backToPicker,
        goToSignature,
        backToEditor,
        pickerItems,
        pickerLoading,
        pickerLoadingMore,
        pickerHasMore,
        debouncedSearch,
        loadMorePicker,
        selectTemplate,
        editorTemplate,
        generateForm,
        generateErrors,
        generateLoading,
        fieldPositions,
        signatureData,
        submitGenerate,
        pendingDelete,
        deleteLoading,
        confirmDelete,
        doDelete,
    };
}
