# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
