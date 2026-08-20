import { updateRootVariable } from "./util/styles";
import "./productQuickActions";
import "./categoriesScroller";

const wpadminbar = document.getElementById("wpadminbar");

if (wpadminbar) {
    const adminBarHeight = wpadminbar.offsetHeight;
    updateRootVariable("--admin-bar-height", `${adminBarHeight}px`);
}

const themeHeader = document.querySelector("header.site-header");

if (themeHeader) {
    const themeHeaderHeight = themeHeader.getBoundingClientRect().height;
    updateRootVariable("--theme-header-height", `${themeHeaderHeight}px`);
}

const searchContainer = document.querySelector("#sf-child-search");
const searchButton = [...document.querySelectorAll(".search-products-button")];

function showSearchContainer() {
    if (searchContainer) {
        searchContainer.classList.remove("hidden");
    }
}

function hideSearchContainer() {
    if (searchContainer) {
        searchContainer.classList.add("hidden");
    }
}

searchContainer?.addEventListener("click", (event) => {
    const target = event.target as HTMLElement;

    if (target === searchContainer) {
        hideSearchContainer();
    }
});

searchButton.forEach((button) => {
    button.addEventListener("click", showSearchContainer);
});

const menuToggleButton = document.getElementById("sf-header-menu-toggle");
const menuContainer = document.querySelector(
    "header.site-header .header-col-utilities",
);

menuToggleButton?.addEventListener("click", toggleMobileMenu);
menuContainer?.addEventListener("click", (event) => {
    const target = event.target as HTMLElement;

    if (target === menuContainer) {
        toggleMobileMenu();
    }
});

function toggleMobileMenu() {
    if (menuContainer) {
        menuContainer.classList.toggle("mobile-menu-hidden");
    }
}

const checkoutFormSelector = ".wp-block-woocommerce-checkout.wc-block-checkout";
const checkoutFormContainer = document.querySelector(checkoutFormSelector);

function observerCheckoutContainerChanges() {
    if (!checkoutFormContainer) return;

    const emailElement: HTMLInputElement | null =
        checkoutFormContainer.querySelector("#email");
    if (emailElement) {
        setReactInputValue(emailElement, "guestBuyer@example.xyz");
    }

    const cityElement: HTMLInputElement | null =
        checkoutFormContainer.querySelector("#billing-city");
    if (cityElement) {
        setReactInputValue(cityElement, "NA");
    }
}

if (checkoutFormContainer) {
    const checkoutObserver = new MutationObserver(
        observerCheckoutContainerChanges,
    );
    checkoutObserver.observe(checkoutFormContainer, {
        childList: true,
        attributes: false,
        subtree: true,
    });
}

function setReactInputValue(
    inputElement: HTMLInputElement | null,
    value: string,
): void {
    if (!inputElement) return;

    // Get the native descriptor from HTMLInputElement prototype
    const descriptor = Object.getOwnPropertyDescriptor(
        window.HTMLInputElement.prototype,
        "value",
    );

    const nativeInputValueSetter = descriptor?.set;

    if (nativeInputValueSetter) {
        nativeInputValueSetter.call(inputElement, value);
    } else {
        inputElement.value = value;
    }

    // Dispatch the input event so React's state updates
    const event = new Event("input", { bubbles: true });
    inputElement.dispatchEvent(event);
}
