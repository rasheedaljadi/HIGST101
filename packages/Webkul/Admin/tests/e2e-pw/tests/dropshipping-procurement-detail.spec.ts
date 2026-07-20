import { test, expect } from "../setup";

test.describe("Dropshipping Procurement & Fulfillment E2E", () => {
    test.beforeEach(async ({}, testInfo) => {
        testInfo.setTimeout(120000);
    });

    test("should successfully navigate to fulfillment index and details page", async ({ adminPage }) => {
        // Navigate to the Dropshipping Fulfillment screen
        await adminPage.goto("admin/dropshipping/fulfillment");

        // Verify page header is rendered
        await expect(adminPage.locator("h1")).toContainText("تنفيذ الطلبات");

        // Look for the Purchase Order grid container or datagrid
        const datagrid = adminPage.locator("div.bg-white.dark\\:bg-gray-900.rounded-lg").first();
        await expect(datagrid).toBeVisible();

        // Check if there are any purchase order records in the grid that we can view
        const poLink = adminPage.locator('a[href*="/dropshipping/fulfillment/view/"]').first();
        if (await poLink.count() > 0) {
            await poLink.click();

            // Verify detail page has loaded and has the PO header
            await expect(adminPage.locator("h1")).toContainText("أمر شراء #");

            // Verify that the new Procurement & Allocation widget is visible
            const trackerWidget = adminPage.locator("#procurement-allocation-tracker");
            await expect(trackerWidget).toBeVisible();
            await expect(trackerWidget.locator("h2")).toContainText("تعقب التوريد وتخصيص المخزون");
        }
    });
});
