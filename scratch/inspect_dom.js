const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({
        viewport: { width: 1280, height: 800 }
    });

    console.log("Navigating to Product 222 PDP...");
    await page.goto('https://highest-ye.store/2026-mens-new-embroidered-business-leisure-short-sleeved-polo-shirt-fashion-short-sleeved-comfortable-and-breathable-top', { waitUntil: 'networkidle' });

    // Wait for the gallery image to be rendered
    await page.waitForTimeout(3000);

    const domData = await page.evaluate(() => {
        // Find main image in desktop gallery
        const stickyDiv = document.querySelector('.sticky');
        if (!stickyDiv) {
            return { error: ".sticky container not found" };
        }

        // Find main image container (the second child of sticky flex container)
        const galleryContainers = stickyDiv.children;
        const mainImgDiv = Array.from(galleryContainers).find(child => child.querySelector('img') && !child.classList.contains('flex-24'));
        const img = mainImgDiv ? mainImgDiv.querySelector('img') : stickyDiv.querySelector('img');

        if (!img) {
            return { error: "Main image element not found inside gallery" };
        }

        const container = img.parentElement;

        const getStyles = (el) => {
            const cs = window.getComputedStyle(el);
            const rect = el.getBoundingClientRect();
            return {
                tagName: el.tagName.toLowerCase(),
                id: el.id,
                className: el.className,
                rectWidth: rect.width,
                rectHeight: rect.height,
                computedWidth: cs.width,
                computedHeight: cs.height,
                computedAspectRatio: cs.aspectRatio,
                computedMaxWidth: cs.maxWidth,
                computedMaxHeight: cs.maxHeight,
                computedMinWidth: cs.minWidth,
                computedMinHeight: cs.minHeight,
                computedDisplay: cs.display,
                computedPosition: cs.position,
                computedOverflow: cs.overflow,
                computedObjectFit: cs.objectFit,
                computedFlex: cs.flex,
                computedFlexDirection: cs.flexDirection,
                computedAlignItems: cs.alignItems,
                computedJustifyContent: cs.justifyContent,
                computedBoxSizing: cs.boxSizing,
                computedPadding: cs.padding,
                computedMargin: cs.margin,
            };
        };

        const imageInfo = {
            rectWidth: img.getBoundingClientRect().width,
            rectHeight: img.getBoundingClientRect().height,
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
            currentSrc: img.currentSrc || img.src,
            computedStyles: getStyles(img)
        };

        const containerInfo = {
            rectWidth: container.getBoundingClientRect().width,
            rectHeight: container.getBoundingClientRect().height,
            computedStyles: getStyles(container)
        };

        // Trace parents up to Product Gallery / Body
        const parents = [];
        let curr = container.parentElement;
        while (curr && curr !== document.body) {
            parents.push({
                tagName: curr.tagName.toLowerCase(),
                id: curr.id,
                className: curr.className,
                rectWidth: curr.getBoundingClientRect().width,
                rectHeight: curr.getBoundingClientRect().height,
                computedWidth: window.getComputedStyle(curr).width,
                computedHeight: window.getComputedStyle(curr).height,
                computedAspectRatio: window.getComputedStyle(curr).aspectRatio,
                computedMaxWidth: window.getComputedStyle(curr).maxWidth,
                computedMaxHeight: window.getComputedStyle(curr).maxHeight,
                computedDisplay: window.getComputedStyle(curr).display,
                computedFlex: window.getComputedStyle(curr).flex,
                computedGrid: window.getComputedStyle(curr).gridTemplateColumns,
            });
            if (curr.tagName.toLowerCase() === 'v-product-gallery' || curr.classList.contains('sticky')) {
                // keep going up a bit more to see container wrapper
            }
            curr = curr.parentElement;
        }

        return {
            imageInfo,
            containerInfo,
            parents
        };
    });

    console.log("DOM_DATA_START");
    console.log(JSON.stringify(domData, null, 2));
    console.log("DOM_DATA_END");

    await browser.close();
})();
