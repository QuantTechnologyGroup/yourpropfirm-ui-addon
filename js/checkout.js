// Main checkout script for YourPropFirm
// All initialization and event bindings are handled in a single $(document).ready block for clarity and maintainability
console.log("[YPF] checkout.js parsed — bundle debug build");
(function ($) {
  var i18n = window.ypfCheckoutWizard || {};

  /**
   * Get cookie value by name
   * @param {string} name - Cookie name
   * @return {string|null} Cookie value or null if not found
   */
  function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  }

  /**
   * Apply coupon automatically from cookie if exists
   * Cookie name: _ypf_coupon_cookie
   */
  function applyCouponFromCookie() {
    const couponCode = getCookie("_ypf_coupon_cookie");

    if (couponCode) {
      // Set coupon code to input field if exists
      if ($("#coupon_code").length) {
        $("#coupon_code").val(couponCode);
      }

      // Apply the coupon via AJAX
      $.ajax({
        type: "POST",
        url: yourpropfirm_purchase.ajax_url,
        data: {
          action: "apply_coupon_action",
          coupon_code: couponCode,
          billing_email: $('input[name="billing_email"]').val() || "",
        },
        success: function (response) {
          // Trigger checkout update to reflect the coupon
          $(document.body).trigger("update_checkout");
          if (response.data && response.data.message) {
            showCouponMessage(response.data.message, response.success);
          }
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.log("Error applying coupon from cookie:", errorThrown);
        },
      });
    }
  }

  /**
   * Fill WooCommerce billing fields with user profile data
   * @param {string} email - User email
   * @param {object} profile - User profile object
   */
  function fillBillingFields(email, profile) {
    // Fill and lock the email field
    const emailField = $('input[name="billing_email"]');
    if (emailField.length) {
      emailField.val(email).attr("readonly", "readonly");
    }
    // Fill other billing fields
    $("#billing_first_name").val(profile.firstname);
    $("#billing_last_name").val(profile.lastname);
    $("#billing_phone").val(profile.phone);
    $("#billing_postcode").val(profile.zipCode);
    $("#billing_city").val(profile.city);
    $("#billing_address_1").val(profile.addressLine);
    $("#billing_country").val(profile.countryID).trigger("change");
  }

  /**
   * Apply coupon using AJAX
   */
  function applyCoupon() {
    var coupon_code = $("#coupon_code").val();
    if (coupon_code) {
      $.ajax({
        type: "POST",
        url: yourpropfirm_purchase.ajax_url,
        data: {
          action: "apply_coupon_action",
          coupon_code: coupon_code,
          billing_email: $('input[name="billing_email"]').val() || "",
        },
        beforeSend: function () {
          // Show loading state
          $(".coupon-apply-btn").text(i18n.applyingLabel || "Applying...");
        },
        success: function (response) {
          // Clear input and show success/error message
          $("#coupon_code").val("");
          $(document.body).trigger("update_checkout");
          var msg =
            (response.data && response.data.message) ||
            (i18n.couponAppliedMsg || "Coupon applied successfully!");
          showCouponMessage(msg, response.success);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          // Show error message
          showCouponMessage((i18n.couponErrorPrefix || "Error applying coupon: ") + errorThrown, false);
        },
        complete: function () {
          // Reset button text
          $(".coupon-apply-btn").text(i18n.applyLabel || "Apply");
        },
      });
    }
  }

  /**
   * Show coupon message in WooCommerce NoticeGroup
   * @param {string} message - Message to display
   * @param {boolean} isSuccess - Whether this is a success message
   */
  function showCouponMessage(message, isSuccess) {
    var noticeClass = isSuccess ? "woocommerce-message" : "woocommerce-error";
    var noticeHtml =
      '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout yourpropfirm-coupon-notice">' +
      '<div role="alert"><ul class="' +
      noticeClass +
      '" tabindex="-1">' +
      "<li>" +
      message +
      "</li>" +
      "</ul></div></div>";

    // Remove existing coupon notices
    $(".yourpropfirm-coupon-notice").remove();

    // Insert at top of checkout form
    var $form = $("form.checkout");
    if ($form.length) {
      $form.prepend(noticeHtml);
      $(".yourpropfirm-coupon-notice")[0].scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }

    // Auto-remove after 5 seconds
    setTimeout(function () {
      $(".yourpropfirm-coupon-notice").fadeOut(300, function () {
        $(this).remove();
      });
    }, 5000);
  }

  /**
   * Show a floating notice message to the user
   * @param {string} message - Message to display
   */
  function showNoticeMessage(message) {
    // Remove existing notices
    const existingNotices = document.querySelectorAll(".yourpropfirm-notice");
    existingNotices.forEach((notice) => notice.remove());
    // Create notice element
    const notice = document.createElement("div");
    notice.className = "yourpropfirm-notice";
    notice.style.cssText = `
			position: fixed;
			top: 20px;
			right: 20px;
			background: #f8d7da;
			color: #721c24;
			padding: 12px 20px;
			border: 1px solid #f5c6cb;
			border-radius: 4px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			z-index: 9999;
			font-size: 14px;
			max-width: 300px;
		`;
    notice.textContent = message;
    document.body.appendChild(notice);
    // Remove notice after 3 seconds
    setTimeout(() => {
      if (notice.parentNode) {
        notice.parentNode.removeChild(notice);
      }
    }, 3000);
  }

  /**
   * Protect billing fields from unauthorized modifications
   */
  function protectBillingFields() {
    const protectedFields = [
      "billing_email",
      "billing_email_confirm",
      "billing_first_name",
      "billing_last_name",
      "billing_phone",
      "billing_address_1",
      "billing_city",
      "billing_state",
      "billing_postcode",
      "billing_country",
    ];
    // Observe for unauthorized field changes
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.type === "childList") {
          const billingWrapper = document.querySelector(
            ".woocommerce-billing-fields__field-wrapper",
          );
          if (billingWrapper && mutation.target === billingWrapper) {
            validateFieldStructure();
          }
        }
      });
    });
    const billingWrapper = document.querySelector(
      ".woocommerce-billing-fields__field-wrapper",
    );
    if (billingWrapper) {
      observer.observe(billingWrapper, {
        childList: true,
        subtree: true,
      });
    }
    // Validate and restore field structure if modified
    function validateFieldStructure() {
      const billingWrapper = document.querySelector(
        ".woocommerce-billing-fields__field-wrapper",
      );
      if (!billingWrapper) return;
      const allFields = billingWrapper.querySelectorAll(".form-row");
      allFields.forEach(function (field) {
        const input = field.querySelector("input, select");
        if (input && input.name) {
          if (!protectedFields.includes(input.name)) {
            console.warn(
              "YourPropFirm: Removing unauthorized field:",
              input.name,
            );
            field.remove();
          }
        }
      });
      protectedFields.forEach(function (fieldName) {
        const field = billingWrapper.querySelector(`[name="${fieldName}"]`);
        if (!field) {
          console.warn("YourPropFirm: Required field missing:", fieldName);
          // Could add logic to recreate missing fields here
        }
      });
    }
    // Initial validation
    setTimeout(validateFieldStructure, 500);
    console.log("YourPropFirm: Billing fields protection activated");
  }

  /**
   * Prevent external scripts from modifying checkout fields
   */
  function preventFieldManipulation() {
    // Override common DOM manipulation functions for billing fields
    const originalAppend = Element.prototype.appendChild;
    const originalRemove = Element.prototype.removeChild;
    const originalInsertBefore = Element.prototype.insertBefore;
    Element.prototype.appendChild = function (newNode) {
      if (
        this.closest &&
        this.closest(".woocommerce-billing-fields__field-wrapper")
      ) {
        const stack = new Error().stack;
        if (!stack.includes("yourpropfirm") && !stack.includes("woocommerce")) {
          console.warn("YourPropFirm: Blocked unauthorized field addition");
          return newNode;
        }
      }
      return originalAppend.call(this, newNode);
    };
    Element.prototype.removeChild = function (oldNode) {
      if (
        this.closest &&
        this.closest(".woocommerce-billing-fields__field-wrapper")
      ) {
        if (
          oldNode.querySelector &&
          oldNode.querySelector('[data-yourpropfirm-protected="true"]')
        ) {
          console.warn("YourPropFirm: Blocked unauthorized field removal");
          return oldNode;
        }
      }
      return originalRemove.call(this, oldNode);
    };
    Element.prototype.insertBefore = function (newNode, referenceNode) {
      if (
        this.closest &&
        this.closest(".woocommerce-billing-fields__field-wrapper")
      ) {
        const stack = new Error().stack;
        if (!stack.includes("yourpropfirm") && !stack.includes("woocommerce")) {
          console.warn("YourPropFirm: Blocked unauthorized field insertion");
          return newNode;
        }
      }
      return originalInsertBefore.call(this, newNode, referenceNode);
    };
  }

  /**
   * Initialize email confirmation field protection
   */
  function initEmailConfirmationProtection() {
    setTimeout(function () {
      const emailConfirmField = document.querySelector(
        'input[name="billing_email_confirm"]',
      );
      if (emailConfirmField) {
        // Prevent paste, drop, and context menu for security
        emailConfirmField.addEventListener("paste", function (e) {
          e.preventDefault();
          showNoticeMessage(
            i18n.pasteNotAllowed || "Paste is not allowed in Email Confirmation field for security.",
          );
          return false;
        });
        emailConfirmField.addEventListener("drop", function (e) {
          e.preventDefault();
          return false;
        });
        emailConfirmField.addEventListener("contextmenu", function (e) {
          e.preventDefault();
          return false;
        });
        emailConfirmField.addEventListener("dragstart", function (e) {
          e.preventDefault();
          return false;
        });
        // Real-time validation
        emailConfirmField.addEventListener("input", function () {
          validateEmailMatch();
        });
        // Also add validation to main email field
        const emailField = document.querySelector(
          'input[name="billing_email"]',
        );
        if (emailField) {
          emailField.addEventListener("input", function () {
            validateEmailMatch();
          });
        }
        console.log("YourPropFirm: Email confirmation protection initialized");
      }
    }, 1000);
  }

  /**
   * Validate that email and confirmation fields match
   */
  function validateEmailMatch() {
    const emailField = document.querySelector('input[name="billing_email"]');
    const emailConfirmField = document.querySelector(
      'input[name="billing_email_confirm"]',
    );
    if (emailField && emailConfirmField && emailConfirmField.value !== "") {
      if (emailField.value !== emailConfirmField.value) {
        emailConfirmField.setCustomValidity(i18n.emailMismatch || "Email does not match");
        emailConfirmField.style.borderColor = "#dc3545";
      } else {
        emailConfirmField.setCustomValidity("");
        emailConfirmField.style.borderColor = "#28a745";
      }
    }
  }

  // Bundle packages state and helpers
  var activeBundleId = "";

  function getBundleData(bundleId) {
    var store = window.ypfCheckoutStore;
    if (!store) return null;
    var bundles =
      (store.bundles) ||
      (store.addons_data && store.addons_data.bundles) ||
      [];
    for (var i = 0; i < bundles.length; i++) {
      if (bundles[i].id === bundleId) return bundles[i];
    }
    return null;
  }

  function applyBundleActive(bundleId) {
    console.log("[YPF Bundle] applyBundleActive called:", bundleId);

    var $bundleInput = $('.bundle-input[value="' + bundleId + '"]');
    var addonIds = [];
    try {
      addonIds = JSON.parse($bundleInput.attr("data-addon-ids") || "[]");
    } catch (e) {}

    // Show the container — filter its contents instead of hiding it
    $(".container-available-addons").show();

    // Show only addon-options that belong to the bundle (checked + disabled + locked)
    // Hide all others
    $(".container-available-addons .addon-option").each(function () {
      var $input = $(this).find(".addon-input").first();
      var addonId = $input.val();
      if (addonIds.indexOf(addonId) !== -1) {
        $(this).show().addClass("bundle-forced");
        $input.prop("checked", true).prop("disabled", true);
      } else {
        $(this).hide();
        $input.prop("checked", false).prop("disabled", true);
      }
    });

    // Mark bundle card active
    $(".bundle-option").removeClass("is-active");
    $bundleInput.closest(".bundle-option").addClass("is-active");

    // Update fallback hidden field
    $("#bundle_id_fallback").val(bundleId);

    // Inject hidden addon[] inputs — disabled inputs don't submit, hidden ones do
    $("input.bundle-forced-addon-js").remove();
    addonIds.forEach(function (addonId) {
      var index = addonId.split("-")[0];
      $('<input type="hidden" class="bundle-forced-addon-js">')
        .attr("name", "addon[" + index + "]")
        .val(addonId)
        .appendTo("form.woocommerce-checkout");
    });

    console.log("[YPF Bundle] active, addon IDs:", addonIds);
  }

  function applyBundleInactive() {
    console.log("[YPF Bundle] applyBundleInactive called");
    // Restore all addon-options to default state
    $(".container-available-addons").show();
    $(".container-available-addons .addon-option")
      .show()
      .removeClass("bundle-forced");
    $(".container-available-addons .addon-input")
      .prop("checked", false)
      .prop("disabled", false);
    $(".bundle-option").removeClass("is-active");
    $("input.bundle-forced-addon-js").remove();
    $("#bundle_id_fallback").val("");
  }

  function syncBundleState() {
    var $checked = $("input.bundle-input:checked");
    console.log("[YPF Bundle] syncBundleState — checked found:", $checked.length, "val:", $checked.val());
    if ($checked.length && $checked.val()) {
      activeBundleId = $checked.val();
      applyBundleActive(activeBundleId);
    } else {
      activeBundleId = "";
      applyBundleInactive();
    }
  }

  // Single entry point for all initialization and event bindings
  $(document).ready(function () {
    // Fill billing fields if profile data is available
    if (
      typeof yourpropfirm_reset !== "undefined" &&
      yourpropfirm_reset.profile
    ) {
      fillBillingFields(yourpropfirm_reset.email, yourpropfirm_reset.profile);
    } else if (
      typeof yourpropfirm_purchase !== "undefined" &&
      yourpropfirm_purchase.profile
    ) {
      fillBillingFields(
        yourpropfirm_purchase.email,
        yourpropfirm_purchase.profile,
      );
    }

    // Initialize protections and event handlers
    initEmailConfirmationProtection();
    protectBillingFields();
    preventFieldManipulation();

    // Auto-apply coupon from cookie if exists
    applyCouponFromCookie();

    // Coupon button click handler
    $(".coupon-apply-btn").on("click", applyCoupon);
    // Re-initialize protections after WooCommerce AJAX checkout update
    $(document.body).on("updated_checkout", function () {
      syncBundleState();

      setTimeout(function () {
        initEmailConfirmationProtection();
        protectBillingFields();
      }, 100);

      // Move WC notices from outside the form into the form for consistency
      var $form = $("form.checkout");
      if ($form.length) {
        $form
          .parent()
          .children(
            ".woocommerce-message, .woocommerce-error, .woocommerce-info",
          )
          .each(function () {
            var $notice = $(this);
            var noticeText = $notice.text().trim();
            var isError = $notice.hasClass("woocommerce-error");
            var noticeClass = isError
              ? "woocommerce-error"
              : "woocommerce-message";

            $notice.remove();

            // Wrap in NoticeGroup and prepend inside form
            var $wrapped = $(
              '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout yourpropfirm-coupon-notice"></div>',
            );
            var $alert = $('<div role="alert"></div>');
            var $list = $("<ul></ul>", { class: noticeClass, tabindex: "-1" });
            var $item = $("<li></li>").text(noticeText);

            $list.append($item);
            $alert.append($list);
            $wrapped.append($alert);
            $form.prepend($wrapped);
            $wrapped[0].scrollIntoView({ behavior: "smooth", block: "center" });

            setTimeout(function () {
              $wrapped.fadeOut(300, function () {
                $(this).remove();
              });
            }, 5000);
          });
      }
    });

    $(document.body).on("click", ".addon-option", function (e) {
      // Prevent recursion if the original click was on the input itself
      if ($(e.target).is(".addon-input")) {
        return;
      }

      const $input = $(this).find(".addon-input").first();
      if (!$input.length || $input.prop("disabled")) return;

      // Toggle checkbox or select radio without triggering click bubbling
      const type = ($input.attr("type") || "").toLowerCase();
      if (type === "checkbox") {
        $input.prop("checked", !$input.prop("checked"));
      } else {
        $input.prop("checked", true);
      }

      // Trigger change to refresh checkout, avoids click recursion
      $input.trigger("change");
    });

    // Capture bundle radio state BEFORE native label→radio behavior fires on click
    $(document.body).on("mousedown", ".bundle-option", function () {
      var $radio = $(this).find(".bundle-input").first();
      $(this).data("bundleWasChecked", $radio.prop("checked"));
    });

    // Bundle option card click — only handles deselect (select is handled by change)
    // Native label click fires change BEFORE click bubbles here, so we use the
    // pre-click state captured in mousedown to detect "already-checked" clicks.
    $(document.body).on("click", ".bundle-option", function () {
      var wasChecked = $(this).data("bundleWasChecked");
      $(this).removeData("bundleWasChecked");

      if (wasChecked) {
        var $radio = $(this).find(".bundle-input").first();
        $radio.prop("checked", false);
        activeBundleId = "";
        applyBundleInactive();
        $(document.body).trigger("update_checkout");
      }
      // Not wasChecked: native label→radio→change already called applyBundleActive
    });

    // Bundle radio change — handles select (and keyboard navigation)
    $(document.body).on("change", ".bundle-input", function () {
      console.log("[YPF Bundle] change event — checked:", $(this).prop("checked"), "val:", $(this).val());
      if ($(this).prop("checked") && $(this).val()) {
        activeBundleId = $(this).val();
        applyBundleActive(activeBundleId);
      } else {
        activeBundleId = "";
        applyBundleInactive();
      }
      $(document.body).trigger("update_checkout");
    });

    // Sync bundle state on init
    syncBundleState();

    // Icon button mode: sync hidden select with radio input
    if ($("body").hasClass("ypf-payment-icons")) {
      $(document.body).on("change", "input[name='payment_method']", function () {
        $("#payment_method_select").val($(this).val());
      });
    }
  });
})(jQuery);

