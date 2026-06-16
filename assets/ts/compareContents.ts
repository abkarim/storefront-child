import { createHTMLElement } from "./util/element";
import { getCompareProducts } from "./util/storage";

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

async function getProducts() {
    const products = getCompareProducts();

    const emptyNoticeElement = document.getElementById("sf-compare-empty");
    if (emptyNoticeElement) {
        if (products.length !== 0) {
            emptyNoticeElement.style.display = "none";
        } else {
            emptyNoticeElement.style.display = "block";
        }
    }

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

const imagesTableRow = document.getElementById("row-product-images");
const nameTableRow = document.getElementById("row-product-titles");
const priceTableRow = document.getElementById("row-product-prices");
const availTableRow = document.getElementById("row-product-stock");
const purchaseTableRow = document.getElementById("row-product-buy");
const actionTableRow = document.getElementById("row-product-triggers");

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
            class: "sf-del-col",
            "data-id": product.id.toString(),
        },
        "Remove",
    );
    const actionTD = createHTMLElement("td", tdAttr, removeBtnElement);
    actionTableRow?.appendChild(actionTD);
}
