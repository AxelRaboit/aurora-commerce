import { expect, test } from "@playwright/test";

test.describe("Login page", () => {
    test("renders the login form and validates an empty submit", async ({
        page,
    }) => {
        await page.goto("/login");

        await expect(page).toHaveURL(/\/login$/);
        await expect(
            page.locator("input[type='email'], input[name*='email']").first(),
        ).toBeVisible();
        await expect(
            page.locator("input[type='password']").first(),
        ).toBeVisible();
    });

    test("rejects invalid credentials with an error", async ({ page }) => {
        await page.goto("/login");

        await page
            .locator("input[type='email'], input[name*='email']")
            .first()
            .fill("nobody@example.com");
        await page
            .locator("input[type='password']")
            .first()
            .fill("wrong-password");
        await page.locator("button[type='submit']").first().click();

        await expect(page).toHaveURL(/\/login/);
    });
});
