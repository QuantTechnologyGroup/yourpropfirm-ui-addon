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

  function fmtMoney(n) {
    return "$" + Number(n || 0).toLocaleString("en-US");
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
    var pid = (store.config && store.config.currentProductId) || null;
    if (!pid) {
      var sel = document.querySelector('input[name="selected_product"]:checked');
      pid = sel ? sel.value : Object.keys(store.products || {})[0];
    }
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

  function updateSummary() {
    var store = getStore();
    if (!store || !store.products) return;
    var product = currentProduct(store);
    if (!product) return;

    var attrs = checkedVariantAttrs();
    var variation = product.isVariable ? matchVariation(store, product, attrs) : null;
    var price = variation ? variation.price : product.price;
    var currency =
      (variation && variation.programCurrency) || product.programCurrency || cfg.currency || "USD";

    var evalLabel = attrName(product, "pa_evaluation", attrs["pa_evaluation"]);
    var sizeLabel = attrName(product, "pa_account_size", attrs["pa_account_size"]);
    var platRadio = document.querySelector('input[name="trading_platform"]:checked');
    var platLabel = platRadio
      ? (product.tradingPlatforms && product.tradingPlatforms[platRadio.value]) || platRadio.value
      : "";

    setText("product", (sizeLabel ? sizeLabel + " — " : "") + evalLabel);
    setText("category", evalLabel);
    setText("account", sizeLabel);
    setText("platform", platLabel);
    setText("currency", currency);
    setText("base", fmtMoney(price));
    setText("subtotal", fmtMoney(price));
    setText("total", fmtMoney(price));
  }

  // Delegated: the plugin re-renders the selection markup via innerHTML on every
  // change, so we listen on document rather than binding to specific nodes.
  function bindSelections() {
    document.addEventListener("change", function (e) {
      var t = e.target;
      if (!t) return;
      var isVariantOrPlatform =
        (t.classList &&
          (t.classList.contains("variant-attribute-radio") ||
            t.classList.contains("platform-radio"))) ||
        t.name === "selected_product" ||
        t.name === "trading_platform";
      if (isVariantOrPlatform) {
        // Let the plugin's handler re-render/sync first, then recompute.
        setTimeout(updateSummary, 0);
      }
    });
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
      // Challenge Requirement is step-1 only; Payment/Coupon is full-form only.
      if (challengeReq) challengeReq.classList.add("ypf-field-hidden");
      if (paymentCoupon) paymentCoupon.classList.add("ypf-field-hidden");
    }

    function enterFullForm() {
      expanded = true;
      getNonEmailRows().forEach(function (row) { row.classList.remove("ypf-field-hidden"); });
      nextBtn.classList.add("ypf-field-hidden");
      substepNav.classList.remove("ypf-field-hidden");
      if (sidebarNav) sidebarNav.classList.remove("ypf-field-hidden");
      if (secureCheckout) secureCheckout.classList.remove("ypf-field-hidden");
      // Challenge Requirement stays hidden on step 2; Payment/Coupon appears now.
      if (challengeReq) challengeReq.classList.add("ypf-field-hidden");
      if (paymentCoupon) paymentCoupon.classList.remove("ypf-field-hidden");
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
