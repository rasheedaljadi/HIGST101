import asyncio
from playwright.async_api import async_playwright

async def main():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page(viewport={"width": 1400, "height": 900})
        
        url = "https://highest-ye.store/high-waist-stripe-fitness-yoga-shorts-for-womans-quick-dry-breathable-hip-lifting-su"
        print(f"Navigating to {url}...")
        await page.goto(url, wait_until="networkidle", timeout=30000)
        
        await page.wait_for_timeout(3000)
        
        # Click on one of the swatches if available
        swatches = await page.query_selector_all("span.cursor-pointer, div.cursor-pointer")
        print(f"Found {len(swatches)} potential swatch elements")
        
        # Inspect main image element and container
        img_info = await page.evaluate("""() => {
            const img = document.querySelector('img[fetchpriority="high"]') || document.querySelector('div.relative img');
            const container = img ? img.parentElement : null;
            if (!img || !container) return { error: "Image or container not found" };
            
            const imgRect = img.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const imgStyle = window.getComputedStyle(img);
            const containerStyle = window.getComputedStyle(container);
            
            return {
                img: {
                    src: img.src,
                    naturalWidth: img.naturalWidth,
                    naturalHeight: img.naturalHeight,
                    width: imgRect.width,
                    height: imgRect.height,
                    objectFit: imgStyle.objectFit,
                    aspectRatio: imgStyle.aspectRatio
                },
                container: {
                    width: containerRect.width,
                    height: containerRect.height,
                    aspectRatio: containerStyle.aspectRatio,
                    maxWidth: containerStyle.maxWidth,
                    maxHeight: containerStyle.maxHeight,
                    backgroundColor: containerStyle.backgroundColor
                }
            };
        }""")
        
        print("Initial Image Info:", img_info)
        
        # Take initial screenshot
        await page.screenshot(path=r"C:\Users\RASHEED\.gemini\antigravity-ide\brain\5d6ca11e-c43f-4e97-a20b-0a6c6465216c\pdp_shorts_inspection.png")
        print("Screenshot saved!")
        
        await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
