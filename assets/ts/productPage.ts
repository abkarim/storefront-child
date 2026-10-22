const variationForm = document.querySelector("form.variations_form");

if (variationForm) {

    const addToCartButton = variationForm.querySelector("button.single_add_to_cart_button");
    const buyNowButton = variationForm.querySelector("button.sf-buy-now-button");

    if (addToCartButton) {
        // if add to cart button is disabled then diable buy now button
        // watch for class changes in add to cart button

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (!buyNowButton) return;
                if (mutation.type === "attributes") {
                    if (mutation.attributeName === "class") {
                        if (addToCartButton.classList.contains("disabled")) {
                            buyNowButton.classList.add("disabled");
                        } else {
                            buyNowButton.classList.remove("disabled");
                        }
                    }
                }
            });
        });

        observer.observe(addToCartButton, {
            attributes: true,
        });
    }

}




