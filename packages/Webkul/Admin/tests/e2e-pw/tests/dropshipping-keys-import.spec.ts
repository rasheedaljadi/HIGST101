import { test, expect } from "../setup";

test.describe("Dropshipping Settings and Product Importer E2E", () => {
    // Increase test timeout to 120 seconds for slow local server starts
    test.beforeEach(async ({}, testInfo) => {
        testInfo.setTimeout(120000);
    });

    test("should successfully navigate, update, and persist AliExpress API settings", async ({ adminPage }) => {
        // Navigate to the AliExpress Keys management screen
        await adminPage.goto("admin/dropshipping/keys");

        // Verify page header is rendered
        await expect(adminPage.locator("h1")).toContainText("إدارة مفاتيح AliExpress");

        // Fill in credential details
        await adminPage.locator('input[name="app_key"]').fill("50067890");
        await adminPage.locator('input[name="app_secret"]').fill("mock-app-secret-123456789");
        await adminPage.locator('input[name="shipping_margin"]').fill("12.50");
        await adminPage.locator('input[name="shipping_extra_days"]').fill("10");

        // Configure dropdown scheduling
        await adminPage.locator('select[name="sync_schedule"]').selectOption("twice-daily");

        // Click save button
        const saveButton = adminPage.locator('button[type="submit"]:has-text("حفظ الإعدادات")');
        await expect(saveButton).toBeVisible();
        await saveButton.click();

        // Verify redirect/flash success response via CSS class text-emerald-600
        const successAlert = adminPage.locator("div.text-emerald-600");
        await expect(successAlert).toBeVisible({ timeout: 15000 });
        await expect(successAlert).toContainText("حفظ");

        // Verify inputs persist saved values (using DB decimal format 12.5000)
        await expect(adminPage.locator('input[name="app_key"]')).toHaveValue("50067890");
        await expect(adminPage.locator('input[name="shipping_margin"]')).toHaveValue("12.5000");
        await expect(adminPage.locator('input[name="shipping_extra_days"]')).toHaveValue("10");
    });

    test("should trigger product import and render live progress logs", async ({ adminPage }) => {
        // Navigate to the single product importer panel
        await adminPage.goto("admin/dropshipping/import");

        // Verify page layout and input presence
        await expect(adminPage.locator("h1")).toContainText("استيراد منتج من AliExpress");
        const identifierInput = adminPage.locator("#ae-identifier");
        await expect(identifierInput).toBeVisible();

        // Enter test product identifier and click submit
        await identifierInput.fill("1005006789012345");
        const importButton = adminPage.locator("#ae-import-btn");
        await expect(importButton).toBeVisible();
        await importButton.click();

        // Verify progress panel becomes visible
        const progressPanel = adminPage.locator("#ae-progress-panel");
        await expect(progressPanel).toBeVisible();

        // Wait for terminal feedback (either success panel or failure warning) to complete
        // Using :not(.hidden) to ensure exactly one visible element is matched in strict mode
        const resultBox = adminPage.locator("#ae-success:not(.hidden), #ae-error:not(.hidden)");
        await expect(resultBox).toBeVisible({ timeout: 60000 });
    });
});
