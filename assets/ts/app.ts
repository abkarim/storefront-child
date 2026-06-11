const wpadminbar = document.getElementById("wpadminbar");

if (wpadminbar) {
    const adminBarHeight = wpadminbar.offsetHeight;
    document.documentElement.style.setProperty(
        "--admin-bar-height",
        `${adminBarHeight}px`,
    );
}

const themeHeader = document.querySelector("header.site-header");

if (themeHeader) {
    const themeHeaderHeight = themeHeader.getBoundingClientRect().height;
    document.documentElement.style.setProperty(
        "--theme-header-height",
        `${themeHeaderHeight}px`,
    );
}
