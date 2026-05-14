import { describe, it, expect } from "vitest";
import {
    statusBadge,
    statusBadgeColor,
    accessRequestStatusBadge,
    accessRequestStatusBadgeColor,
} from "./statusStyles.js";

describe("statusBadge", () => {
    it("returns the correct classes for known statuses", () => {
        expect(statusBadge("published")).toBe(
            "bg-emerald-500/15 text-emerald-400",
        );
        expect(statusBadge("draft")).toBe("bg-amber-500/15 text-amber-400");
    });

    it("returns fallback classes for unknown status", () => {
        expect(statusBadge("unknown")).toBe("bg-surface-2 text-secondary");
    });
});

describe("statusBadgeColor", () => {
    it("returns color name for known status", () => {
        expect(statusBadgeColor("scheduled")).toBe("violet");
        expect(statusBadgeColor("archived")).toBe("slate");
    });

    it("returns gray for unknown status", () => {
        expect(statusBadgeColor("whatever")).toBe("gray");
    });
});

describe("accessRequestStatusBadge", () => {
    it("returns correct classes for access request statuses", () => {
        expect(accessRequestStatusBadge("pending")).toBe(
            "bg-amber-500/15 text-amber-400",
        );
        expect(accessRequestStatusBadge("approved")).toBe(
            "bg-emerald-500/15 text-emerald-400",
        );
    });

    it("returns fallback for unknown status", () => {
        expect(accessRequestStatusBadge("other")).toBe(
            "bg-surface-2 text-secondary",
        );
    });
});

describe("accessRequestStatusBadgeColor", () => {
    it("returns correct color for known status", () => {
        expect(accessRequestStatusBadgeColor("rejected")).toBe("gray");
    });
});
