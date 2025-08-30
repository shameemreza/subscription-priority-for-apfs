# Subscription Priority for All Products for WooCommerce Subscriptions

A mini addon for **All Products for WooCommerce Subscriptions** that makes subscription plans the default selection and visually prioritizes them over one-time purchases.

## Purpose

This plugin solves a common request from WooCommerce store owners who want to:

- Guide customers toward subscription purchases.
- Make subscription options more prominent.
- Simplify the subscription selection process.
- Increase recurring revenue.

## Features

- **Automatic Default Selection** - Subscription plans are pre-selected on product pages.
- **Direct Subscribe from Shop** - Adds subscription version directly to cart.
- **Visual Hierarchy** - Bold and highlighted subscription options.
- **Custom Button Text** - "Sign up" instead of "Add to Cart".
- **AJAX Support** - Full compatibility with AJAX add-to-cart.
- **HPOS Compatible** - Works with High-Performance Order Storage.
- **Developer Friendly** - Extensible with hooks and filters.

## Requirements

- WordPress 6.0 or higher.
- PHP 7.4 or higher.
- WooCommerce 7.0 or higher.
- WooCommerce Subscriptions.
- All Products for WooCommerce Subscriptions.

## Installation

1. Download the latest release from [GitHub Releases](https://github.com/shameemreza/subscription-priority-for-apfs/releases)
2. Upload to `/wp-content/plugins/subscription-priority-for-apfs/`
3. Activate through the WordPress admin

## Configuration

No configuration needed! The plugin works immediately after activation.

## Customization

### Available Filters

```
// Control which products default to subscription
add_filter( 'spapfs_enable_default_subscription', function( $enable, $product ) {
    // Your logic here
    return $enable;
}, 10, 2 );

// Customize the Sign up button text
add_filter( 'spapfs_subscribe_button_text', function( $text, $product ) {
    return __( 'Start Subscription', 'your-textdomain' );
}, 10, 2 );

// Override styles
add_filter( 'spapfs_inline_styles', function( $css ) {
    // Add or modify CSS
    return $css;
} );
```

### Custom Styling

Override the default blue (#0073aa) highlighting:

```
/* Change subscription highlight color */
.wcsatt-options-product
  .subscription-option
  input[type="radio"]:checked
  + label {
  color: #your-color !important;
  background: linear-gradient(
    90deg,
    rgba(your-rgb, 0.05) 0%,
    rgba(your-rgb, 0.02) 100%
  );
}
```

## Changelog

### Version 1.0.2 (2024-11-28)

- Fixed: Created missing `languages` folder for translations
- Fixed: Removed deprecated `load_plugin_textdomain()` function (not needed since WordPress 4.6)
- Fixed: Corrected text domain throughout plugin to match plugin slug
- Improved: Code compliance with WordPress Plugin Check standards

### Version 1.0.1 (2024-11-28)

- Fixed: Shop page button now correctly shows "Sign up" instead of "Add to cart"
- Fixed: "Sign up" button on shop pages now adds subscription version (not one-time purchase)
- Improved: Better detection of AJAX add-to-cart requests from shop pages
- Changed: Button text from "Subscribe" to "Sign up" (matches WooCommerce Subscriptions default)

### Version 1.0.0 (2024-11-28)

- Initial release
- Automatic subscription selection
- Direct subscription add-to-cart
- Bold and highlighted subscription text
- Visual hierarchy implementation
- HPOS compatibility
- Developer hooks and filters

## 📄 License

This plugin is licensed under the [GPL v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

⭐ If you find this plugin helpful, please give it a star on GitHub!
