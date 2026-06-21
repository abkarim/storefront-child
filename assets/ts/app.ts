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
