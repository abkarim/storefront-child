import { updateRootVariable } from "./util/styles";
import "./productQuickActions";
import "./categoriesScroller";
import "./productPage"

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

    // 1. Update First Name label and placeholder to "Full Name"
    const firstNameLabels = checkoutFormContainer.querySelectorAll<HTMLLabelElement>(
        'label[for="billing-first_name"], label[for="shipping-first_name"]'
    );
    firstNameLabels.forEach((label) => {
        if (label.firstChild && label.firstChild.nodeType === Node.TEXT_NODE) {
            if (label.firstChild.textContent?.includes("First name")) {
                label.firstChild.textContent = label.firstChild.textContent.replace("First name", "Full Name");
            }
        }
    });

    const firstNameInputs = checkoutFormContainer.querySelectorAll<HTMLInputElement>(
        "#billing-first_name, #shipping-first_name"
    );
    firstNameInputs.forEach((input) => {
        if (input.placeholder && input.placeholder.includes("First name")) {
            input.placeholder = input.placeholder.replace("First name", "Full Name");
        }
    });

    // 2. Move Phone field below Full Name in DOM
    const firstNameWrapper = checkoutFormContainer.querySelector(
        ".wc-block-components-address-form__first-name"
    );
    const phoneWrapper = checkoutFormContainer.querySelector(
        ".wc-block-components-address-form__phone"
    );
    if (
        firstNameWrapper &&
        phoneWrapper &&
        firstNameWrapper.parentElement &&
        firstNameWrapper.nextElementSibling !== phoneWrapper
    ) {
        firstNameWrapper.parentElement.insertBefore(
            phoneWrapper,
            firstNameWrapper.nextElementSibling
        );
    }

    // 3. Place District on left with Address in a row
    const stateInput = checkoutFormContainer.querySelector<HTMLElement>("#billing-state, #shipping-state");
    const stateWrapper = stateInput?.closest<HTMLElement>(
        ".wc-block-components-address-form__state, .wc-block-components-combobox, .wc-block-components-text-input"
    ) || checkoutFormContainer.querySelector<HTMLElement>(".wc-block-components-address-form__state");

    const addressInput = checkoutFormContainer.querySelector<HTMLElement>("#billing-address_1, #shipping-address_1");
    const addressWrapper = addressInput?.closest<HTMLElement>(
        ".wc-block-components-address-form__address_1, .wc-block-components-text-input"
    ) || checkoutFormContainer.querySelector<HTMLElement>(".wc-block-components-address-form__address_1");

    if (
        phoneWrapper &&
        stateWrapper &&
        phoneWrapper.parentElement &&
        phoneWrapper.nextElementSibling !== stateWrapper
    ) {
        phoneWrapper.parentElement.insertBefore(
            stateWrapper,
            phoneWrapper.nextElementSibling
        );
    }

    if (
        stateWrapper &&
        addressWrapper &&
        stateWrapper.parentElement &&
        stateWrapper.nextElementSibling !== addressWrapper
    ) {
        stateWrapper.parentElement.insertBefore(
            addressWrapper,
            stateWrapper.nextElementSibling
        );
    }

    // Ensure District label
    const stateLabels = checkoutFormContainer.querySelectorAll<HTMLLabelElement>(
        'label[for="billing-state"], label[for="shipping-state"]'
    );
    stateLabels.forEach((label) => {
        if (label.firstChild && label.firstChild.nodeType === Node.TEXT_NODE) {
            if (label.firstChild.textContent && !label.firstChild.textContent.includes("District")) {
                label.firstChild.textContent = label.firstChild.textContent.replace(/State(\s*\/\s*County)?/i, "District");
            }
        }
    });

    // 4. Remove "add note to your order" section
    const orderNotes = checkoutFormContainer.querySelectorAll<HTMLElement>(
        ".wp-block-woocommerce-checkout-order-notes-block, .wc-block-checkout__add-note, .wc-block-components-checkout-order-notes"
    );
    orderNotes.forEach((el) => {
        if (el.style.display !== "none") {
            el.style.display = "none";
        }
    });

    // 4. Remove terms from above place order button
    const termsElements = checkoutFormContainer.querySelectorAll<HTMLElement>(
        ".wc-block-checkout__terms, .wc-block-components-checkout-step--terms, .wc-block-components-terms-and-conditions, .wc-block-components-checkout-terms, .wc-block-checkout__actions .wc-block-components-checkbox-terms"
    );
    termsElements.forEach((el) => {
        if (el.style.display !== "none") {
            el.style.display = "none";
        }
    });

    // 5. Hide payment options if Cash on Delivery is the only available payment method
    let paymentStep = checkoutFormContainer.querySelector<HTMLElement>(
        "#payment-method, .wc-block-components-checkout-step--payment, .wc-block-components-checkout-step--payment-method, .wp-block-woocommerce-checkout-payment-methods-block, .wc-block-checkout__payment-methods"
    );

    if (!paymentStep) {
        const stepTitles = checkoutFormContainer.querySelectorAll<HTMLElement>(
            ".wc-block-components-checkout-step__title, .wc-block-components-title, h2, h3"
        );
        stepTitles.forEach((title) => {
            if (title.textContent?.trim().toLowerCase().includes("payment")) {
                paymentStep = title.closest<HTMLElement>(".wc-block-components-checkout-step, section, fieldset") || paymentStep;
            }
        });
    }

    if (paymentStep) {
        const radioInputs = paymentStep.querySelectorAll<HTMLInputElement>('input[type="radio"]');
        const textContent = paymentStep.textContent?.toLowerCase() || "";
        const hasOtherGateway = textContent.includes("bkash") || textContent.includes("nagad") || textContent.includes("rocket") || textContent.includes("stripe") || textContent.includes("paypal") || textContent.includes("card");

        const isOnlyCod = document.body.classList.contains("sf-cod-only-payment") ||
            (radioInputs.length === 1 && radioInputs[0].value.toLowerCase().includes("cod")) ||
            (radioInputs.length <= 1 && !hasOtherGateway);

        if (isOnlyCod) {
            document.body.classList.add("sf-cod-only-payment");
            if (paymentStep.style.display !== "none") {
                paymentStep.style.display = "none";
            }
            if (radioInputs.length === 1 && !radioInputs[0].checked) {
                radioInputs[0].click();
            }
        } else if (radioInputs.length > 1 || hasOtherGateway) {
            document.body.classList.remove("sf-cod-only-payment");
            if (paymentStep.style.display === "none") {
                paymentStep.style.display = "";
            }
        }
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
