/**
 * @vitest-environment happy-dom
 */
import { describe, it, expect, beforeEach, vi } from "vitest";

async function freshUseTheme(storedTheme = null, prefersDark = false) {
    vi.stubGlobal("localStorage", {
        getItem: vi.fn().mockReturnValue(storedTheme),
        setItem: vi.fn(),
    });
    vi.stubGlobal(
        "matchMedia",
        vi.fn().mockReturnValue({ matches: prefersDark }),
    );
    vi.resetModules();
    const { useTheme } = await import("./useTheme.js");
    return useTheme;
}

describe("useTheme", () => {
    beforeEach(() => {
        vi.unstubAllGlobals();
        vi.resetModules();
        document.documentElement.className = "";
    });

    it("reads the stored theme from localStorage", async () => {
        const useTheme = await freshUseTheme("dark");
        const { theme } = useTheme();
        expect(theme.value).toBe("dark");
    });

    it("falls back to prefers-color-scheme when nothing is stored", async () => {
        const useTheme = await freshUseTheme(null, true);
        const { theme } = useTheme();
        expect(theme.value).toBe("dark");
    });

    it("defaults to light when no preference and no storage", async () => {
        const useTheme = await freshUseTheme(null, false);
        const { theme } = useTheme();
        expect(theme.value).toBe("light");
    });

    it("toggle switches from light to dark", async () => {
        const useTheme = await freshUseTheme("light");
        const { theme, toggle } = useTheme();
        toggle();
        expect(theme.value).toBe("dark");
    });

    it("toggle switches from dark to light", async () => {
        const useTheme = await freshUseTheme("dark");
        const { theme, toggle } = useTheme();
        toggle();
        expect(theme.value).toBe("light");
    });
});
