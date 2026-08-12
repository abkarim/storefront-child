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
