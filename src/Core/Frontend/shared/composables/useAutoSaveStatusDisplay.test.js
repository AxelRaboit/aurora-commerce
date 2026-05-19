import { describe, it, expect, vi } from "vitest";
import { ref } from "vue";
import { useAutoSaveStatusDisplay } from "./useAutoSaveStatusDisplay.js";

// Provide a deterministic i18n stub so the labels are stable.
vi.mock("vue-i18n", () => ({
    useI18n: () => ({
        t: (key) => key,
    }),
}));

describe("useAutoSaveStatusDisplay", () => {
    it("returns null when the status is idle", () => {
        const status = ref("idle");
        const { display } = useAutoSaveStatusDisplay(status);

        expect(display.value).toBeNull();
    });

    it("renders a spinner for pending and saving", () => {
        const status = ref("pending");
        const { display } = useAutoSaveStatusDisplay(status);

        expect(display.value?.spin).toBe(true);
        expect(display.value?.label).toBe("shared.common.autosave.pending");

        status.value = "saving";
        expect(display.value?.spin).toBe(true);
        expect(display.value?.label).toBe("shared.common.autosave.saving");
    });

    it("renders a non-spinning check for saved", () => {
        const status = ref("saved");
        const { display } = useAutoSaveStatusDisplay(status);

        expect(display.value?.spin).toBe(false);
        expect(display.value?.classes).toContain("emerald");
    });

    it("renders an error tone for error", () => {
        const status = ref("error");
        const { display } = useAutoSaveStatusDisplay(status);

        expect(display.value?.spin).toBe(false);
        expect(display.value?.classes).toContain("rose");
    });

    it("reacts to status changes", () => {
        const status = ref("idle");
        const { display } = useAutoSaveStatusDisplay(status);

        expect(display.value).toBeNull();
        status.value = "saved";
        expect(display.value?.label).toBe("shared.common.autosave.saved");
    });
});
