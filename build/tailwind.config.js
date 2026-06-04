/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "../templates/**/*.php",
    "../js/**/*.js",
    "../src/css/**/*.css",
  ],
  darkMode: ["class", '[class="dark"]'],
  theme: {
    extend: {
      colors: {
        black: "#0D0D0D",
        yourpropfirm: {
          primary: "var(--yourpropfirm-primary)",
          "primary-hover": "var(--yourpropfirm-primary-hover)",
          "primary-light": "var(--yourpropfirm-primary-light)",
          "primary-dark": "var(--yourpropfirm-primary-dark)",
          "primary-50": "var(--yourpropfirm-primary-50)",
          "primary-300": "var(--yourpropfirm-primary-300)",
          fieldborder: "var(--border-standby)",
          secondary: "var(--yourpropfirm-secondary)",
          background: "var(--yourpropfirm-background)",
          "primary-text": "var(--yourpropfirm-primary-text)",
          "secondary-text": "var(--yourpropfirm-secondary-text)",
          "button-text": "var(--yourpropfirm-button-text)",
          card: "var(--yourpropfirm-card)",
          "progressbar-from": "var(--yourpropfirm-progressbar-from)",
          "progressbar-to": "var(--yourpropfirm-progressbar-to)",
          accent: "var(--yourpropfirm-accent)",
          border: "var(--yourpropfirm-border)",
          text: "var(--yourpropfirm-text)",
          success: "var(--yourpropfirm-success)",
          warning: "var(--yourpropfirm-warning)",
          error: "var(--yourpropfirm-error)",
        },
        message: {
          success: {
            color: "var(--success-color)",
            background: "var(--success-background)",
          },
          error: {
            color: "var(--danger-color)",
            background: "var(--danger-background)",
          },
        },
        gray: {
          50: "#FCFCFC",
          100: "#F7F7F7",
          200: "#E5E5E5",
          300: "#d1d5db",
          400: "#9ca3af",
          500: "#B3B3B3",
          600: "#666666",
          700: "#374151",
          800: "#1f2937",
          900: "#111827",
          950: "#030712",
        },
      },
      fontFamily: {
        sans: ["Archivo", "-apple-system", "BlinkMacSystemFont", "Segoe UI", "Roboto", "Helvetica Neue", "Arial", "sans-serif"],
      },
      borderRadius: { checkout: "8px" },
      spacing: { checkout: "1.5rem" },
      animation: {
        "fade-in": "fadeIn 0.3s ease-in-out",
        "slide-up": "slideUp 0.3s ease-out",
      },
      keyframes: {
        fadeIn: { "0%": { opacity: "0" }, "100%": { opacity: "1" } },
        slideUp: { "0%": { transform: "translateY(10px)", opacity: "0" }, "100%": { transform: "translateY(0)", opacity: "1" } },
      },
    },
  },
  plugins: [require("@tailwindcss/forms"), require("@tailwindcss/typography")],
  prefix: "tw-",
  important: true,
};
