import {
    addCompareProduct,
    getCompareProducts,
    isCompareProductExists,
    removeCompareProduct,
} from "./util/storage";

const compareButtonSelector = ".sf-compare-btn";
const quickViewButtonSelector = ".sf-quickview-btn";

/**
 * Render compare count badge on the compare button in the header
 */
export function renderCompareCount() {
    const compareCountBadge = document.querySelector(
        "header .header-col-utilities .items-compare-summary .product-compare-count",
    );
    const compareProducts = getCompareProducts();
    if (compareCountBadge) {
        const length = compareProducts.length;
        compareCountBadge.textContent = length > 0 ? length.toString() : "";
    }
}
renderCompareCount();

const products = document.querySelectorAll("ul.products li.product");
products.forEach((product) => {
    const quickViewContainer = product.querySelector(
        ".sf-archive-action-buttons-group",
    );

    if (!quickViewContainer) return;
    const productId = quickViewContainer.getAttribute("data-product-id");

    product.addEventListener(
        "mouseenter",
        () => {
            const isInCompare = isCompareProductExists(productId!);
            if (isInCompare) {
                const element = quickViewContainer.querySelector(
                    compareButtonSelector,
                );
                if (element) {
                    element.setAttribute("added", "true");
                }
            }
        },
        { once: true },
    );

    quickViewContainer.addEventListener("click", (event) => {
        const target = event.target as HTMLElement;
        const compareBtn = target.closest(compareButtonSelector);
        const quickViewBtn = target.closest(quickViewButtonSelector);

        if (compareBtn) {
            event.preventDefault();
            if (!isCompareProductExists(productId!)) {
                compareBtn.setAttribute("added", "true");
                addCompareProduct(productId!);
            } else {
                compareBtn.setAttribute("added", "false");
                removeCompareProduct(productId!);
            }
            renderCompareCount();
        }

        if (quickViewBtn) {
            event.preventDefault();
            console.log(`Quick view product ID: ${productId}`);
            // Implement quick view functionality here
        }
    });
});
