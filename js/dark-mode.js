/**
 * Dark Mode Toggle Functionality
 * Handles theme switching between light and dark modes
 */

(function () {
  "use strict";

  // Cookie helper function
  const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(";").shift();
    return null;
  };

  // Theme management functions
  const getStoredTheme = () => localStorage.getItem("theme");
  const setStoredTheme = (theme) => localStorage.setItem("theme", theme);
  const getPreferredTheme = () => {
    // Priority 1: Check cookie from server-side theme parameter
    const cookieTheme = getCookie("yourpropfirm_theme_mode");
    if (cookieTheme && (cookieTheme === "dark" || cookieTheme === "light")) {
      return cookieTheme;
    }

    // Priority 2: Check localStorage
    const storedTheme = getStoredTheme();
    if (storedTheme) {
      return storedTheme;
    }

    // Priority 3: FundedBot is a dark-first experience — default to dark
    // unless the user has explicitly opted into light via toggle/cookie above.
    return "dark";
  };

  const setTheme = (theme) => {
    if (theme === "dark") {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
    setStoredTheme(theme);
    updateToggleButton(theme);
  };

  const updateToggleButton = (theme) => {
    const sunIcon = document.getElementById("sun-icon");
    const moonIcon = document.getElementById("moon-icon");

    if (theme === "dark") {
      sunIcon?.classList.remove("tw-hidden");
      sunIcon?.classList.add("tw-block");
      moonIcon?.classList.remove("tw-block");
      moonIcon?.classList.add("tw-hidden");
    } else {
      sunIcon?.classList.remove("tw-block");
      sunIcon?.classList.add("tw-hidden");
      moonIcon?.classList.remove("tw-hidden");
      moonIcon?.classList.add("tw-block");
    }
  };

  const toggleTheme = () => {
    const currentTheme = getStoredTheme() || getPreferredTheme();
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    setTheme(newTheme);
  };

  // Initialize theme on page load
  const initializeTheme = () => {
    const theme = getPreferredTheme();
    setTheme(theme);
  };

  // Setup event listeners
  const setupEventListeners = () => {
    const toggleButton = document.getElementById("theme-toggle");
    if (toggleButton) {
      toggleButton.addEventListener("click", toggleTheme);
    }

    // Listen for system theme changes
    window
      .matchMedia("(prefers-color-scheme: dark)")
      .addEventListener("change", (e) => {
        if (!getStoredTheme()) {
          setTheme(e.matches ? "dark" : "light");
        }
      });
  };

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      initializeTheme();
      setupEventListeners();
    });
  } else {
    initializeTheme();
    setupEventListeners();
  }

  // Expose theme functions globally for checkout integration
  window.YourPropFirmTheme = {
    getTheme: () => getStoredTheme() || getPreferredTheme(),
    setTheme: setTheme,
    toggleTheme: toggleTheme,
  };
})();
