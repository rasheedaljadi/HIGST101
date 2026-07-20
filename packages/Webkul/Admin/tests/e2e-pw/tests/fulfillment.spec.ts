import { test, expect } from "../setup";

test.describe("Fulfillment Bridge Admin E2E", () => {
    test("should load the fulfillment dashboard and render KPIs", async ({ adminPage }) => {
        // Navigate to the fulfillment bridge section
        await adminPage.goto("admin/dropshipping/fulfillment");

        // Verify page layout and header title
        await expect(adminPage.locator("header")).toBeVisible();
        await expect(adminPage).toHaveTitle(/Fulfillment/);

        // Verify presence of Dashboard widgets / KPI counters
        await expect(adminPage.getByText("Needs Review")).toBeVisible();
        await expect(adminPage.getByText("Success Rate")).toBeVisible();
        await expect(adminPage.getByText("Retry Rate")).toBeVisible();
    });

    test("should switch between tabbed grids seamlessly", async ({ adminPage }) => {
        await adminPage.goto("admin/dropshipping/fulfillment");

        // Click on the 'Approval Requests' tab/button
        const approvalTab = adminPage.locator("button:has-text('Approval Requests')");
        await expect(approvalTab).toBeVisible();
        await approvalTab.click();

        // Verify the active state / active classes or datagrid content switch
        await expect(adminPage.locator("button:has-text('Purchase Orders')")).toBeVisible();
    });

    test("should adjust layout responsively on mobile viewport sizes", async ({ adminPage }) => {
        await adminPage.goto("admin/dropshipping/fulfillment");

        // Resize viewport to mobile dimensions
        await adminPage.setViewportSize({ width: 375, height: 667 });

        // Verify hamburger menu drawer toggle is visible on mobile
        const hamburgerMenu = adminPage.locator("i.icon-menu");
        await expect(hamburgerMenu).toBeVisible();

        // Verify that main sidebar collapsed drawer trigger opens drawer
        await hamburgerMenu.click();
        await expect(adminPage.locator(".v-drawer-content")).toBeVisible();
    });
});
