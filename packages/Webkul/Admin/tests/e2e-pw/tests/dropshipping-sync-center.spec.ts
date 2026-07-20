import { test, expect } from "../setup";

test.describe("Dropshipping Synchronization Center & Events E2E", () => {
    // Increase test timeout to 120 seconds for slow local server starts
    test.beforeEach(async ({}, testInfo) => {
        testInfo.setTimeout(120000);
    });

    test("should successfully navigate Sync Center tabs and verify grid tables rendering", async ({ adminPage }) => {
        // Navigate to the AliExpress Sync Center dashboard
        await adminPage.goto("admin/dropshipping/sync");

        // Verify page header is rendered
        await expect(adminPage.locator("h1")).toContainText("إدارة مزامنة AliExpress");

        // 1. Verify tab buttons exist
        const tabBtnProducts = adminPage.locator('.ae-tab-btn[data-tab="imported-products"]');
        const tabBtnRuns = adminPage.locator('.ae-tab-btn[data-tab="sync-runs"]');
        const tabBtnEvents = adminPage.locator('.ae-tab-btn[data-tab="events-tracker"]');

        await expect(tabBtnProducts).toBeVisible();
        await expect(tabBtnRuns).toBeVisible();
        await expect(tabBtnEvents).toBeVisible();

        // 2. Click Sync Runs tab and verify visibility
        await tabBtnRuns.click();
        const runsHeading = adminPage.locator('h2:has-text("سجل جلسات المزامنة المجدولة")');
        await expect(runsHeading).toBeVisible();

        // 3. Click Events Tracker tab and verify headings
        await tabBtnEvents.click();
        const outboxHeading = adminPage.locator('h2:has-text("أحداث الصادر للـ Domain")');
        const inboxHeading = adminPage.locator('h2:has-text("أحداث الوارد الخارجية")');
        await expect(outboxHeading).toBeVisible();
        await expect(inboxHeading).toBeVisible();

        // 4. Verify outbox manual replay action trigger (if events exist)
        const outboxReplayBtn = adminPage.locator(".ae-outbox-replay-btn").first();
        if (await outboxReplayBtn.count() > 0) {
            // Dismiss window alert dialogs automatically during execution
            adminPage.once("dialog", async (dialog) => {
                expect(dialog.message()).toContain("نجاح");
                await dialog.accept();
            });

            await outboxReplayBtn.click();
        }

        // 5. Verify inbox manual replay action trigger (if events exist)
        const inboxReplayBtn = adminPage.locator(".ae-inbox-replay-btn").first();
        if (await inboxReplayBtn.count() > 0) {
            adminPage.once("dialog", async (dialog) => {
                expect(dialog.message()).toContain("نجاح");
                await dialog.accept();
            });

            await inboxReplayBtn.click();
        }
    });
});
