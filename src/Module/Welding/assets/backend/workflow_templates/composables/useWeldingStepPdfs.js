import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Link / unlink PDF templates to a workflow step from inside the editor.
 * `steps` is the parent's reactive array; the composable mutates the
 * `pdfTemplates` sub-collection of the affected step in place.
 */
export function useWeldingStepPdfs(steps) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    const modalStep = ref(null);
    const pdfTemplateOptions = ref([]);
    const form = ref({});

    async function openAdd(step) {
        modalStep.value = step;
        form.value = {
            pdfTemplateId: "",
            position: step.pdfTemplates.length,
            required: true,
        };
        if (pdfTemplateOptions.value.length === 0) {
            const res = await fetch("/backend/welding/options/pdf-templates", {
                headers: { Accept: "application/json" },
            });
            const data = await res.json();
            if (data.success) pdfTemplateOptions.value = data.items;
        }
    }

    function close() {
        modalStep.value = null;
    }

    async function save() {
        if (!form.value.pdfTemplateId) {
            toast.error(t("welding.editor.pdf_template_required"));
            return;
        }
        const data = await request(
            "/backend/welding/workflow-step-pdf-templates",
            {
                workflowStepTemplateId: modalStep.value.id,
                pdfTemplateId: Number(form.value.pdfTemplateId),
                position: form.value.position,
                required: form.value.required,
            },
        );
        if (data?.success) {
            const stepIdx = steps.value.findIndex(
                (s) => s.id === modalStep.value.id,
            );
            steps.value[stepIdx].pdfTemplates.push(data.entry);
            steps.value[stepIdx].pdfTemplatesCount =
                steps.value[stepIdx].pdfTemplates.length;
            modalStep.value = null;
            toast.success(t("welding.editor.pdf_added"));
        }
    }

    async function remove(step, entry) {
        const data = await request(
            `/backend/welding/workflow-step-pdf-templates/${entry.id}/delete`,
            {},
        );
        if (data?.success) {
            const stepIdx = steps.value.findIndex((s) => s.id === step.id);
            steps.value[stepIdx].pdfTemplates = steps.value[
                stepIdx
            ].pdfTemplates.filter((p) => p.id !== entry.id);
        }
    }

    return {
        loading,
        modalStep,
        pdfTemplateOptions,
        form,
        openAdd,
        close,
        save,
        remove,
    };
}
