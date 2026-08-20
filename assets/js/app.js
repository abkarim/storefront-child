(() => {
  // assets/ts/util/styles.ts
  function updateRootVariable(name, value) {
    document.documentElement.style.setProperty(name, value);
  }

  // assets/ts/util/storage.ts
  function setStorageItem(key, value) {
    localStorage.setItem(key, value);
  }
  function getStorageItem(key) {
    return localStorage.getItem(key);
  }
  var compareListKey = "compareList";
  function addCompareProduct(productId) {
    const compareList = getStorageItem(compareListKey);
    let compareArray = compareList ? JSON.parse(compareList) : [];
    if (!compareArray.includes(productId)) {
      compareArray.push(productId);
      setStorageItem(compareListKey, JSON.stringify(compareArray));
    }
  }
  function removeCompareProduct(productId) {
    const compareList = getStorageItem(compareListKey);
    if (compareList) {
      let compareArray = JSON.parse(compareList);
      compareArray = compareArray.filter((id) => id !== productId);
      setStorageItem(compareListKey, JSON.stringify(compareArray));
    }
  }
  function getCompareProducts() {
    const compareList = getStorageItem(compareListKey);
    return compareList ? JSON.parse(compareList) : [];
  }
  function isCompareProductExists(productId) {
    const compareList = getStorageItem(compareListKey);
    if (compareList) {
      const compareArray = JSON.parse(compareList);
      return compareArray.includes(productId);
    }
    return false;
  }

  // assets/ts/productQuickActions.ts
  var compareButtonSelector = ".sf-compare-btn";
  var quickViewButtonSelector = ".sf-quickview-btn";
  var quickViewContainerSelector = "#sf-child-quick-view";
  var quickViewContainer = document.querySelector(quickViewContainerSelector);
  var quickViewContentArea = quickViewContainer?.querySelector(
    ".dynamic-content"
  );
  function renderCompareCount() {
    const compareCountBadge = document.querySelector(
      "header .header-col-utilities .items-compare-summary .product-compare-count"
    );
    const compareProducts = getCompareProducts();
    if (compareCountBadge) {
      const length = compareProducts.length;
      compareCountBadge.textContent = length > 0 ? length.toString() : "";
    }
  }
  renderCompareCount();
  var products = document.querySelectorAll("ul.products li.product");
  products.forEach((product) => {
    const quickViewContainer2 = product.querySelector(
      ".sf-archive-action-buttons-group"
    );
    if (!quickViewContainer2) return;
    const productId = quickViewContainer2.getAttribute("data-product-id");
    product.addEventListener(
      "mouseenter",
      () => {
        const isInCompare = isCompareProductExists(productId);
        if (isInCompare) {
          const element = quickViewContainer2.querySelector(
            compareButtonSelector
          );
          if (element) {
            element.setAttribute("added", "true");
          }
        }
      },
      { once: true }
    );
    quickViewContainer2.addEventListener("click", (event) => {
      const target = event.target;
      const compareBtn = target.closest(compareButtonSelector);
      const quickViewBtn = target.closest(quickViewButtonSelector);
      if (compareBtn) {
        event.preventDefault();
        if (!isCompareProductExists(productId)) {
          compareBtn.setAttribute("added", "true");
          addCompareProduct(productId);
        } else {
          compareBtn.setAttribute("added", "false");
          removeCompareProduct(productId);
        }
        renderCompareCount();
      }
      if (quickViewBtn) {
        event.preventDefault();
        openQuickView(productId);
      }
    });
  });
  function stopBodyScroll() {
    document.body.style.overflow = "hidden";
  }
  function allowBodyScroll() {
    document.body.style.overflow = "";
  }
  quickViewContainer?.addEventListener("click", (event) => {
    const target = event.target;
    if (target.classList.contains("close") || target === quickViewContainer) {
      event.preventDefault();
      closeQuickView();
    }
  });
  function closeQuickView() {
    if (quickViewContainer) {
      quickViewContainer.classList.add("hidden");
    }
    if (quickViewContentArea) {
      quickViewContentArea.innerHTML = "";
    }
    allowBodyScroll();
  }
  async function openQuickView(productId) {
    if (!quickViewContainer || !quickViewContentArea) return;
    quickViewContainer.classList.remove("hidden");
    quickViewContentArea.innerHTML = '<div class="sf-qv-loading">Loading...</div>';
    stopBodyScroll();
    try {
      const response = await fetch(
        `${sf_qv_params.ajax_url}?action=sf_child_quick_view&product_id=${productId}`
      );
      const result = await response.json();
      if (result.success) {
        quickViewContentArea.innerHTML = result.data;
        const variationForm = quickViewContentArea.querySelector(".variations_form");
        if (variationForm) {
          variationForm.dispatchEvent(
            new CustomEvent("wc_variation_form", { bubbles: true })
          );
        }
      } else {
        quickViewContentArea.innerHTML = '<p class="sf-qv-error">Failed to load product details.</p>';
      }
    } catch (error) {
      console.error("Quick view fetch error:", error);
      quickViewContentArea.innerHTML = '<p class="sf-qv-error">An error occurred. Please try again.</p>';
    }
  }

  // assets/ts/categoriesScroller.ts
  document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(
      ".wp-block-woocommerce-product-categories.slide ul.wc-block-product-categories-list.wc-block-product-categories-list--has-images.wc-block-product-categories-list--depth-0"
    );
    if (!container) return;
    let moving = false;
    let startX = 0;
    let startScrollLeft = 0;
    let scrollInterval = null;
    const scrollSpeed = 2;
    const intervalTime = 20;
    const delayAfterActivityInMS = 5e3;
    const lastTarget = container.scrollWidth - container.clientWidth;
    let currentX = 0;
    let isTicking = false;
    const DRAG_SPEED_MULTIPLIER = 2;
    function updateScrollPosition() {
      if (!container || !moving) {
        isTicking = false;
        return;
      }
      const walkX = (currentX - startX) * DRAG_SPEED_MULTIPLIER;
      container.scrollLeft = startScrollLeft - walkX;
      isTicking = false;
    }
    function startAutoScroll() {
      if (moving) return;
      container.scrollLeft += scrollSpeed;
      if (lastTarget !== container.scrollLeft) {
        scrollInterval = setTimeout(startAutoScroll, intervalTime);
      } else {
        container.scrollLeft = 0;
        scrollInterval = setTimeout(startAutoScroll, 2e3);
      }
    }
    function stopAutoScroll() {
      if (scrollInterval) {
        clearTimeout(scrollInterval);
        scrollInterval = null;
      }
    }
    container.addEventListener("pointerdown", (e) => {
      if (e.button !== 0) return;
      stopAutoScroll();
      if (scrollInterval) clearTimeout(scrollInterval);
      moving = true;
      startX = e.clientX;
      currentX = e.clientX;
      startScrollLeft = container.scrollLeft;
    });
    container.addEventListener("pointermove", (e) => {
      stopAutoScroll();
      currentX = e.clientX;
      if (!isTicking) {
        requestAnimationFrame(updateScrollPosition);
        isTicking = true;
      }
      if (!moving) {
        scrollInterval = setTimeout(
          startAutoScroll,
          delayAfterActivityInMS
        );
      }
    });
    const handlePointerRelease = (event) => {
      if (!moving) return;
      moving = false;
      scrollInterval = setTimeout(() => {
        startAutoScroll();
      }, delayAfterActivityInMS);
    };
    container.addEventListener("pointerup", handlePointerRelease);
    container.addEventListener("pointercancel", handlePointerRelease);
    const observerOptions = {
      root: null,
      threshold: 0.05
    };
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            startAutoScroll();
          } else {
            stopAutoScroll();
          }
        });
      },
      observerOptions
    );
    observer.observe(container);
  });

  // assets/ts/app.ts
  var wpadminbar = document.getElementById("wpadminbar");
  if (wpadminbar) {
    const adminBarHeight = wpadminbar.offsetHeight;
    updateRootVariable("--admin-bar-height", `${adminBarHeight}px`);
  }
  var themeHeader = document.querySelector("header.site-header");
  if (themeHeader) {
    const themeHeaderHeight = themeHeader.getBoundingClientRect().height;
    updateRootVariable("--theme-header-height", `${themeHeaderHeight}px`);
  }
  var searchContainer = document.querySelector("#sf-child-search");
  var searchButton = [...document.querySelectorAll(".search-products-button")];
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
    const target = event.target;
    if (target === searchContainer) {
      hideSearchContainer();
    }
  });
  searchButton.forEach((button) => {
    button.addEventListener("click", showSearchContainer);
  });
  var menuToggleButton = document.getElementById("sf-header-menu-toggle");
  var menuContainer = document.querySelector(
    "header.site-header .header-col-utilities"
  );
  menuToggleButton?.addEventListener("click", toggleMobileMenu);
  menuContainer?.addEventListener("click", (event) => {
    const target = event.target;
    if (target === menuContainer) {
      toggleMobileMenu();
    }
  });
  function toggleMobileMenu() {
    if (menuContainer) {
      menuContainer.classList.toggle("mobile-menu-hidden");
    }
  }
  var checkoutFormSelector = ".wp-block-woocommerce-checkout.wc-block-checkout";
  var checkoutFormContainer = document.querySelector(checkoutFormSelector);
  function observerCheckoutContainerChanges() {
    if (!checkoutFormContainer) return;
    const emailElement = checkoutFormContainer.querySelector("#email");
    if (emailElement) {
      setReactInputValue(emailElement, "guestBuyer@example.xyz");
    }
    const cityElement = checkoutFormContainer.querySelector("#billing-city");
    if (cityElement) {
      setReactInputValue(cityElement, "NA");
    }
  }
  if (checkoutFormContainer) {
    const checkoutObserver = new MutationObserver(
      observerCheckoutContainerChanges
    );
    checkoutObserver.observe(checkoutFormContainer, {
      childList: true,
      attributes: false,
      subtree: true
    });
  }
  function setReactInputValue(inputElement, value) {
    if (!inputElement) return;
    const descriptor = Object.getOwnPropertyDescriptor(
      window.HTMLInputElement.prototype,
      "value"
    );
    const nativeInputValueSetter = descriptor?.set;
    if (nativeInputValueSetter) {
      nativeInputValueSetter.call(inputElement, value);
    } else {
      inputElement.value = value;
    }
    const event = new Event("input", { bubbles: true });
    inputElement.dispatchEvent(event);
  }
})();
