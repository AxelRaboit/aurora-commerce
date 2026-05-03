/**
 * @vitest-environment happy-dom
 */
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { defineComponent, h, ref } from "vue";
import { mount } from "@vue/test-utils";
import { createTestI18n } from "@/tests/helpers/createTestI18n.js";
import { useOcrJobs } from "@billing/vue/composables/useOcrJobs.js";

const STATUS_URL = "/admin/billing/ocr/jobs/__id__/status";
const RETRY_URL = "/admin/billing/ocr/jobs/__id__/retry";
const INTERVAL = 100;

function makeJob(id, overrides = {}) {
    return {
        id,
        fileName: `f-${id}.png`,
        status: "queued",
        statusLabel: "Queued",
        statusColor: "slate",
        isTerminal: false,
        confidence: null,
        modelUsed: null,
        createdAt: "2025-01-01T00:00:00+00:00",
        ...overrides,
    };
}

function mountWithComposable(setupFn) {
    const Comp = defineComponent({
        setup: () => {
            setupFn();
            return () => h("div");
        },
    });
    return mount(Comp, { global: { plugins: [createTestI18n()] } });
}

beforeEach(() => {
    vi.useFakeTimers();
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.useRealTimers();
});

describe("useOcrJobs — polling", () => {
    it("does not poll terminal jobs", async () => {
        const fetchMock = vi.fn();
        vi.stubGlobal("fetch", fetchMock);

        const jobs = ref([
            makeJob(1, { status: "completed", isTerminal: true }),
        ]);
        let api;
        mountWithComposable(() => {
            api = useOcrJobs(jobs, {
                statusUrlTemplate: STATUS_URL,
                intervalMs: INTERVAL,
            });
        });
        api.start();

        await vi.advanceTimersByTimeAsync(INTERVAL * 5);
        expect(fetchMock).not.toHaveBeenCalled();
    });

    it("polls non-terminal jobs and patches them on response", async () => {
        const updated = makeJob(1, {
            status: "needs_review",
            isTerminal: true,
        });
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ success: true, job: updated }),
        });
        vi.stubGlobal("fetch", fetchMock);

        const jobs = ref([makeJob(1)]);
        let api;
        mountWithComposable(() => {
            api = useOcrJobs(jobs, {
                statusUrlTemplate: STATUS_URL,
                intervalMs: INTERVAL,
            });
        });
        api.start();

        await vi.advanceTimersByTimeAsync(INTERVAL + 10);

        expect(fetchMock).toHaveBeenCalledWith(
            "/admin/billing/ocr/jobs/1/status",
            expect.objectContaining({ headers: expect.any(Object) }),
        );
        expect(jobs.value[0].status).toBe("needs_review");
        expect(jobs.value[0].isTerminal).toBe(true);
    });

    it("keeps polling until the job becomes terminal", async () => {
        const seq = [
            { success: true, job: makeJob(1, { status: "extracting" }) },
            { success: true, job: makeJob(1, { status: "parsing" }) },
            {
                success: true,
                job: makeJob(1, { status: "completed", isTerminal: true }),
            },
        ];
        let i = 0;
        const fetchMock = vi.fn().mockImplementation(async () => ({
            ok: true,
            json: async () => seq[Math.min(i++, seq.length - 1)],
        }));
        vi.stubGlobal("fetch", fetchMock);

        const jobs = ref([makeJob(1)]);
        let api;
        mountWithComposable(() => {
            api = useOcrJobs(jobs, {
                statusUrlTemplate: STATUS_URL,
                intervalMs: INTERVAL,
            });
        });
        api.start();

        await vi.advanceTimersByTimeAsync(INTERVAL * 5 + 50);

        expect(fetchMock).toHaveBeenCalledTimes(3);
        expect(jobs.value[0].status).toBe("completed");
    });

    it("ignores transient HTTP errors and retries on next tick", async () => {
        const fetchMock = vi
            .fn()
            .mockResolvedValueOnce({ ok: false, json: async () => ({}) })
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    success: true,
                    job: makeJob(1, { status: "completed", isTerminal: true }),
                }),
            });
        vi.stubGlobal("fetch", fetchMock);

        const jobs = ref([makeJob(1)]);
        let api;
        mountWithComposable(() => {
            api = useOcrJobs(jobs, {
                statusUrlTemplate: STATUS_URL,
                intervalMs: INTERVAL,
            });
        });
        api.start();

        await vi.advanceTimersByTimeAsync(INTERVAL * 3 + 10);

        expect(fetchMock.mock.calls.length).toBeGreaterThanOrEqual(2);
        expect(jobs.value[0].isTerminal).toBe(true);
    });

    it("stop() cancels in-flight pollers", async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ success: true, job: makeJob(1) }),
        });
        vi.stubGlobal("fetch", fetchMock);

        const jobs = ref([makeJob(1)]);
        let api;
        mountWithComposable(() => {
            api = useOcrJobs(jobs, {
                statusUrlTemplate: STATUS_URL,
                intervalMs: INTERVAL,
            });
        });
        api.start();
        api.stop();

        await vi.advanceTimersByTimeAsync(INTERVAL * 5);
        expect(fetchMock).not.toHaveBeenCalled();
    });
});

describe("useOcrJobs — hasInvoice", () => {
    it("returns true when invoiceId is set regardless of status", () => {
        let api;
        mountWithComposable(() => {
            api = useOcrJobs(ref([]), { statusUrlTemplate: STATUS_URL });
        });
        expect(api.hasInvoice({ status: "completed", invoiceId: 42 })).toBe(
            true,
        );
        expect(api.hasInvoice({ status: "needs_review", invoiceId: 42 })).toBe(
            true,
        );
        // rescan case: back to queued but invoice already linked
        expect(api.hasInvoice({ status: "queued", invoiceId: 42 })).toBe(true);
    });

    it("returns false when invoiceId is null or missing", () => {
        let api;
        mountWithComposable(() => {
            api = useOcrJobs(ref([]), { statusUrlTemplate: STATUS_URL });
        });
        expect(api.hasInvoice({ status: "completed", invoiceId: null })).toBe(
            false,
        );
        expect(
            api.hasInvoice({ status: "needs_review", invoiceId: null }),
        ).toBe(false);
        expect(api.hasInvoice({ status: "failed", invoiceId: null })).toBe(
            false,
        );
        expect(api.hasInvoice({ status: "queued", invoiceId: null })).toBe(
            false,
        );
    });
});
