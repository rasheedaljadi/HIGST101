import { test, expect } from "../setup";

test.describe("Dropshipping Finance & Bookkeeping E2E", () => {
    test.beforeEach(async ({}, testInfo) => {
        testInfo.setTimeout(120000);
    });

    test("should successfully navigate to finance index, verify KPI cards, and toggle tabs", async ({ adminPage }) => {
        // Navigate to the Dropshipping Finance Center screen
        await adminPage.goto("admin/dropshipping/finance");

        // Verify page header is rendered
        await expect(adminPage.locator("h1")).toContainText("الإدارة المالية والحسابات");

        // Verify all 4 financial KPI cards are rendered
        await expect(adminPage.locator('text="إجمالي المقبوضات (Revenue)"')).toBeVisible();
        await expect(adminPage.locator('text="نفقات الموردين (Expenses)"')).toBeVisible();
        await expect(adminPage.locator('text="صافي الأرباح المحققة (Net Profit)"')).toBeVisible();
        await expect(adminPage.locator('text="تكاليف توريد معلقة (COGS Pending)"')).toBeVisible();

        // Verify tab buttons exist
        const tabBtnLedger = adminPage.locator('.fin-tab-btn[data-tab="ledger-entries"]');
        const tabBtnTimeline = adminPage.locator('.fin-tab-btn[data-tab="financial-timeline"]');

        await expect(tabBtnLedger).toBeVisible();
        await expect(tabBtnTimeline).toBeVisible();

        // Click Financial Timeline tab and verify heading is displayed
        await tabBtnTimeline.click();
        const timelineHeading = adminPage.locator('h2:has-text("شريط العمليات المالي للطلب")');
        await expect(timelineHeading).toBeVisible();

        // Switch back to Ledger Journal tab and verify heading
        await tabBtnLedger.click();
        const ledgerHeading = adminPage.locator('h2:has-text("قيود اليومية المزدوجة")');
        await expect(ledgerHeading).toBeVisible();
    });
});
