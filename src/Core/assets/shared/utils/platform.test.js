import { describe, it, expect } from "vitest";
import { isMac, modKeyLabel } from "./platform.js";

describe("platform", () => {
    it("isMac is a boolean", () => {
        expect(typeof isMac).toBe("boolean");
    });

    it("modKeyLabel is either ⌘ or Ctrl", () => {
        expect(["⌘", "Ctrl"]).toContain(modKeyLabel);
    });

    it("modKeyLabel matches isMac", () => {
        if (isMac) {
            expect(modKeyLabel).toBe("⌘");
        } else {
            expect(modKeyLabel).toBe("Ctrl");
        }
    });
});
