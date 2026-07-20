import { test, expect } from "../setup";

test.describe("Dropshipping Resilience & Operations Monitoring E2E", () => {
    test.beforeEach(async ({}, testInfo) => {
        testInfo.setTimeout(120000);
    });

    test("should successfully navigate to monitoring, verify cards, and trigger circuit reset", async ({ adminPage }) => {
        // Navigate to the Dropshipping Monitoring screen
        await adminPage.goto("admin/dropshipping/monitoring");

        // Verify page header is rendered
        await expect(adminPage.locator("h1")).toContainText("مراقبة العمليات والتحمل");

        // Verify all 3 resilience monitoring cards are rendered
        await expect(adminPage.locator('text="قاطع دورة الاتصالات (Circuit Breaker)"')).toBeVisible();
        await expect(adminPage.locator('text="معدل استهلاك الـ API (Rate Limits)"')).toBeVisible();
        await expect(adminPage.locator('text="طابور مهام التوريد المعلقة (Queue Backlog)"')).toBeVisible();

        // Verify "إعادة ضبط القاطع" reset button exists
        const btnReset = adminPage.locator("#btn-reset-circuit");
        await expect(btnReset).toBeVisible();

        // Click the reset button to verify POST trigger and page reload
        await btnReset.click();

        // Verify redirect/flash success response via the static inline success alert
        const successAlert = adminPage.locator("#inline-success-alert");
        await expect(successAlert).toBeVisible({ timeout: 25000 });
        await expect(successAlert).toContainText("نجاح");
    });
});
