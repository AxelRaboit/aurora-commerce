import { expect, test } from "@playwright/test";
import { mkdtempSync, writeFileSync, rmSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";

async function loginAsAdmin(page) {
    await page.goto("/login");
    await page.locator("input[type='email'], input[name*='email']").first().fill("admin@aurora.app");
    await page.locator("input[type='password']").first().fill("password");
    await page.locator("button[type='submit']").first().click();
    await page.waitForURL(/\/admin/);
}

// 1×1 PNG (smallest valid PNG, decoded from base64).
const TINY_PNG_BASE64 =
    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNgAAIAAAUAAen63NgAAAAASUVORK5CYII=";

test.describe("Admin Media", () => {
    let scratchDir;
    let imagePath;

    test.beforeAll(() => {
        scratchDir = mkdtempSync(join(tmpdir(), "aurora-e2e-"));
        imagePath = join(scratchDir, "e2e-upload.png");
        writeFileSync(imagePath, Buffer.from(TINY_PNG_BASE64, "base64"));
    });

    test.afterAll(() => {
        if (scratchDir) {
            rmSync(scratchDir, { recursive: true, force: true });
        }
    });

    test("media library page loads", async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto("/admin/media");
        await expect(page).toHaveURL(/\/admin\/media/);
        const heading = page.getByRole("heading").first();
        await expect(heading).toBeVisible();
    });

    test("uploads a file via the upload endpoint", async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto("/admin/media");

        // The upload <input type="file"> is hidden; setInputFiles works on hidden inputs.
        const fileInput = page.locator("input[type='file']").first();
        await fileInput.setInputFiles(imagePath);

        // After upload, either a toast confirms it or the new item shows up in the grid.
        const toast = page
            .locator("[data-sonner-toast], [role='status'], [class*='toast']")
            .first();
        await expect(toast).toBeVisible({ timeout: 10_000 });
    });
});
