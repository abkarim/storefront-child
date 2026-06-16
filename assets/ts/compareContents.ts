import { renderCompareCount } from "./productQuickActions";
import { createHTMLElement } from "./util/element";
import { getCompareProducts, removeCompareProduct } from "./util/storage";

interface ProductImage {
    id: number;
    src: string;
    thumbnail: string;
    alt: string;
    name: string;
}

interface ProductPrices {
    price: string;
    regular_price: string;
    sale_price: string;
    currency_code: string;
    currency_symbol: string;
    currency_minor_unit: number;
    price_html: string;
}

interface WooStoreProduct {
    id: number;
    name: string;
    permalink: string;
    images: ProductImage[];
    prices: ProductPrices;
    is_in_stock: boolean;
}

document.addEventListener("DOMContentLoaded", () => {
    getProducts();
});

function handleContents(l: number) {
    const emptyNoticeElement = document.getElementById("sf-compare-empty");
    const contentsElement = document.getElementById("sf-compare-contents");
    if (emptyNoticeElement && contentsElement) {
        if (l !== 0) {
            emptyNoticeElement.style.display = "none";
            contentsElement.style.display = "block";
        } else {
            contentsElement.style.display = "none";
            emptyNoticeElement.style.display = "block";
        }
    }
}

async function getProducts() {
    const products = getCompareProducts();

    handleContents(products.length);

    for (const id of products) {
        try {
            const response: Response = await fetch(
                `/wp-json/wc/store/v1/products/${id}`,
            );
            if (!response.ok) continue;

            const product: WooStoreProduct = await response.json();

            const imageSrc: string = product.images[0]?.src || "";
            const stockClass: string = product.is_in_stock
                ? "sf-stock-in"
                : "sf-stock-out";
            const stockText: string = product.is_in_stock
                ? "In Stock"
                : "Out of Stock";

            renderProductRow(product);
            console.log(product);
        } catch (error) {
            console.error(
                `Could not compile column row data for item index ID ${id}:`,
                error,
            );
        }
    }
}

const tableElements = document.getElementById("sf-compare-table");
const imagesTableRow = document.getElementById("row-product-images");
const nameTableRow = document.getElementById("row-product-titles");
const priceTableRow = document.getElementById("row-product-prices");
const availTableRow = document.getElementById("row-product-stock");
const purchaseTableRow = document.getElementById("row-product-buy");
const actionTableRow = document.getElementById("row-product-triggers");
const clearAllButton = document.querySelector("#sf-compare-clear-all button");

if (clearAllButton) {
    clearAllButton.addEventListener("click", () => {
        const products = getCompareProducts();

        products.forEach((id) => {
            removeProductRowById(id);
        });
    });
}

function removeProductRow(event: PointerEvent): void {
    const target = event.currentTarget as HTMLElement | null;
    if (!target) return;

    const id: string | undefined = target.dataset.id;
    if (!id) return;

    removeProductRowById(id);
}

function removeProductRowById(id: string) {
    const tableDataElements = [
        ...tableElements!.querySelectorAll(`[data-product-id="${id}"]`),
    ];
    tableDataElements.forEach((tde) => tde.remove());

    removeCompareProduct(id);
    renderCompareCount();

    handleContents(getCompareProducts().length);
}

function renderProductRow(product: WooStoreProduct) {
    const tdAttr = {
        "data-product-id": product.id.toString(),
    };

    const imageElement = createHTMLElement("img", {
        src: product.images[0].thumbnail,
        alt: product.images[0].alt,
    });
    const td = createHTMLElement("td", tdAttr, imageElement);
    imagesTableRow?.appendChild(td);

    const titleElement = createHTMLElement("p", {}, product.name);
    const titleTD = createHTMLElement("td", tdAttr, titleElement);
    nameTableRow?.appendChild(titleTD);

    const priceElement = createHTMLElement("p", {}, product.prices.sale_price);
    const priceTD = createHTMLElement("td", tdAttr, priceElement);
    priceTableRow?.appendChild(priceTD);

    const stockText = product.is_in_stock ? "In Stock" : "Out of Stock";
    const stockClass = product.is_in_stock ? "sf-stock-in" : "sf-stock-out";
    const availElement = createHTMLElement(
        "span",
        { class: stockClass },
        stockText,
    );
    const availTD = createHTMLElement("td", tdAttr, availElement);
    availTableRow?.appendChild(availTD);

    const buyLinkElement = createHTMLElement(
        "a",
        {
            href: product.permalink,
            class: "button alt sf-compare-action-btn",
        },
        "View Product",
    );
    const purchaseTD = createHTMLElement("td", tdAttr, buyLinkElement);
    purchaseTableRow?.appendChild(purchaseTD);

    const removeBtnElement = createHTMLElement(
        "button",
        {
            class: "sf-del-col button danger",
            "data-id": product.id.toString(),
        },
        "Remove",
    );

    removeBtnElement.addEventListener("click", removeProductRow);

    const actionTD = createHTMLElement("td", tdAttr, removeBtnElement);
    actionTableRow?.appendChild(actionTD);
}
