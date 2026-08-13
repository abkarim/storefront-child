(() => {
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

  // assets/ts/util/element.ts
  function createHTMLElement(tag, attributes = {}, children) {
    const element = document.createElement(tag);
    for (const [key, val] of Object.entries(attributes)) {
      element.setAttribute(key, val);
    }
    if (children) {
      if (typeof children === "string") {
        element.textContent = children;
      } else {
        element.appendChild(children);
      }
    }
    return element;
  }

  // assets/ts/compareContents.ts
  document.addEventListener("DOMContentLoaded", () => {
    getProducts();
  });
  function handleContents(l) {
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
    const products2 = getCompareProducts();
    handleContents(products2.length);
    for (const id of products2) {
      try {
        const response = await fetch(
          `/wp-json/wc/store/v1/products/${id}`
        );
        if (!response.ok) continue;
        const product = await response.json();
        const imageSrc = product.images[0]?.src || "";
        const stockClass = product.is_in_stock ? "sf-stock-in" : "sf-stock-out";
        const stockText = product.is_in_stock ? "In Stock" : "Out of Stock";
        renderProductRow(product);
        console.log(product);
      } catch (error) {
        console.error(
          `Could not compile column row data for item index ID ${id}:`,
          error
        );
      }
    }
  }
  var tableElements = document.getElementById("sf-compare-table");
  var imagesTableRow = document.getElementById("row-product-images");
  var nameTableRow = document.getElementById("row-product-titles");
  var priceTableRow = document.getElementById("row-product-prices");
  var availTableRow = document.getElementById("row-product-stock");
  var purchaseTableRow = document.getElementById("row-product-buy");
  var actionTableRow = document.getElementById("row-product-triggers");
  var clearAllButton = document.querySelector("#sf-compare-clear-all button");
  if (clearAllButton) {
    clearAllButton.addEventListener("click", () => {
      const products2 = getCompareProducts();
      products2.forEach((id) => {
        removeProductRowById(id);
      });
    });
  }
  function removeProductRow(event) {
    const target = event.currentTarget;
    if (!target) return;
    const id = target.dataset.id;
    if (!id) return;
    removeProductRowById(id);
  }
  function removeProductRowById(id) {
    const tableDataElements = [
      ...tableElements.querySelectorAll(`[data-product-id="${id}"]`)
    ];
    tableDataElements.forEach((tde) => tde.remove());
    removeCompareProduct(id);
    renderCompareCount();
    handleContents(getCompareProducts().length);
  }
  function renderProductRow(product) {
    const tdAttr = {
      "data-product-id": product.id.toString()
    };
    const imageElement = createHTMLElement("img", {
      src: product.images[0].thumbnail,
      alt: product.images[0].alt
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
      stockText
    );
    const availTD = createHTMLElement("td", tdAttr, availElement);
    availTableRow?.appendChild(availTD);
    const buyLinkElement = createHTMLElement(
      "a",
      {
        href: product.permalink,
        class: "button alt sf-compare-action-btn"
      },
      "View Product"
    );
    const purchaseTD = createHTMLElement("td", tdAttr, buyLinkElement);
    purchaseTableRow?.appendChild(purchaseTD);
    const removeBtnElement = createHTMLElement(
      "button",
      {
        class: "sf-del-col button danger",
        "data-id": product.id.toString()
      },
      "Remove"
    );
    removeBtnElement.addEventListener("click", removeProductRow);
    const actionTD = createHTMLElement("td", tdAttr, removeBtnElement);
    actionTableRow?.appendChild(actionTD);
  }
})();
