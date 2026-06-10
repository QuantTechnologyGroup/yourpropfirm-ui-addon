/**
 * FUNDEDBIT checkout wizard — static, JS-driven interactivity.
 *
 * Reads the checked eval-type / account-balance / platform radios and the
 * localized `ypfCheckoutWizard` price catalog, and live-updates the static
 * #ypf-order-summary panel. Also toggles the "Challenge Requirement" dropdown
 * and relabels the multistep Next button to "Continue" / "Proceed to Payment".
 *
 * Selection highlight + radio dots are pure CSS (:has(:checked)); this file
 * only drives the summary numbers, the dropdown, and the button labels.
 * It deliberately does NOT touch any WooCommerce review-order selectors.
 */
(function () {
  "use strict";

  // Always start at step 1 on page load. Runs synchronously before any
  // DOMContentLoaded handler (including the main plugin's step-restore logic)
  // so the plugin sees no hash and initialises at step 1.
  if (location.hash.match(/step=/)) {
    history.replaceState(null, null, location.pathname + location.search);
  }

  var cfg = window.ypfCheckoutWizard || {};
  var currency = cfg.currency || "USD";
  var continueLabel = cfg.continueLabel || "Continue";
  var payLabel = cfg.payLabel || "Proceed to Payment";
  var lastBase = 0; // last store-computed price (pre-coupon), for discount math

  function decodeEntities(s) {
    if (!s || String(s).indexOf("&") === -1) return s || "";
    var t = document.createElement("textarea");
    t.innerHTML = s;
    return t.value;
  }

  // Format using WooCommerce's currency format from the store (symbol / position /
  // decimals / separators), so it follows the WC setting instead of a hardcoded $.
  function fmtMoney(n) {
    var store = getStore();
    var cf = (store && store.currencyFormat) || {};
    var symbol = decodeEntities(cf.symbol || "$");
    var dec = cf.decimals != null ? cf.decimals : 0;
    var s = (Number(n) || 0).toFixed(dec);
    var parts = s.split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, cf.thousand_sep != null ? cf.thousand_sep : ",");
    var out = parts.join(cf.decimal_sep != null ? cf.decimal_sep : ".");
    return cf.position === "right" || cf.position === "right_space" ? out + symbol : symbol + out;
  }

  function setText(key, val) {
    document.querySelectorAll('[data-ypf="' + key + '"]').forEach(function (el) {
      el.textContent = val;
    });
  }

  // ---- Order summary driven by the REAL store (window.ypfCheckoutStore) ----
  // No static price matrix: product / category / account / platform / price all
  // come from the selected variation in the store, matched against the checked
  // variation-attribute radios + trading platform. Degrades gracefully if the
  // store is absent (e.g. reset / competition products).
  function getStore() { return window.ypfCheckoutStore || null; }

  function currentProduct(store) {
    // Prefer the live selection (radio or dropdown) so changing the product on the
    // production category-drill-down updates the summary; fall back to the store's
    // initial product / the first product.
    var sel = document.querySelector('input[name="selected_product"]:checked');
    if (!sel) {
      var dd = document.getElementById("selected_product");
      if (dd && dd.tagName === "SELECT") sel = dd;
    }
    var pid = sel && sel.value ? sel.value : (store.config && store.config.currentProductId) || Object.keys(store.products || {})[0];
    return store.products ? store.products[pid] : null;
  }

  function checkedVariantAttrs() {
    var attrs = {};
    document.querySelectorAll(".variant-attribute-radio:checked").forEach(function (r) {
      var a = r.getAttribute("data-attribute");
      if (a) attrs[a] = r.value;
    });
    return attrs;
  }

  function matchVariation(store, product, attrs) {
    var ids = (product && product.variationIds) || [];
    for (var i = 0; i < ids.length; i++) {
      var v = store.variations[ids[i]];
      if (!v) continue;
      var ok = true;
      for (var k in attrs) {
        if (String(v.attributes[k]) !== String(attrs[k])) { ok = false; break; }
      }
      if (ok) return v;
    }
    return null;
  }

  function attrName(product, attr, slug) {
    var a = product.attributes && product.attributes[attr];
    if (a && a.options) {
      for (var i = 0; i < a.options.length; i++) {
        if (String(a.options[i].slug) === String(slug)) return a.options[i].name;
      }
    }
    return slug || "";
  }

  function currencySymbol(store, variation, product) {
    var cf = (store && store.currencyFormat) || {};
    return decodeEntities(
      (variation && variation.programCurrencySymbol) ||
        (product && product.programCurrencySymbol) ||
        cf.symbol ||
        "$"
    );
  }

  // Account size from REAL program data — production category-drill-down products
  // have no pa_account_size variation attribute, so derive it from the program.
  function accountSizeLabel(store, variation, product) {
    if (product && product.accountSizeFormatted) return decodeEntities(product.accountSizeFormatted);
    var raw = (variation && variation.programAccountSize) || (product && product.programAccountSize) || "";
    if (!raw) return "";
    raw = String(raw);
    var sym = currencySymbol(store, variation, product);
    return raw.indexOf(sym) === 0 ? raw : sym + raw;
  }

  // Leaf product category name — deepest checked category radio (live), falling
  // back to the store's current category path.
  function leafCategoryName(store) {
    var radios = document.querySelectorAll('input[name^="product_category_"]:checked');
    if (radios.length) {
      var last = radios[radios.length - 1];
      var opt = last.closest(".category-option");
      var c = opt && opt.querySelector(".category-option-content");
      if (c && c.textContent.trim()) return c.textContent.trim();
    }
    var path = store && store.config && store.config.currentCategoryPath;
    if (path && path.length && store.categories) {
      var leaf = store.categories[path[path.length - 1]];
      if (leaf && leaf.name) return leaf.name;
    }
    return "";
  }

  function updateSummary() {
    var store = getStore();
    if (!store || !store.products) return;
    var product = currentProduct(store);
    if (!product) return;

    var attrs = checkedVariantAttrs();
    var variation = product.isVariable ? matchVariation(store, product, attrs) : null;
    var price = variation ? variation.price : product.price;

    // Variation-attribute labels (our local FUNDEDBIT seed). Empty on production's
    // category-driven products → fall back to real program/category data so
    // Product / Category / Account always show values.
    var evalAttr = attrName(product, "pa_evaluation", attrs["pa_evaluation"]);
    var sizeAttr = attrName(product, "pa_account_size", attrs["pa_account_size"]);
    var account = sizeAttr || accountSizeLabel(store, variation, product);
    var category = evalAttr || leafCategoryName(store);
    var productLabel =
      sizeAttr && evalAttr
        ? sizeAttr + " — " + evalAttr
        : account && category
          ? account + " — " + category
          : product.name || account || category || "";

    // Currency follows WooCommerce (cfg.currency = get_woocommerce_currency()).
    var currency = cfg.currency || (variation && variation.programCurrency) || product.programCurrency || "USD";

    var platRadio = document.querySelector('input[name="trading_platform"]:checked');
    var platLabel = platRadio
      ? (product.tradingPlatforms && product.tradingPlatforms[platRadio.value]) || platRadio.value
      : "";

    lastBase = Number(price) || 0;
    setText("product", productLabel);
    setText("category", category);
    setText("account", account);
    setText("platform", platLabel);
    setText("currency", currency);
    setText("base", fmtMoney(price));
    setText("subtotal", fmtMoney(price));
    setText("total", fmtMoney(price));
  }

  // ---- Coupon: real apply via the plugin's `apply_coupon_action` endpoint ----
  // No nonce required (verified in the plugin); returns the discounted cart
  // total. We reflect it in the static summary (Discount row + new Total).
  function parseMoney(html) {
    if (html == null) return null;
    var n = parseFloat(String(html).replace(/<[^>]*>/g, " ").replace(/[^0-9.,]/g, "").replace(/,/g, ""));
    return isNaN(n) ? null : n;
  }

  function showCouponMsg(text, ok) {
    var el = document.getElementById("ypf-coupon-msg");
    if (!el) return;
    el.textContent = text;
    el.classList.remove("ypf-field-hidden", "is-error", "is-ok");
    el.classList.add(ok ? "is-ok" : "is-error");
  }

  function applyDiscount(totalNum, code) {
    var row = document.getElementById("ypf-summary-discount-row");
    if (totalNum == null) return;
    var discount = lastBase - totalNum;
    if (discount > 0.001) {
      setText("discount", "-" + fmtMoney(discount));
      setText("discount-code", code ? "(" + code + ")" : "");
      if (row) row.classList.remove("ypf-field-hidden");
    } else if (row) {
      row.classList.add("ypf-field-hidden");
    }
    setText("total", fmtMoney(totalNum));
  }

  function initCoupon() {
    var input = document.getElementById("ypf-coupon-input");
    var btn = document.getElementById("ypf-coupon-apply");
    if (!input || !btn) return;
    var ajaxUrl =
      cfg.ajaxUrl ||
      (window.yourpropfirm_purchase && window.yourpropfirm_purchase.ajax_url) ||
      "/wp-admin/admin-ajax.php";

    btn.addEventListener("click", function () {
      var code = (input.value || "").trim();
      if (!code) { showCouponMsg("Please enter a coupon code.", false); return; }
      var emailEl = document.getElementById("billing_email");
      var email = emailEl ? emailEl.value || "" : "";
      var orig = btn.textContent;
      btn.disabled = true;
      btn.textContent = "Applying…";

      fetch(ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        credentials: "same-origin",
        body:
          "action=apply_coupon_action&coupon_code=" +
          encodeURIComponent(code) +
          "&billing_email=" +
          encodeURIComponent(email),
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.success) {
            showCouponMsg((res.data && res.data.message) || "Coupon applied.", true);
            applyDiscount(parseMoney(res.data && res.data.total), code);
            if (window.jQuery) window.jQuery(document.body).trigger("update_checkout");
          } else {
            showCouponMsg((res.data && res.data.message) || "Invalid coupon code.", false);
          }
        })
        .catch(function () { showCouponMsg("Error applying coupon. Please try again.", false); })
        .finally(function () {
          btn.disabled = false;
          btn.textContent = orig;
        });
    });
  }

  // Delegated: the plugin re-renders the selection markup via innerHTML on every
  // change, so we listen on document rather than binding to specific nodes.
  function bindSelections() {
    document.addEventListener("change", function (e) {
      var t = e.target;
      if (!t) return;
      var cls = t.classList;
      var relevant =
        (cls &&
          (cls.contains("variant-attribute-radio") ||
            cls.contains("platform-radio") ||
            cls.contains("category-radio") ||
            cls.contains("product-radio"))) ||
        t.name === "selected_product" ||
        t.name === "trading_platform" ||
        (t.name && t.name.indexOf("product_category_") === 0);
      if (relevant) {
        // Let the plugin's handler re-render/sync first, then recompute.
        setTimeout(updateSummary, 0);
      }
    });
    // The plugin re-renders the selection (innerHTML) + syncs the cart, then fires
    // updated_checkout — recompute the summary then too (covers the production
    // category-drill-down where the product/variants re-render asynchronously).
    if (window.jQuery) {
      window.jQuery(document.body).on("updated_checkout", function () {
        setTimeout(updateSummary, 0);
      });
    }
  }

  function bindDropdown() {
    var toggle = document.querySelector("[data-ypf-toggle]");
    var details = document.querySelector("[data-ypf-details]");
    if (!toggle || !details) return;
    toggle.addEventListener("click", function () {
      var open = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", open ? "false" : "true");
      details.classList.toggle("is-collapsed", open);
    });
  }

  function applyNavLabel() {
    var nextBtn = document.querySelector("[data-checkout-step-next]");
    if (!nextBtn || nextBtn.disabled) return; // don't clobber the loading state
    var m = location.hash.match(/step=(\d)/);
    // Label is also set at the source via window.ypfMultistep.labels (the add-on's
    // `before` inline script), so this is a defensive re-apply. Use textContent —
    // the trailing arrow is a CSS ::after on .ypf-nav-next, so it survives the
    // multistep JS overwriting the label via textContent.
    nextBtn.textContent = m && m[1] === "2" ? payLabel : continueLabel;
  }

  function bindNavLabel() {
    var nextBtn = document.querySelector("[data-checkout-step-next]");
    if (!nextBtn) return;

    // Best effort: nudge the multistep label object (it computes NEXT_LABELS
    // once at parse time, so we also re-apply the label after each step change).
    if (window.ypfMultistep && window.ypfMultistep.labels) {
      window.ypfMultistep.labels.enterBillingDetails = continueLabel;
      window.ypfMultistep.labels.payAndGetAccess = payLabel;
    }

    applyNavLabel();
    // Run after the multistep handler (which also sets the text on hashchange).
    window.addEventListener("hashchange", function () {
      setTimeout(applyNavLabel, 0);
    });
    if (window.jQuery) {
      window.jQuery(document.body).on("updated_checkout", function () {
        setTimeout(applyNavLabel, 0);
      });
    }
  }

  function initEmailSubstep() {
    var substepNav = document.getElementById("ypf-substep-nav");
    var nextBtn    = document.getElementById("ypf-email-next");
    var prevBtn    = document.getElementById("ypf-email-prev");
    var sidebarNav = document.querySelector(".checkout-step-nav");
    var secureCheckout = document.querySelector(".ypf-secure-checkout");
    var challengeReq = document.getElementById("ypf-challenge-req");
    var paymentCoupon = document.getElementById("ypf-payment-coupon");
    var billingEmail = document.getElementById("billing_email");
    if (!substepNav || !nextBtn || !prevBtn || !billingEmail) return;

    // All billing .form-row elements except the email one.
    var billingWrapper = document.querySelector(".woocommerce-billing-fields__field-wrapper");
    function getNonEmailRows() {
      if (!billingWrapper) return [];
      return Array.prototype.filter.call(
        billingWrapper.querySelectorAll(".form-row"),
        function (row) { return !row.querySelector("#billing_email"); }
      );
    }

    var expanded = false; // tracks whether the full form is visible

    function enterEmailSubstep() {
      expanded = false;
      getNonEmailRows().forEach(function (row) { row.classList.add("ypf-field-hidden"); });
      substepNav.classList.remove("ypf-field-hidden");
      nextBtn.classList.remove("ypf-field-hidden");
      if (sidebarNav) sidebarNav.classList.add("ypf-field-hidden");
      // "Secure checkout" assurance shows on screen 1 and the final (full-form)
      // screen only — hide it on the intermediate email sub-step.
      if (secureCheckout) secureCheckout.classList.add("ypf-field-hidden");
      // Challenge Requirement is step-1 only; Payment/Coupon and consent are full-form only.
      if (challengeReq) challengeReq.classList.add("ypf-field-hidden");
      if (paymentCoupon) paymentCoupon.classList.add("ypf-field-hidden");
      var consentText = document.querySelector(".ypf-consent-text");
      if (consentText) consentText.classList.add("ypf-field-hidden");
    }

    function enterFullForm() {
      expanded = true;
      getNonEmailRows().forEach(function (row) { row.classList.remove("ypf-field-hidden"); });
      nextBtn.classList.add("ypf-field-hidden");
      substepNav.classList.remove("ypf-field-hidden");
      if (sidebarNav) sidebarNav.classList.remove("ypf-field-hidden");
      if (secureCheckout) secureCheckout.classList.remove("ypf-field-hidden");
      // Challenge Requirement stays hidden on step 2; Payment/Coupon and consent appear now.
      if (challengeReq) challengeReq.classList.add("ypf-field-hidden");
      if (paymentCoupon) paymentCoupon.classList.remove("ypf-field-hidden");
      var consentText = document.querySelector(".ypf-consent-text");
      if (consentText) consentText.classList.remove("ypf-field-hidden");
    }

    // Activate email sub-step when step 2 section becomes visible.
    // MutationObserver is primary (fires as soon as the plugin removes [hidden]);
    // hashchange is a fallback for plugins that update the hash separately.
    var step2Section = document.querySelector('[data-checkout-step="2"]');
    if (step2Section) {
      new MutationObserver(function () {
        if (!step2Section.hidden) enterEmailSubstep();
      }).observe(step2Section, { attributes: true, attributeFilter: ["hidden"] });
    }
    window.addEventListener("hashchange", function () {
      if ((location.hash.match(/step=(\d+)/) || [])[1] === "2") enterEmailSubstep();
    });

    // Previous: back to email-only (if full form showing) or back to step 1.
    prevBtn.addEventListener("click", function () {
      if (expanded) {
        enterEmailSubstep();
      } else {
        // Restore sidebar nav before the main plugin navigates to step 1.
        if (sidebarNav) sidebarNav.classList.remove("ypf-field-hidden");
        var sidebarPrev = document.querySelector("[data-checkout-step-prev]");
        if (sidebarPrev) sidebarPrev.click();
      }
    });

    // Continue: validate email, expand to full billing form.
    // Re-query billing_email each time — WooCommerce AJAX can re-render the field.
    nextBtn.addEventListener("click", function () {
      var emailField = document.getElementById("billing_email");
      if (!emailField || !emailField.value || !emailField.validity.valid) {
        if (emailField) emailField.reportValidity();
        return;
      }
      enterFullForm();
    });

    // Belt-and-suspenders: whenever step 1 becomes visible, ensure sidebar nav is shown.
    var step1Section = document.querySelector('[data-checkout-step="1"]');
    if (step1Section) {
      new MutationObserver(function () {
        if (!step1Section.hidden) {
          if (sidebarNav) sidebarNav.classList.remove("ypf-field-hidden");
          if (secureCheckout) secureCheckout.classList.remove("ypf-field-hidden");
          // Back on step 1: Challenge Requirement returns, Payment/Coupon hides.
          if (challengeReq) challengeReq.classList.remove("ypf-field-hidden");
          if (paymentCoupon) paymentCoupon.classList.add("ypf-field-hidden");
        }
      }).observe(step1Section, { attributes: true, attributeFilter: ["hidden"] });
    }

    // Intercept sidebar Prev when full form is visible → go back to email sub-step.
    if (sidebarNav) {
      var sidebarPrevBtn = sidebarNav.querySelector("[data-checkout-step-prev]");
      if (sidebarPrevBtn) {
        sidebarPrevBtn.addEventListener("click", function (e) {
          if (expanded) {
            e.stopImmediatePropagation();
            enterEmailSubstep();
          }
        }, true);
      }
    }
  }

  function init() {
    bindSelections();
    bindDropdown();
    bindNavLabel();
    initEmailSubstep();
    initCoupon();
    updateSummary();
    // Re-apply the label shortly after load in case multistep init runs later.
    setTimeout(applyNavLabel, 50);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
