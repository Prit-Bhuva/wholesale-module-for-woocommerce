# Wholesale Module for WooCommerce

> **_NOTE:_** Make sure you have <mark>Woocommerce</mark> Plugin installed in your wordpress site.

## Description

The Wholesale Module for WooCommerce adds wholesale pricing functionality to your WooCommerce store. This plugin allows you to set wholesale prices for products and enforce minimum quantity requirements for wholesale purchases.

## Features

- Add wholesale pricing to products.
- Configure minimum quantity requirements for wholesale orders.
- Enforce wholesale pricing based on cart total quantity.
- Display adjusted pricing on the product page based on quantity.

## Installation

1. **Upload the Plugin**:

   - Upload the `wholesale-module-for-woocommerce` directory to the `/wp-content/plugins/` directory.

2. **Activate the Plugin**:
   - Go to the WordPress admin area.
   - Navigate to `Plugins` and activate the `Wholesale Module for WooCommerce`.

## Configuration

1. **Set Minimum Wholesale Quantity**:

   - Go to `WooCommerce` > `Settings`.
   - Click on the `Wholesale` tab.
   - Set the minimum quantity required for wholesale pricing.
   - Select the User role which you want to required for wholesale pricing.

2. **Add Wholesale Prices to Products**:
   - Edit a product in WooCommerce.
   - In the `Product Data` section, find the `Wholesale Price` field.
   - Enter the wholesale price for the product.

## Usage

1. **For Wholesale Users**:

   - Log in with a user account that you select the role inside the `WooCommerce` > `Settings` > `Wholesale` tab.
   - Add products to your cart.
   - Prices will be adjusted based on the quantity of items in the cart and the wholesale settings.

2. **For Admins**:
   - Monitor wholesale settings and prices through the WooCommerce settings and product data sections.

## Hooks and Filters

- **`wwd_wholesale_settings`**: Filter to modify the wholesale settings fields.
- **`woocommerce_settings_tabs_array`**: Add a wholesale tab to WooCommerce settings.
- **`woocommerce_settings_wholesale`**: Display wholesale settings in the WooCommerce settings.

## Developer Notes

- Inside the `WooCommerce` > `Settings` > `Wholesale` tab `Wholesale User Role` field it display all the roles which is inside the wordpress.
- The plugin includes JavaScript to dynamically update product prices based on quantity.

## Support

## License
