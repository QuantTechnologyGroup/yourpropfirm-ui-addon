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

  var cfg = window.ypfCheckoutWizard || {};
  var currency = cfg.currency || "USD";
  var prices = cfg.prices || {};
  var continueLabel = cfg.continueLabel || "Continue";
  var payLabel = cfg.payLabel || "Proceed to Payment";

  function fmtMoney(n) {
    return "$" + Number(n || 0).toLocaleString("en-US");
  }

  function priceFor(evalId, balance) {
    return (prices[evalId] && prices[evalId][balance]) || 0;
  }

  function setText(key, val) {
    document.querySelectorAll('[data-ypf="' + key + '"]').forEach(function (el) {
      el.textContent = val;
    });
  }

  function getChecked(name) {
    return document.querySelector('input[name="' + name + '"]:checked');
  }

  function updateSummary() {
    var ev = getChecked("ypf_eval_type");
    var bal = getChecked("ypf_account_balance");
    var plat = getChecked("ypf_platform");
    if (!ev || !bal) return;

    var evalLabel = ev.getAttribute("data-category") || ev.getAttribute("data-label") || "";
    var balance = parseInt(bal.value, 10);
    var balLabel = bal.getAttribute("data-label") || fmtMoney(balance);
    var platLabel = plat ? plat.getAttribute("data-label") || "" : "";
    var price = priceFor(ev.value, balance);

    setText("product", balLabel + " — " + evalLabel);
    setText("category", evalLabel);
    setText("account", balLabel);
    setText("platform", platLabel);
    setText("currency", currency);
    setText("base", fmtMoney(price));
    setText("subtotal", fmtMoney(price));
    setText("total", fmtMoney(price));
  }

  function bindSelections() {
    var inputs = document.querySelectorAll(
      'input[name="ypf_eval_type"], input[name="ypf_account_balance"], input[name="ypf_platform"]'
    );
    inputs.forEach(function (el) {
      el.addEventListener("change", updateSummary);
    });
  }

  function bindDropdown() {
    var toggle = document.querySelector("[data-ypf-toggle]");
    var details = document.querySelector("[data-ypf-details]");
    if (!toggle || !details) return;
    toggle.addEventListener("click", function () {
      var open = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", open ? "false" : "true");
      details.hidden = open;
    });
  }

  function bindNavLabel() {
    var nextBtn = document.querySelector("[data-checkout-step-next]");
    if (!nextBtn) return;

    // Best effort: nudge the multistep label object (it computes NEXT_LABELS
    // once at parse time, so we also re-apply the text after each step change).
    if (window.ypfMultistep && window.ypfMultistep.labels) {
      window.ypfMultistep.labels.enterBillingDetails = continueLabel;
      window.ypfMultistep.labels.payAndGetAccess = payLabel;
    }

    function currentStep() {
      var m = location.hash.match(/step=(\d)/);
      return m ? m[1] : "1";
    }
    function applyLabel() {
      if (nextBtn.disabled) return; // don't clobber the loading state
      nextBtn.textContent = currentStep() === "2" ? payLabel : continueLabel;
    }

    applyLabel();
    // Run after the multistep handler (which also sets the text on hashchange).
    window.addEventListener("hashchange", function () {
      setTimeout(applyLabel, 0);
    });
    if (window.jQuery) {
      window.jQuery(document.body).on("updated_checkout", function () {
        setTimeout(applyLabel, 0);
      });
    }
  }

  function init() {
    bindSelections();
    bindDropdown();
    bindNavLabel();
    updateSummary();
    // Re-apply the label shortly after load in case multistep init runs later.
    setTimeout(function () {
      var nextBtn = document.querySelector("[data-checkout-step-next]");
      if (nextBtn && !nextBtn.disabled) {
        var m = location.hash.match(/step=(\d)/);
        nextBtn.textContent = (m && m[1] === "2") ? payLabel : continueLabel;
      }
    }, 50);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
