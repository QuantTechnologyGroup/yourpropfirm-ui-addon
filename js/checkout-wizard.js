/**
 * FUNDEDBIT checkout wizard — owns the 2-step navigation + JS-driven summary.
 *
 * Plugin 1.15 removed the main plugin's checkout-multistep.js, so this file now
 * OWNS the Challenge -> Information step engine (see initStepEngine below). It
 * reproduces that engine's DOM contract — toggling [data-checkout-step]
 * sections, driving the .checkout-step-indicator items, setting the #step=N
 * hash and firing `hashchange` — so the label + email-substep logic here keeps
 * working unchanged. The final "Proceed to Payment" rides WooCommerce's native
 * place-order submit (the configured gateway, e.g. Confirmo) — no resurrected
 * AJAX, no order-creation logic of our own.
 *
 * It also reads the checked eval-type / account-balance / platform radios and
 * the real window.ypfCheckoutStore to live-update the #ypf-order-summary panel,
 * toggles the "Challenge Requirement" dropdown, and applies the coupon via the
 * plugin's apply_coupon_action endpoint.
 *
 * Selection highlight + radio dots are pure CSS (:has(:checked)); this file
 * drives the summary numbers, the dropdown, the button labels, and the steps.
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

  // ---- Platform model (category-driven) state -------------------------------
  // The main plugin's JS re-renders the eval sub-categories (name-only) and the
  // account-balance products (store currency) on every selection change,
  // stripping the eval description/badge and the account currency. `maps` carries
  // those back (localized from PHP): categories{id:{name,description,badge}} and
  // products{id:{accountSize,accountCurrency,accountLabel}}. We re-apply them
  // after each re-render. `desired` remembers the user's chosen eval + account so
  // a Trading-Platform switch (which the plugin resets) can restore them — the
  // platforms mirror, so only currency/price should change, not the selection.
  var maps = (cfg.maps && typeof cfg.maps === "object") ? cfg.maps : { categories: {}, products: {} };
  if (!maps.categories) maps.categories = {};
  if (!maps.products) maps.products = {};
  var desired = { evalName: null, sizeKey: null };
  var restoring = false; // guard: suppress capture/restore re-entry during a restore
  // Authoritative cart-sync state (fixes the selection-sync race — see below).
  var syncTimer = null; // debounce handle for the authoritative cart sync
  var syncChain = Promise.resolve(); // serialize sync-cart calls (never two in flight)
  var submitting = false; // guard against a double place-order

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
      // Prefer the explicit name node (our eval-card markup adds badge + desc to
      // .category-option-content, so reading the whole block would include them).
      var c = opt && (opt.querySelector(".category-option-name") || opt.querySelector(".category-option-content"));
      if (c && c.textContent.trim()) return c.textContent.trim();
    }
    var path = store && store.config && store.config.currentCategoryPath;
    if (path && path.length && store.categories) {
      var leaf = store.categories[path[path.length - 1]];
      if (leaf && leaf.name) return leaf.name;
    }
    return "";
  }

  // Account balance label in the product's Account Currency — read from the
  // data-account-label attribute our form-product-selection.php override sets.
  // (The store's accountSizeFormatted uses the WRONG/store currency for it.)
  function selectedAccountLabel() {
    var r = document.querySelector('input[name="selected_product"]:checked');
    if (r && r.getAttribute("data-account-label")) return r.getAttribute("data-account-label");
    var dd = document.getElementById("selected_product");
    if (dd && dd.tagName === "SELECT" && dd.options[dd.selectedIndex]) {
      var v = dd.options[dd.selectedIndex].getAttribute("data-account-label");
      if (v) return v;
    }
    return "";
  }

  // ---- Platform model: re-apply data the plugin's re-render strips ----------
  var SEL = ".woocommerce-product-selection ";

  function jq() { return window.jQuery; }

  // Mirror fallback: eval sub-categories repeat under each platform, but the admin
  // may have filled the description/badge under only ONE platform. Key the
  // description/badge by eval NAME (preferring the first non-empty value) so every
  // platform's eval cards stay consistent without duplicating data in admin.
  var evalMetaByName = (function () {
    var byName = {};
    Object.keys(maps.categories).forEach(function (id) {
      var c = maps.categories[id];
      if (!c || !c.name) return;
      var slot = byName[c.name] || (byName[c.name] = { description: "", badge: "" });
      if (!slot.description && c.description) slot.description = c.description;
      if (!slot.badge && c.badge) slot.badge = c.badge;
    });
    return byName;
  })();

  function resolveEvalMeta(id, name) {
    var c = maps.categories[id] || {};
    var fb = evalMetaByName[name || c.name] || {};
    return {
      name: c.name || name || "",
      description: c.description || fb.description || "",
      badge: c.badge || fb.badge || "",
    };
  }

  // Eval sub-category cards: the plugin re-renders them as a flat name in
  // .category-option-content. Rebuild the design structure (badge + name + desc)
  // from the localized category map. Idempotent — safe to run on every change.
  function reinjectEvalMeta() {
    var labels = document.querySelectorAll(SEL + ".subcategory-section .category-option");
    Array.prototype.forEach.call(labels, function (label) {
      var radio = label.querySelector('input[type="radio"]');
      var content = label.querySelector(".category-option-content");
      if (!radio || !content) return;
      var nameEl = content.querySelector(".category-option-name");
      var name = nameEl ? nameEl.textContent.trim() : content.textContent.trim();
      var meta = resolveEvalMeta(radio.value, name);
      if (meta.name) name = meta.name;

      content.textContent = "";
      if (meta && meta.badge) {
        var b = document.createElement("span");
        b.className = "category-option-badge";
        b.textContent = meta.badge;
        content.appendChild(b);
      }
      var n = document.createElement("span");
      n.className = "category-option-name";
      n.textContent = name;
      content.appendChild(n);
      if (meta && meta.description) {
        var d = document.createElement("span");
        d.className = "category-option-desc";
        d.textContent = meta.description;
        content.appendChild(d);
      }
    });
  }

  // Account-balance pills: the plugin re-labels them in the store currency.
  // Re-format to the account currency from the product map and re-add the
  // data-account-* attributes the summary reads.
  function reformatAccountPills() {
    var labels = document.querySelectorAll(SEL + ".selected-product-section .product-option");
    Array.prototype.forEach.call(labels, function (label) {
      var radio = label.querySelector('input[type="radio"]');
      if (!radio) return;
      var meta = maps.products[radio.value];
      if (!meta) return;
      radio.setAttribute("data-account-label", meta.accountLabel || "");
      radio.setAttribute("data-account-currency", meta.accountCurrency || "");
      var nameEl = label.querySelector(".product-option-name");
      if (nameEl && meta.accountLabel) nameEl.textContent = meta.accountLabel;
    });
  }

  function reapplySelectionMeta() {
    reinjectEvalMeta();
    reformatAccountPills();
  }

  // Trading Platform = the checked level-0 category (Bybit / Platform 5).
  function platformName() {
    var r = document.querySelector('input[name="product_category_0"]:checked');
    if (!r) return "";
    var label = r.closest(".category-option");
    var n = label && (label.querySelector(".category-option-name") || label.querySelector(".category-option-content"));
    return n ? n.textContent.trim() : "";
  }

  // Currency follows the selected product's ACCOUNT currency (USDT / USD), not
  // the store currency. Read the data attr (re-added above), then the map.
  function accountCurrencyCode() {
    var r = document.querySelector('input[name="selected_product"]:checked');
    if (r) {
      var c = r.getAttribute("data-account-currency");
      if (c) return c;
      var m = maps.products[r.value];
      if (m && m.accountCurrency) return m.accountCurrency;
    }
    return cfg.currency || "USD";
  }

  // ---- Preserve eval + account across a Trading-Platform switch -------------
  function readEvalName(label) {
    var nameEl = label.querySelector(".category-option-name");
    if (nameEl) return nameEl.textContent.trim();
    var c = label.querySelector(".category-option-content");
    return c ? c.textContent.trim() : "";
  }

  function captureDesired() {
    var evalRadios = document.querySelectorAll(SEL + ".subcategory-section .category-radio:checked");
    if (evalRadios.length) {
      var label = evalRadios[evalRadios.length - 1].closest(".category-option");
      if (label) desired.evalName = readEvalName(label);
    }
    var prod = document.querySelector('input[name="selected_product"]:checked');
    if (prod) {
      var m = maps.products[prod.value];
      if (m) desired.sizeKey = String(m.accountSize);
    }
  }

  function findEvalRadioByName(name) {
    var labels = document.querySelectorAll(SEL + ".subcategory-section .category-option");
    for (var i = 0; i < labels.length; i++) {
      if (readEvalName(labels[i]) === name) return labels[i].querySelector('input[type="radio"]');
    }
    return null;
  }

  function findProductRadioBySize(sizeKey) {
    var radios = document.querySelectorAll(SEL + '.selected-product-section input[name="selected_product"]');
    for (var i = 0; i < radios.length; i++) {
      var m = maps.products[radios[i].value];
      if (m && String(m.accountSize) === String(sizeKey)) return radios[i];
    }
    return null;
  }

  // After a platform change the plugin auto-selects the first eval + last
  // product. Re-select the user's remembered eval + account (the platforms
  // mirror, so the same eval/size exist). Each re-select drives the plugin's
  // own synchronous (store-backed) re-render, so the cart/currency follow.
  function restoreSelection() {
    if (restoring) return;
    if (!desired.evalName && !desired.sizeKey) return;
    restoring = true;
    try {
      if (desired.evalName) {
        var evalRadio = findEvalRadioByName(desired.evalName);
        if (evalRadio && !evalRadio.checked && jq()) {
          evalRadio.checked = true;
          jq()(evalRadio).trigger("change");
        }
      }
      if (desired.sizeKey) {
        var prodRadio = findProductRadioBySize(desired.sizeKey);
        if (prodRadio && !prodRadio.checked && jq()) {
          prodRadio.checked = true;
          jq()(prodRadio).trigger("change");
        }
      }
    } finally {
      restoring = false;
    }
    reapplySelectionMeta();
    updateSummary();
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
    var account = sizeAttr || selectedAccountLabel() || accountSizeLabel(store, variation, product);
    var category = evalAttr || leafCategoryName(store);
    var productLabel =
      sizeAttr && evalAttr
        ? sizeAttr + " — " + evalAttr
        : account && category
          ? account + " — " + category
          : product.name || account || category || "";

    // Currency follows the selected product's ACCOUNT currency (platform model:
    // Bybit -> USDT, Platform 5 -> USD). Falls back to the store currency when no
    // account currency is set (non-platform products). NOTE: Base/Sub/Total below
    // intentionally stay in the STORE currency via fmtMoney — only this Currency
    // row + the account balance follow the account currency.
    var currency = accountCurrencyCode();

    // Trading Platform = the checked level-0 category (Bybit / Platform 5). Falls
    // back to the legacy trading_platform radio for the non-platform/variation model.
    var platLabel = platformName();
    if (!platLabel) {
      var platRadio = document.querySelector('input[name="trading_platform"]:checked');
      platLabel = platRadio
        ? (product.tradingPlatforms && product.tradingPlatforms[platRadio.value]) || platRadio.value
        : "";
    }

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
    // Capture the user's INTENT before the plugin reacts. mousedown fires before
    // the radio's change (and before the platform-switch cascade auto-selects the
    // first eval / last product), so we record exactly the eval/account the user
    // pressed — never the plugin's programmatic auto-selection.
    document.addEventListener("mousedown", function (e) {
      if (restoring || !e.target.closest) return;
      var label = e.target.closest("label.category-option, label.product-option");
      if (!label) return;
      if (label.closest(".subcategory-section")) {
        desired.evalName = readEvalName(label); // eval sub-category
      } else if (label.classList.contains("product-option")) {
        var radio = label.querySelector('input[type="radio"]');
        var m = radio && maps.products[radio.value];
        if (m) desired.sizeKey = String(m.accountSize); // account balance
      }
      // Platform (level-0) presses are handled by the change -> restore path.
    });

    document.addEventListener("change", function (e) {
      var t = e.target;
      if (!t) return;
      var cls = t.classList;
      var name = t.name || "";

      // Any category change makes the plugin re-render products and auto-select
      // the LAST one ($5,000). Restore the user's remembered eval + account once
      // the (synchronous, store-backed) cascade settles. restoreSelection is
      // idempotent, so it serves BOTH flows:
      //   - Platform switch (product_category_0): the plugin reset BOTH, so eval
      //     AND account are restored (unchanged behavior — "just like now").
      //   - Evaluation-type switch (product_category_1): desired.evalName is the
      //     eval the user just picked (mousedown), so the eval-restore is a no-op
      //     skip and only the account (desired.sizeKey) is restored.
      // The `restoring` guard prevents the nested cascade changes from
      // re-scheduling, and a redundant schedule is a harmless no-op.
      if (name.indexOf("product_category_") === 0 && !restoring) {
        setTimeout(restoreSelection, 0);
      }

      var relevant =
        (cls &&
          (cls.contains("variant-attribute-radio") ||
            cls.contains("platform-radio") ||
            cls.contains("category-radio") ||
            cls.contains("product-radio"))) ||
        name === "selected_product" ||
        name === "trading_platform" ||
        name.indexOf("product_category_") === 0;
      if (relevant) {
        // Selection changed: schedule the authoritative cart sync (debounced, so
        // it lands AFTER the plugin's/restore's racing syncCart calls and makes
        // the cart match the checked product), then re-apply our meta + summary.
        scheduleAuthoritativeSync();
        setTimeout(function () {
          reapplySelectionMeta();
          updateSummary();
        }, 0);
      }
    });

    // The plugin signals a full container re-render (ypf_containers_rerendered)
    // and WooCommerce fires updated_checkout after the cart sync — re-apply our
    // meta + summary on both (covers the async product/variants re-render).
    if (window.jQuery) {
      window
        .jQuery(document.body)
        .on("updated_checkout ypf_containers_rerendered", function () {
          setTimeout(function () {
            reapplySelectionMeta();
            updateSummary();
          }, 0);
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

  // ---- Step engine (Challenge <-> Information) ---------------------------
  // Replaces the main plugin's removed checkout-multistep.js. Owns step
  // visibility + the stepper; navigation goes through goToStep(), which also
  // emits `hashchange` so applyNavLabel()/initEmailSubstep() react as before.
  var TOTAL_STEPS = 2;

  function stepFromHash() {
    var m = location.hash.match(/step=(\d+)/);
    var n = m ? parseInt(m[1], 10) : 1;
    return n >= 1 && n <= TOTAL_STEPS ? n : 1;
  }

  function applyStepState(n) {
    document.querySelectorAll("[data-checkout-step]").forEach(function (el) {
      var s = parseInt(el.getAttribute("data-checkout-step"), 10);
      if (s === n) el.removeAttribute("hidden");
      else el.setAttribute("hidden", "");
    });
    document.querySelectorAll(".checkout-step-indicator__item").forEach(function (el) {
      var s = parseInt(el.getAttribute("data-step"), 10);
      var hint = el.querySelector(".checkout-step-indicator__hint");
      el.classList.remove("is-active", "is-upcoming", "is-completed");
      if (s === n) {
        el.classList.add("is-active");
        if (hint && el.dataset.hintActive) hint.textContent = el.dataset.hintActive;
        el.removeAttribute("role"); el.removeAttribute("tabindex"); el.style.cursor = "";
      } else if (s < n) {
        el.classList.add("is-completed");
        if (hint && el.dataset.hintCompleted) hint.textContent = el.dataset.hintCompleted;
        el.setAttribute("role", "button"); el.setAttribute("tabindex", "0"); el.style.cursor = "pointer";
      } else {
        el.classList.add("is-upcoming");
        if (hint && el.dataset.hintUpcoming) hint.textContent = el.dataset.hintUpcoming;
        el.removeAttribute("role"); el.removeAttribute("tabindex"); el.style.cursor = "";
      }
    });
    var prevBtn = document.querySelector("[data-checkout-step-prev]");
    if (prevBtn) {
      if (n > 1) prevBtn.removeAttribute("hidden");
      else prevBtn.setAttribute("hidden", "");
    }
    var form = document.querySelector("form.checkout");
    if (form) form.dataset.checkoutActiveStep = n;
  }

  function goToStep(n) {
    n = Math.max(1, Math.min(TOTAL_STEPS, n));
    applyStepState(n);
    var hash = "#step=" + n;
    if (location.hash !== hash) history.replaceState(null, "", hash);
    // Notify the label + email-substep listeners (they key off `hashchange`).
    try {
      window.dispatchEvent(new HashChangeEvent("hashchange"));
    } catch (e) {
      var ev = document.createEvent("Event");
      ev.initEvent("hashchange", true, true);
      window.dispatchEvent(ev);
    }
    var indicator = document.querySelector(".checkout-step-indicator");
    if (indicator) indicator.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  // ---- Authoritative cart sync (fixes the selection-sync race) --------------
  // A selection change fires several racing syncCart calls (the plugin
  // auto-selecting the last product + our restore re-selecting `desired` + the
  // user's own pick). sync-cart is last-write-wins server-side, so the WC cart —
  // and therefore the ORDER — can land on the wrong product while the summary
  // (which reads the checked radio) shows the right one. We own an authoritative
  // sync of the CURRENTLY checked product: debounced during interaction, and
  // mandatorily awaited right before placing the order.
  function selectionContainer() {
    return document.querySelector(".woocommerce-product-selection");
  }
  function checkedProductId() {
    var r = document.querySelector('input[name="selected_product"]:checked');
    if (!r) {
      var dd = document.getElementById("selected_product");
      if (dd && dd.tagName === "SELECT") r = dd;
    }
    return r && r.value ? parseInt(r.value, 10) : 0;
  }
  // Sync the checked product to the cart. Serialized via syncChain so two never
  // overlap; reads the product + REST nonce at execution time (always current).
  function syncCheckedProduct() {
    var c = selectionContainer();
    var base = c && c.getAttribute("data-rest-url");
    if (!base) return syncChain;
    syncChain = syncChain.then(function () {
      var pid = checkedProductId();
      if (!pid) return;
      var cc = selectionContainer();
      var nonce = (cc && cc.getAttribute("data-rest-nonce")) || "";
      return fetch(base + "product/sync-cart", {
        method: "POST",
        headers: { "Content-Type": "application/json", "X-WP-Nonce": nonce },
        credentials: "same-origin",
        body: JSON.stringify({ product_id: pid }),
      }).then(function () {}, function () {});
    });
    return syncChain;
  }
  function scheduleAuthoritativeSync() {
    if (syncTimer) clearTimeout(syncTimer);
    // Fire after the plugin's/restore's own syncCart calls for this interaction
    // have settled, so ours is the last write and the cart == the checked product.
    syncTimer = setTimeout(function () { syncTimer = null; syncCheckedProduct(); }, 450);
  }

  function doNativeSubmit() {
    var form = document.querySelector("form.checkout");
    // WooCommerce binds its place-order AJAX to the form's submit event, so
    // triggering the form submit runs the native flow (validation + the chosen
    // gateway). Preferred over clicking #place_order, which is in a hidden block.
    if (form && window.jQuery) { window.jQuery(form).submit(); return; }
    var placeOrder = document.getElementById("place_order");
    if (placeOrder) { placeOrder.click(); return; }
    if (form && form.requestSubmit) { form.requestSubmit(); return; }
    if (form) form.submit();
  }

  // Final step rides WooCommerce's native place-order. Before submitting, force
  // one authoritative sync of the checked product and WAIT for it, so the order
  // is always built from the product shown in the summary (never a race loser).
  function submitNativeOrder() {
    if (submitting) return;
    submitting = true;
    if (syncTimer) { clearTimeout(syncTimer); syncTimer = null; }
    var done = false;
    var go = function () {
      if (done) return;
      done = true;
      submitting = false;
      doNativeSubmit();
    };
    syncCheckedProduct().then(go, go);
    // Safety: never block the order indefinitely on a hung/failed sync.
    setTimeout(go, 2500);
  }

  function initStepEngine() {
    var steps = document.querySelectorAll("[data-checkout-step]");
    if (!steps.length) return;
    var nextBtn = document.querySelector("[data-checkout-step-next]");
    var prevBtn = document.querySelector("[data-checkout-step-prev]");

    if (nextBtn) {
      nextBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (nextBtn.disabled) return;
        var n = stepFromHash();
        if (n >= TOTAL_STEPS) submitNativeOrder();
        else goToStep(n + 1);
      });
    }
    if (prevBtn) {
      prevBtn.addEventListener("click", function (e) {
        e.preventDefault();
        goToStep(stepFromHash() - 1);
      });
    }
    document.querySelectorAll(".checkout-step-indicator__item").forEach(function (el) {
      function navIfCompleted() {
        if (el.classList.contains("is-completed")) {
          goToStep(parseInt(el.getAttribute("data-step"), 10));
        }
      }
      el.addEventListener("click", navIfCompleted);
      el.addEventListener("keydown", function (ev) {
        if (ev.key === "Enter" || ev.key === " ") { ev.preventDefault(); navIfCompleted(); }
      });
    });
    // Defensive: re-paint if the hash is changed by any other means.
    window.addEventListener("hashchange", function () {
      applyStepState(stepFromHash());
    });

    // Initial paint (step 1) — no dispatch; user navigation drives the rest.
    applyStepState(stepFromHash());
  }

  function init() {
    bindSelections();
    bindDropdown();
    bindNavLabel();
    initEmailSubstep();
    initStepEngine();
    initCoupon();
    // Platform model: apply the eval badge/desc + account currency to the initial
    // server render, remember the starting eval+account, then compute the summary.
    reapplySelectionMeta();
    captureDesired();
    updateSummary();
    // Re-apply the label shortly after load in case init order shifts.
    setTimeout(applyNavLabel, 50);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
