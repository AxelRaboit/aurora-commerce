import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { toast } from "vue-sonner";
import { buildPath } from "@/shared/utils/http/buildPath.js";
import { useRequest } from "@/shared/composables/http/backend/useRequest.js";

/**
 * Two complementary flows exposed to the Budget page:
 * - `savePreset`: capture the current month as a new preset (name +
 *   optional description), so it can be re-applied later.
 * - `apply`: list presets for the current wallet, pick one, choose
 *   append/replace, apply to the displayed month.
 *
 * Both stay isolated from each other so the BudgetsApp can wire them
 * to two separate buttons without coupling. The composable owns
 * loading/errors state — the SFC just renders modals against it.
 */
export function useBudgetPresetHooks({
    savePresetPath,
    listPresetsPath,
    applyPresetPath,
    onApplied,
}) {
    const { t } = useI18n();
    const { loading, request } = useRequest();

    // Save flow
    const showSave = ref(false);
    const saveForm = ref({ name: "", description: "" });
    const saveErrors = ref({});

    function openSave() {
        saveForm.value = { name: "", description: "" };
        saveErrors.value = {};
        showSave.value = true;
    }

    async function submitSave(walletId, month) {
        if (!walletId) return;
        saveErrors.value = {};

        const response = await request(buildPath(savePresetPath, { walletId }), {
            name: saveForm.value.name,
            description: saveForm.value.description || null,
            month,
        });
        if (!response) return;
        if (response.success === false) {
            saveErrors.value = response.errors ?? {};
            return;
        }
        toast.success(t("personal_finance.budget_presets.saved_from_month"));
        showSave.value = false;
    }

    // Apply flow
    const showApply = ref(false);
    const presetList = ref([]);
    const selectedPresetId = ref(null);
    const applyMode = ref("append");
    const applyErrors = ref({});

    async function openApply(walletId) {
        applyErrors.value = {};
        applyMode.value = "append";
        selectedPresetId.value = null;

        if (!walletId) {
            presetList.value = [];
            showApply.value = true;
            return;
        }
        const response = await request(buildPath(listPresetsPath, { walletId }), undefined, { method: "GET" });
        presetList.value = response?.presets ?? [];
        if (presetList.value.length === 1) {
            selectedPresetId.value = presetList.value[0].id;
        }
        showApply.value = true;
    }

    async function submitApply(month) {
        if (!selectedPresetId.value) {
            applyErrors.value = { preset: t("personal_finance.budget_presets.errors.pick_preset") };
            return;
        }
        applyErrors.value = {};

        const response = await request(
            buildPath(applyPresetPath, { id: selectedPresetId.value }),
            { month, mode: applyMode.value },
        );
        if (!response) return;
        if (response.success === false) {
            applyErrors.value = response.errors ?? {};
            return;
        }
        const insertedCount = response.insertedCount ?? 0;
        toast.success(
            t("personal_finance.budget_presets.applied", { count: insertedCount }, insertedCount),
        );
        showApply.value = false;
        onApplied?.(response);
    }

    return {
        loading,
        // save
        showSave, saveForm, saveErrors, openSave, submitSave,
        // apply
        showApply, presetList, selectedPresetId, applyMode, applyErrors, openApply, submitApply,
    };
}