// Add protection for email confirmation field - prevent paste
(function () {
  "use strict";

  var i18n = window.ypfCheckoutWizard || {};

  function applyCoupon() {
    // apply coupon with jQuery ajax
    var coupon_code = $("#coupon_code").val();

    if (coupon_code) {
      jQuery.ajax({
        type: "POST",
        url: yourpropfirm_purchase.ajax_url,
        data: {
          action: "apply_coupon_action",
          coupon_code: coupon_code,
          billing_email:
            jQuery('input[name="billing_email"]').val() || "",
        },
        beforeSend: function () {
          // Show loading state
          $(".coupon-apply-btn").text(i18n.applyingLabel || "Applying...");
        },
        success: function (response) {
          // Clear input and show success/error message
          $("#coupon_code").val("");
          jQuery(document.body).trigger("update_checkout");
          var msg =
            (response.data && response.data.message) ||
            (i18n.couponAppliedMsg || "Coupon applied successfully!");
          showCouponMessage(msg, response.success);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          // Show error message
          showCouponMessage((i18n.couponErrorPrefix || "Error applying coupon: ") + errorThrown, false);
        },
        complete: function () {
          // Reset button text
          $(".coupon-apply-btn").text(i18n.applyLabel || "Apply");
        },
      });
    }
  }

  /**
   * Show coupon message in WooCommerce NoticeGroup
   * @param {string} message - Message to display
   * @param {boolean} isSuccess - Whether this is a success message
   */
  function showCouponMessage(message, isSuccess) {
    var noticeClass = isSuccess ? "woocommerce-message" : "woocommerce-error";
    var noticeHtml =
      '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout yourpropfirm-coupon-notice">' +
      '<div role="alert"><ul class="' +
      noticeClass +
      '" tabindex="-1">' +
      "<li>" +
      message +
      "</li>" +
      "</ul></div></div>";

    // Remove existing coupon notices
    jQuery(".yourpropfirm-coupon-notice").remove();

    // Insert at top of checkout form
    var $form = jQuery("form.checkout");
    if ($form.length) {
      $form.prepend(noticeHtml);
      jQuery(".yourpropfirm-coupon-notice")[0].scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    }

    // Auto-remove after 5 seconds
    setTimeout(function () {
      jQuery(".yourpropfirm-coupon-notice").fadeOut(2000, function () {
        jQuery(this).remove();
      });
    }, 5000);
  }

  /**
   * Initialize email confirmation field protection
   * @since 1.0.0
   */
  function initEmailConfirmationProtection() {
    // Wait for fields to be loaded
    setTimeout(function () {
      const emailConfirmField = document.querySelector(
        'input[name="billing_email_confirm"]',
      );

      if (emailConfirmField) {
        // Prevent paste, drop, and context menu
        emailConfirmField.addEventListener("paste", function (e) {
          e.preventDefault();
          showNoticeMessage(
            i18n.pasteNotAllowed || "Paste is not allowed in Email Confirmation field for security.",
          );
          return false;
        });

        emailConfirmField.addEventListener("drop", function (e) {
          e.preventDefault();
          return false;
        });

        emailConfirmField.addEventListener("contextmenu", function (e) {
          e.preventDefault();
          return false;
        });

        // Prevent right-click drag
        emailConfirmField.addEventListener("dragstart", function (e) {
          e.preventDefault();
          return false;
        });

        // Real-time validation
        emailConfirmField.addEventListener("input", function () {
          validateEmailMatch();
        });

        // Also add validation to main email field
        const emailField = document.querySelector(
          'input[name="billing_email"]',
        );
        if (emailField) {
          emailField.addEventListener("input", function () {
            validateEmailMatch();
          });
        }

        console.log("YourPropFirm: Email confirmation protection initialized");
      }
    }, 1000);
  }

  /**
   * Validate email fields match in real-time
   * @since 1.0.0
   */
  function validateEmailMatch() {
    const emailField = document.querySelector('input[name="billing_email"]');
    const emailConfirmField = document.querySelector(
      'input[name="billing_email_confirm"]',
    );

    if (emailField && emailConfirmField && emailConfirmField.value !== "") {
      if (emailField.value !== emailConfirmField.value) {
        emailConfirmField.setCustomValidity(i18n.emailMismatch || "Email does not match");
        emailConfirmField.style.borderColor = "#dc3545";
      } else {
        emailConfirmField.setCustomValidity("");
        emailConfirmField.style.borderColor = "#28a745";
      }
    }
  }

  /**
   * Show notice message to user
   * @since 1.0.0
   * @param {string} message Message to display
   */
  function showNoticeMessage(message) {
    // Remove existing notices
    const existingNotices = document.querySelectorAll(".yourpropfirm-notice");
    existingNotices.forEach((notice) => notice.remove());

    // Create notice element
    const notice = document.createElement("div");
    notice.className = "yourpropfirm-notice";
    notice.style.cssText = `
			position: fixed;
			top: 20px;
			right: 20px;
			background: #f8d7da;
			color: #721c24;
			padding: 12px 20px;
			border: 1px solid #f5c6cb;
			border-radius: 4px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			z-index: 9999;
			font-size: 14px;
			max-width: 300px;
		`;
    notice.textContent = message;

    document.body.appendChild(notice);

    // Remove notice after 3 seconds
    setTimeout(() => {
      if (notice.parentNode) {
        notice.parentNode.removeChild(notice);
      }
    }, 3000);
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener(
      "DOMContentLoaded",
      initEmailConfirmationProtection,
    );
  } else {
    initEmailConfirmationProtection();
  }

  // Re-initialize after AJAX updates
  jQuery(document.body).on("updated_checkout", function () {
    initEmailConfirmationProtection();
  });

  // Apply coupon on button click
  jQuery(".coupon-apply-btn").on("click", applyCoupon);
})();

