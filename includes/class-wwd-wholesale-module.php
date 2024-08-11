<?php

/**
 * Class WWD_Wholesale_Module
 * 
 * This class handles the wholesale pricing functionality for WooCommerce.
 */
if (!class_exists('WWD_Wholesale_Module')):
    class WWD_Wholesale_Module
    {
        // Define plugin version
        const VERSION = '1.0.0';

        // Define plugin directory
        private $plugin_dir;

        /**
         * Constructor to initialize hooks and actions.
         */
        public function __construct()
        {
            $this->plugin_dir = plugin_dir_path(__FILE__);
            $this->init_hooks();
        }

        /**
         * Initialize hooks and actions for the plugin.
         */
        private function init_hooks()
        {
            // Enqueue styles and scripts
            add_action('wp_enqueue_scripts', [$this, 'wwd_enqueue_scripts']);

            // Plugin action links (ensure the hook matches your plugin file)
            add_filter('plugin_action_links', [$this, 'wwd_add_plugin_link'], 10, 2);

            // Add a new tab to WooCommerce settings
            add_filter('woocommerce_settings_tabs_array', [$this, 'add_wholesale_settings_tab'], 50);

            // Display settings fields in the Wholesale settings tab
            add_action('woocommerce_settings_wholesale', [$this, 'wholesale_settings_tab']);

            // Save the settings for the Wholesale tab
            add_action('woocommerce_update_options_wholesale', [$this, 'save_wholesale_settings']);

            // Add a wholesale price field to the product pricing section
            add_action('woocommerce_product_options_pricing', [$this, 'add_wholesale_price_field']);

            // Save the wholesale price field data
            add_action('woocommerce_process_product_meta', [$this, 'save_wholesale_price_field']);

            // Check if the cart items meet the minimum quantity requirements for wholesalers
            add_action('woocommerce_check_cart_items', [$this, 'check_minimum_quantity_per_product']);

            // Adjust cart item prices based on total quantity and wholesale price
            add_action('woocommerce_before_calculate_totals', [$this, 'adjust_cart_item_prices_based_on_total_quantity']);

            // Change the product price text based on the total quantity on the product page
            add_action('wp_footer', [$this, 'change_product_price_text']);
        }

        /**
         * Enqueue styles and scripts for wholesale users.
         */
        public function wwd_enqueue_scripts()
        {
            if (!$this->is_selected_user_role()) {
                return;
            }

            if (!class_exists('WooCommerce')) return;

            if (function_exists('is_cart') && function_exists('is_product')) {
                if (is_cart() || is_product()) {
                    wp_enqueue_style('wwd-custom-style', plugins_url('assets/css/custom-style.css', __FILE__), [], time());
                }
            }
        }

        /**
         * Show action links on the plugin screen.
         *
         * @param mixed $links Plugin Action links.
         *
         * @return array
         */
        public function wwd_add_plugin_link($plugin_actions, $plugin_file)
        {
            // Define the main plugin file dynamically
            $main_plugin_file = plugin_basename(WHOLESALE_PLUGIN_FILE);

            // Check if the current plugin file is the one we're targeting
            if ($main_plugin_file === $plugin_file) {
                // Create a new array to hold the custom action links
                $new_actions = array();

                // Add the 'Settings' link first
                $new_actions['wwd_settings'] = sprintf(
                    __('<a href="%s">Settings</a>', 'wholesale'),
                    esc_url(admin_url('admin.php?page=wc-settings&tab=wholesale'))
                );

                // Add existing action links, preserving the order and adding 'Deactivate' last
                foreach ($plugin_actions as $key => $action) {
                    if ($key !== 'deactivate') {
                        $new_actions[$key] = $action;
                    }
                }

                // Add the 'Deactivate' link (or other standard links) at the end
                if (isset($plugin_actions['deactivate'])) {
                    $new_actions['deactivate'] = $plugin_actions['deactivate'];
                }

                // Return the reordered array
                return $new_actions;
            }

            return $plugin_actions;
        }




        /**
         * Add a new tab in WooCommerce settings for Wholesale settings.
         * 
         * @param array $settings_tabs Existing WooCommerce settings tabs.
         * @return array Modified settings tabs.
         */
        public function add_wholesale_settings_tab($settings_tabs)
        {
            $settings_tabs['wholesale'] = __('Wholesale', 'woocommerce');
            return $settings_tabs;
        }

        /**
         * Display settings fields in the Wholesale settings tab.
         */
        public function wholesale_settings_tab()
        {
            woocommerce_admin_fields($this->get_wholesale_settings());
        }

        /**
         * Define settings fields for the Wholesale settings tab.
         * 
         * @return array Settings fields array.
         */
        public function get_wholesale_settings()
        {
            $wholesale_min_quantity = get_option('wwd_wholesale_min_quantity', 3); // Default to 3 if not set
            $wholesale_user_role = get_option('wwd_wholesale_user_role', ''); // Default to empty if not set

            // Get all user roles
            global $wp_roles;
            $roles = $wp_roles->roles;
            $roles_options = [];
            $roles_options[''] = 'Select User Role';
            foreach ($roles as $role_key => $role) {
                $roles_options[$role_key] = $role['name'];
            }

            $settings = [
                'section_title' => [
                    'name'     => __('Wholesale Settings', 'woocommerce'),
                    'type'     => 'title',
                    'desc'     => __('Configure the wholesale settings below.', 'woocommerce'),
                    'id'       => 'wwd_wholesale_settings'
                ],
                'wholesale_min_quantity' => [
                    'name'     => __('Minimum Wholesale Quantity', 'woocommerce'),
                    'type'     => 'number',
                    'desc'     => __('Set the minimum quantity required for wholesale pricing.', 'woocommerce'),
                    'id'       => 'wwd_wholesale_min_quantity',
                    'value'    => $wholesale_min_quantity, // Set the saved value here
                    'css'      => 'min-width:300px;'
                ],
                'wholesale_user_role' => [
                    'name'     => __('Wholesale User Role', 'woocommerce'),
                    'type'     => 'select',
                    'desc'     => __('Select the user role that should have wholesale pricing.', 'woocommerce'),
                    'id'       => 'wwd_wholesale_user_role',
                    'options'  => $roles_options,
                    'value'    => $wholesale_user_role // Set the saved value here
                ],
                'section_end' => [
                    'type'     => 'sectionend',
                    'id'       => 'wwd_wholesale_settings_end'
                ],
            ];
            return apply_filters('wwd_wholesale_settings', $settings);
        }

        /**
         * Save the settings for the Wholesale tab.
         */
        public function save_wholesale_settings()
        {
            if (isset($_POST['wwd_wholesale_min_quantity'])) {
                $wholesale_min_quantity = sanitize_text_field($_POST['wwd_wholesale_min_quantity']);
                update_option('wwd_wholesale_min_quantity', $wholesale_min_quantity);
            }
            if (isset($_POST['wwd_wholesale_user_role'])) {
                $wholesale_user_role = sanitize_text_field($_POST['wwd_wholesale_user_role']);
                update_option('wwd_wholesale_user_role', $wholesale_user_role);
            }
        }

        /**
         * Add a wholesale price field to the product pricing section.
         */
        public function add_wholesale_price_field()
        {
            woocommerce_wp_text_input([
                'id'          => '_wholesale_price',
                'label'       => 'Wholesale Price',
                'placeholder' => 'Enter wholesale price',
                'desc_tip'    => 'true',
                'description' => 'Enter the wholesale price for this product.',
                'type'        => 'text',
                'data_type'   => 'price'
            ]);
        }

        /**
         * Save the wholesale price field data.
         * 
         * @param int $post_id The ID of the post being saved.
         */
        public function save_wholesale_price_field($post_id)
        {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
            if (isset($_POST['_wholesale_price'])) {
                $wholesale_price = sanitize_text_field($_POST['_wholesale_price']);
                update_post_meta($post_id, '_wholesale_price', $wholesale_price);
            }
        }

        /**
         * Check if the current user has the selected user role.
         * 
         * @return bool True if the user has the role selected in the settings, false otherwise.
         */
        public function is_selected_user_role()
        {
            if (!is_user_logged_in()) {
                return false;
            }
            $current_user = wp_get_current_user();
            $wholesale_role = get_option('wwd_wholesale_user_role', '');
            return in_array($wholesale_role, $current_user->roles);
        }

        /**
         * Check if the cart items meet the minimum quantity requirements for the selected user role.
         */
        public function check_minimum_quantity_per_product()
        {
            if (!$this->is_selected_user_role()) {
                return;
            }

            $minimum_quantity = !empty(get_option('wwd_wholesale_min_quantity')) ? get_option('wwd_wholesale_min_quantity') : 3; // Default 3 if not set

            $cart = WC()->cart->get_cart();
            $total_quantity = array_sum(array_column($cart, 'quantity'));
            $productQty = $total_quantity < $minimum_quantity || $total_quantity > $minimum_quantity;

            if ($productQty) {
                wc_add_notice(sprintf('Please add at least %d products to your cart to receive a wholesaler discount.', $minimum_quantity), 'error');
            }
        }

        /**
         * Adjust cart item prices based on total quantity and wholesale price.
         * 
         * @param WC_Cart $cart The cart object.
         */
        public function adjust_cart_item_prices_based_on_total_quantity($cart)
        {
            if (!$this->is_selected_user_role()) {
                return;
            }

            $minimum_quantity = !empty(get_option('wwd_wholesale_min_quantity')) ? get_option('wwd_wholesale_min_quantity') : 3; // Default to 3 if not set

            $total_quantity = array_sum(array_column($cart->get_cart(), 'quantity'));

            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $product_id = $cart_item['product_id'];
                $wholesale_price = get_post_meta($product_id, '_wholesale_price', true);

                // Check if the total quantity is equal to  minimum quantity
                if ($total_quantity == $minimum_quantity && $wholesale_price && is_numeric($wholesale_price)) {
                    $cart_item['data']->set_price(floatval($wholesale_price));
                } else {
                    // Reset to regular price if below the minimum quantity
                    $regular_price = get_post_meta($product_id, '_price', true);

                    $cart_item['data']->set_price(floatval($regular_price));
                }
            }
        }


        /**
         * Change the product price text based on the total quantity on the product page.
         */
        public function change_product_price_text()
        {
            if (!$this->is_selected_user_role()) {
                return;
            }
            if (is_product()) {
                global $product;
                $regular_price = $product->get_regular_price();
                $sale_price = $product->get_sale_price();
                $price = $sale_price ? $sale_price : $regular_price;
                $minimum_quantity = !empty(get_option('wwd_wholesale_min_quantity')) ? get_option('wwd_wholesale_min_quantity') : 3;

                $wholesale_price = get_post_meta($product->get_id(), '_wholesale_price', true);
                if (!$wholesale_price || !is_numeric($wholesale_price)) {
                    $wholesale_price = $price;
                }
?>
                <script type="text/javascript">
                    (function($) {
                        $(document).ready(function() {
                            var productRegularPrice = parseFloat(<?php echo json_encode($price); ?>);
                            var productSalePrice = parseFloat('<?php echo esc_js($sale_price); ?>');
                            var wholesalePrice = parseFloat(<?php echo json_encode(floatval($wholesale_price)); ?>);
                            var minQty = <?php echo json_encode($minimum_quantity); ?>;

                            var originalPrice = productSalePrice ? productSalePrice : productRegularPrice;

                            // Update price based on quantity
                            function updatePrice() {
                                var inputQty = parseInt($("input.qty").val(), 10);
                                var newPrice = originalPrice;

                                if (inputQty == minQty) {
                                    newPrice = wholesalePrice || originalPrice;
                                }

                                // Update only the price inside <ins> tag if sale price exists
                                if (productSalePrice) {
                                    $('.price ins .woocommerce-Price-amount bdi').text('$' + newPrice.toFixed(2));
                                } else {
                                    // If no sale price, update the regular price
                                    $('.price .woocommerce-Price-amount bdi').text('$' + newPrice.toFixed(2));
                                }
                            }

                            // Attach change event to quantity input
                            $("input.qty").on('change', function() {
                                updatePrice();
                            });
                        });
                    })(jQuery);
                </script>
<?php
            }
        }
    }
endif;
