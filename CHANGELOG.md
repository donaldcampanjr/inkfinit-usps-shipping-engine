<!--
- **Project**: Inkfinit Shipping Core Design
- **Author**: Donald Campan Jr.
- **Company**: Inkfinit LLC
- **Band**: Waking The Cadaver
-->

# Changelog

<!-- markdownlint-disable MD024 -->

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Core Benefits

- **Reduce Costs**: Automatically finds the most cost-effective USPS rates and optimal packaging.
- **Save Time**: Eliminates hours of manual data entry with shipping presets and bulk management tools.
- **Increase Accuracy**: Prevents costly shipping errors and delays with official USPS Address Validation.
- **Boost Conversions**: Builds customer confidence by providing accurate delivery estimates at checkout.
- **Streamline Operations**: Manage the entire shipping process, from rates to labels, directly within the WordPress dashboard.

## Project Scope

### In Scope

- Providing real-time domestic and international shipping rates exclusively for the United States Postal Service (USPS).
- Deep and seamless integration with the WooCommerce ecosystem, including products, orders, and shipping zones.
- Full shipping lifecycle support within WordPress: address validation, label printing, and packing slips.
- Advanced, intelligent features such as a proprietary box packing algorithm, flat-rate box support, and a conditional rule engine.

### Out of Scope

- Support for any other shipping carriers (e.g., FedEx, UPS, DHL). This is a USPS-only solution.
- Functionality as a standalone application outside of a WordPress and WooCommerce environment.
- Comprehensive inventory or stock management features beyond product dimensions and weight for shipping calculations.

## [Unreleased]

## [1.2.0]

### Added

- **Address Validation**: Integration with USPS Address Validation API to ensure shipping accuracy.
- **Label Printing**: Added functionality for printing shipping labels directly via the USPS API.
- **Delivery Estimates**: Display estimated delivery dates to customers during checkout.
- **Packing Slips**: Generate and print packing slips for orders.
- **Flat Rate Boxes**: Support for USPS Flat Rate boxes and pricing.
- **Bulk Variation Manager**: A tool to efficiently manage shipping properties for multiple product variations.
- **Product Dimension Recommender**: An intelligent tool to suggest dimensions for products based on historical data.

### Changed

-

### Fixed

-

## [1.1.0] - 2025-12-02

### Added

- **Commercial Licensing**: Introduced a comprehensive commercial licensing structure with four tiers (Free, Pro, Premium, Enterprise) detailed in `LICENSE-COMMERCIAL.md`.
- **Developer Attribution**: Added "Inkfinit LLC" as the developer in `plugin.php`, `README.md`, and all relevant documentation to ensure proper credit.
- **Product Preset Picker**: Implemented a new product preset picker on the product edit screen, featuring AJAX-powered auto-fill and auto-save for weight and dimensions.
- **WooCommerce Integration**: Integrated shipping presets directly into the WooCommerce > Settings > Shipping > Shipping Classes screen for improved administrator visibility.

### Changed

- **BREAKING CHANGE**: The plugin license was changed from GPLv3 to a proprietary commercial license to support a freemium business model.
- **Plugin Positioning**: Updated `plugin.php` header and `README.md` to reflect the new commercial positioning and freemium model.
- **Admin Footer**: Refactored the admin footer to render only on the main plugin dashboard, reducing clutter on other admin pages.

### Fixed

- **UI Layout**: Corrected multiple CSS issues, including text alignment and padding, to ensure the admin interface has a professional, native WordPress look and feel.
- **Preset Application**: Resolved a critical bug where selecting a shipping preset did not automatically populate and save the corresponding data to the product.

### Security

- Implemented security hardening measures, including nonce verification on AJAX endpoints, output escaping, and input sanitization to protect against common vulnerabilities.

## [1.0.0] - 2025-10-01

### Added

- **Initial Release**: First public version of the Inkfinit Shipping Engine.
- **Real-Time Rates**: Core functionality for fetching real-time USPS shipping rates via their modern OAuth 2.0 API.
- **Box Packing**: Advanced box packing algorithm to determine the most efficient packaging for a given set of products.
- **Rule Engine**: A flexible rule engine for conditional shipping logic.
- **WooCommerce Integration**: Seamless integration with WooCommerce checkout and shipping zones.