// Billing fields protection against external modifications
(function () {
  "use strict";

  /**
   * Protect billing fields from external modifications
   * @since 1.0.0
   */
  function protectBillingFields() {
    // Define protected field structure
    const protectedFields = [
      "billing_email",
      "billing_email_confirm",
      "billing_first_name",
      "billing_last_name",
      "billing_phone",
      "billing_address_1",
      "billing_city",
      "billing_state",
      "billing_postcode",
      "billing_country",
    ];

    // Monitor for unauthorized field additions/removals
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        if (mutation.type === "childList") {
          const billingWrapper = document.querySelector(
            ".woocommerce-billing-fields__field-wrapper",
          );
          if (billingWrapper && mutation.target === billingWrapper) {
            validateFieldStructure();
          }
        }
      });
    });

    // Start observing
    const billingWrapper = document.querySelector(
      ".woocommerce-billing-fields__field-wrapper",
    );
    if (billingWrapper) {
      observer.observe(billingWrapper, {
        childList: true,
        subtree: true,
      });
    }

    /**
     * Validate and restore field structure if modified
     * @since 1.0.0
     */
    function validateFieldStructure() {
      const billingWrapper = document.querySelector(
        ".woocommerce-billing-fields__field-wrapper",
      );
      if (!billingWrapper) return;

      // Check for unauthorized fields and remove them
      const allFields = billingWrapper.querySelectorAll(".form-row");
      allFields.forEach(function (field) {
        const input = field.querySelector("input, select");
        if (input && input.name) {
          if (!protectedFields.includes(input.name)) {
            console.warn(
              "YourPropFirm: Removing unauthorized field:",
              input.name,
            );
            field.remove();
          }
        }
      });

      // Ensure all required fields are present
      protectedFields.forEach(function (fieldName) {
        const field = billingWrapper.querySelector(`[name="${fieldName}"]`);
        if (!field) {
          console.warn("YourPropFirm: Required field missing:", fieldName);
          // Could add logic to recreate missing fields here
        }
      });
    }

    // Initial validation
    setTimeout(validateFieldStructure, 500);

    console.log("YourPropFirm: Billing fields protection activated");
  }

  /**
   * Prevent external scripts from modifying checkout fields
   * @since 1.0.0
   */
  function preventFieldManipulation() {
    // Override common field manipulation functions
    const originalAppend = Element.prototype.appendChild;
    const originalRemove = Element.prototype.removeChild;
    const originalInsertBefore = Element.prototype.insertBefore;

    Element.prototype.appendChild = function (newNode) {
      // Allow our own modifications but block others for billing fields
      if (
        this.closest &&
        this.closest(".woocommerce-billing-fields__field-wrapper")
      ) {
        const stack = new Error().stack;
        if (!stack.includes("yourpropfirm") && !stack.includes("woocommerce")) {
          console.warn("YourPropFirm: Blocked unauthorized field addition");
          return newNode;
        }
      }
      return originalAppend.call(this, newNode);
    };

    Element.prototype.removeChild = function (oldNode) {
      // Protect billing fields from removal
      if (
        this.closest &&
        this.closest(".woocommerce-billing-fields__field-wrapper")
      ) {
        if (
          oldNode.querySelector &&
          oldNode.querySelector('[data-yourpropfirm-protected="true"]')
        ) {
          console.warn("YourPropFirm: Blocked unauthorized field removal");
          return oldNode;
        }
      }
      return originalRemove.call(this, oldNode);
    };

    Element.prototype.insertBefore = function (newNode, referenceNode) {
      // Control field insertion in billing area
      if (
        this.closest &&
        this.closest(".woocommerce-billing-fields__field-wrapper")
      ) {
        const stack = new Error().stack;
        if (!stack.includes("yourpropfirm") && !stack.includes("woocommerce")) {
          console.warn("YourPropFirm: Blocked unauthorized field insertion");
          return newNode;
        }
      }
      return originalInsertBefore.call(this, newNode, referenceNode);
    };
  }

  // Initialize protection when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      protectBillingFields();
      preventFieldManipulation();
    });
  } else {
    protectBillingFields();
    preventFieldManipulation();
  }

  // Re-initialize after AJAX updates
  jQuery(document.body).on("updated_checkout", function () {
    setTimeout(function () {
      protectBillingFields();
    }, 100);
  });
})();
