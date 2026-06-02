# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2026-06-02

### Changed
- **Architecture**: Transformed from a sync-based source repository into a fully installable WordPress add-on plugin (`yourpropfirm-ui-addon`). The repository can now be cloned directly into `wp-content/plugins/` and activated — no manual file sync required.

### Added
- `yourpropfirm-ui-addon.php` — WordPress plugin entry point with plugin header, constants, and dependency check for the main `yourpropfirm` plugin.
- `includes/class-ypf-ui-addon-hooks.php` — Override logic: registers `woocommerce_locate_template` at priority 10000 (beats main plugin's 9999) and `wp_enqueue_scripts` at priority 1000 (replaces main plugin's checkout CSS at 999). Immune to main plugin's `disable_other_checkout_overrides()` cleanup.
- Admin notice when the main YourPropFirm Plugin is not active.

### Removed
- `scripts/sync-to-plugin.*` — no longer needed; install the repo as a plugin instead.

## [1.0.0] - 2026-06-02

### Added

- Initial extraction from yourpropfirm-plugin v1.14.0
- Tailwind CSS build system with `tw-` prefix and `!important` utilities
- `checkout.js` frontend interaction script
- `dark-mode.js` theme toggle with `localStorage.theme` and `prefers-color-scheme` fallback
- 22 WooCommerce checkout PHP templates (overrides under `woocommerce/checkout/`)
- 2 plugin-specific partials (under `public/partials/checkout/`)
- Sync scripts for copying build artifacts back to the plugin (PowerShell and Bash)
- Documentation files

[Unreleased]: https://github.com/yourpropfirm/checkout-frontend/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/yourpropfirm/checkout-frontend/releases/tag/v1.0.0
