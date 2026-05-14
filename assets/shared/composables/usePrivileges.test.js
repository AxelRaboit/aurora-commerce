import { describe, it, expect, vi, beforeEach } from "vitest";

async function freshUsePrivileges({ isDev = false, isAdmin = false, privileges = [] } = {}) {
    vi.stubGlobal("__isDev__", isDev ? true : undefined);
    vi.stubGlobal("__isAdmin__", isAdmin ? true : undefined);
    vi.stubGlobal("__privileges__", privileges);
    vi.resetModules();
    const { usePrivileges } = await import("./usePrivileges.js");
    return usePrivileges;
}

describe("usePrivileges", () => {
    beforeEach(() => {
        vi.unstubAllGlobals();
        vi.resetModules();
    });

    it("dev user can do anything", async () => {
        const usePrivileges = await freshUsePrivileges({ isDev: true });
        const { can } = usePrivileges();
        expect(can("crm.contacts.view")).toBe(true);
        expect(can("anything.random")).toBe(true);
    });

    it("admin user can do anything", async () => {
        const usePrivileges = await freshUsePrivileges({ isAdmin: true });
        const { can } = usePrivileges();
        expect(can("crm.contacts.view")).toBe(true);
    });

    it("regular user can only perform explicitly granted privileges", async () => {
        const usePrivileges = await freshUsePrivileges({
            privileges: ["crm.contacts.view", "crm.contacts.edit"],
        });
        const { can } = usePrivileges();
        expect(can("crm.contacts.view")).toBe(true);
        expect(can("crm.contacts.edit")).toBe(true);
        expect(can("crm.contacts.delete")).toBe(false);
    });

    it("regular user with no privileges cannot do anything", async () => {
        const usePrivileges = await freshUsePrivileges();
        const { can } = usePrivileges();
        expect(can("crm.contacts.view")).toBe(false);
    });

    it("exposes isDev and isAdmin flags", async () => {
        const usePrivileges = await freshUsePrivileges({ isDev: true });
        const { isDev, isAdmin } = usePrivileges();
        expect(isDev).toBe(true);
        expect(isAdmin).toBe(false);
    });
});
