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
    const quickViewContainer = product.querySelector(
      ".sf-archive-action-buttons-group"
    );
    if (!quickViewContainer) return;
    const productId = quickViewContainer.getAttribute("data-product-id");
    product.addEventListener(
      "mouseenter",
      () => {
        const isInCompare = isCompareProductExists(productId);
        if (isInCompare) {
          const element = quickViewContainer.querySelector(
            compareButtonSelector
          );
          if (element) {
            element.setAttribute("added", "true");
          }
        }
      },
      { once: true }
    );
    quickViewContainer.addEventListener("click", (event) => {
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
        console.log(`Quick view product ID: ${productId}`);
      }
    });
  });

  // assets/ts/categoriesScroller.ts
  document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(
      ".wp-block-woocommerce-product-categories ul.wc-block-product-categories-list.wc-block-product-categories-list--has-images.wc-block-product-categories-list--depth-0"
    );
    if (!container) return;
    let scrollInterval = null;
    const scrollSpeed = 2;
    const intervalTime = 30;
    const lastTarget = container.scrollWidth - container.clientWidth;
    function startAutoScroll() {
      container.scrollLeft += scrollSpeed;
      if (lastTarget !== container.scrollLeft) {
        scrollInterval = setTimeout(startAutoScroll, intervalTime);
      } else {
        container.scrollLeft = 0;
        scrollInterval = setTimeout(startAutoScroll, 1e3);
      }
    }
    function stopAutoScroll() {
      if (scrollInterval) {
        clearTimeout(scrollInterval);
        scrollInterval = null;
      }
    }
    container.addEventListener("mouseenter", stopAutoScroll);
    container.addEventListener("mouseleave", startAutoScroll);
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
})();
