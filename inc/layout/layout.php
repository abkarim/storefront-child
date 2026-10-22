<?php

namespace StoreFront_Child;

class Layout
{
    public function __construct()
    {
        add_filter('woocommerce_get_country_locale', [$this, 'sf_blocks_checkout_hide_fields']);
        add_filter('woocommerce_default_address_fields', [$this, 'sf_custom_default_address_fields'], 9999);
        add_filter('woocommerce_checkout_fields', [$this, 'sf_custom_checkout_fields'], 9999);
        add_filter('woocommerce_enable_order_notes_field', '__return_false', 9999);
        add_filter('woocommerce_checkout_show_terms', '__return_false', 9999);
        add_filter('woocommerce_terms_page_id', '__return_zero', 9999);
        add_filter('woocommerce_get_terms_and_conditions_checkbox_text', '__return_empty_string', 9999);
        add_filter('woocommerce_get_privacy_policy_text', '__return_empty_string', 9999);
        add_filter('woocommerce_checkout_posted_data', [$this, 'sf_filter_checkout_posted_data']);
        add_action('woocommerce_checkout_create_order', [$this, 'sf_split_fullname_into_order_names'], 10, 2);
        add_filter('body_class', [$this, 'sf_child_check_cod_only_body_class']);

        add_action('wp', [$this, "reposition_storefront_page_title"]);

        add_action("wp_head", [$this, "apply_styles"]);

        add_filter('woocommerce_get_price_html', [$this, 'sf_child_add_explicit_price_labels'], 100, 2);

        add_action('woocommerce_after_shop_loop_item', [$this, 'add_archive_custom_buttons'], 15);

        // 1. Remove the default thumbnail from the main archive loop
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10);

        // 2. Explicitly remove it from shortcode loops to prevent the duplicate 2nd image
        remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 11);

        // 2. Add our custom function that includes the wrapper
        add_action('woocommerce_before_shop_loop_item_title', [$this, 'custom_woocommerce_template_loop_product_thumbnail'], 10);

        // merge gallery and product summary containers on single product pages to allow for a more flexible layout structure 
        add_action('woocommerce_before_single_product_summary', [$this, 'sf_child_open_custom_product_wrapper'], 5);
        add_action('woocommerce_after_single_product_summary', [$this, 'sf_child_close_custom_product_wrapper'], 5);


        add_action('woocommerce_single_product_summary', [$this, 'add_custom_delivery_details'], 38);
        add_action('woocommerce_after_add_to_cart_button', [$this, 'add_checkout_button']);

        // Option 2: Buy Now - isolate single item checkout and preserve cart session
        add_filter('woocommerce_add_to_cart_validation', [$this, 'sf_child_handle_buy_now_cart_isolation'], 10, 2);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'sf_child_buy_now_redirect']);
        add_action('template_redirect', [$this, 'sf_child_maybe_restore_saved_cart_on_abandon']);
        add_action('woocommerce_thankyou', [$this, 'sf_child_restore_saved_cart_after_purchase'], 10, 1);

        // disable storefront's default header elements since we're replacing them with a custom template part
        remove_action('storefront_header', 'storefront_site_branding', 20);
        remove_action('storefront_header', 'storefront_product_search', 40);

        /**
         * Register the [woocommerce_sf_child_compare_products] Shortcode
         */
        add_shortcode('woocommerce_sf_child_compare_products', [$this, 'sf_child_render_compare_page_content']);
        add_shortcode('track_order_via_phone_sf_child', [$this, 'track_order_via_phone']);
        add_action('wp_ajax_sf_process_phone_only_order_tracking', [$this, 'track_order_via_phone_handler']);
        add_action('wp_ajax_nopriv_sf_process_phone_only_order_tracking', [$this, 'track_order_via_phone_handler']);

        add_action('init', [$this, 'sf_child_remove_handheld_footer_bar']);

        add_action('woocommerce_before_main_content', [$this, 'display_category_hero_header'], 2);
        add_action('wp_ajax_sf_child_quick_view', [$this, 'sf_child_quick_view_handler']);
        add_action('wp_ajax_nopriv_sf_child_quick_view', [$this, 'sf_child_quick_view_handler']);
    }

    function sf_child_quick_view_handler()
    {
        if (!isset($_GET['product_id'])) {
            wp_send_json_error('Missing Product ID');
        }

        $product_id = intval($_GET['product_id']);
        global $post, $product;

        $post = get_post($product_id);
        if (!$post || 'product' !== $post->post_type) {
            wp_send_json_error('Invalid Product');
        }

        setup_postdata($post);
        $product = wc_get_product($product_id);

        ob_start();
?>
        <div class="sf-qv-wrapper product">
            <div class="sf-qv-image">
                <?php echo $product->get_image('large'); ?>
            </div>
            <div class="sf-qv-details summary entry-summary">
                <h2 class="product_title entry-title"><?php echo $product->get_title(); ?></h2>
                <p class="price"><?php echo $product->get_price_html(); ?></p>
                <div class="woocommerce-product-details__short-description">
                    <?php echo apply_filters('woocommerce_short_description', $product->get_short_description()); ?>
                </div>
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
        $html = ob_get_clean();

        wp_send_json_success($html);
    }

    public function track_order_via_phone_handler()
    {
        // Nonce verification check
        if (! isset($_POST['security']) || ! wp_verify_nonce($_POST['security'], 'sf_phone_only_track_nonce_action')) {
            echo '<p class="sf-track-error">Security check failed. Please refresh the page.</p>';
            wp_die();
        }

        // Clean up incoming phone input format
        $input_phone = isset($_POST['billing_phone']) ? preg_replace('/\D/', '', $_POST['billing_phone']) : '';

        if (empty($input_phone)) {
            echo '<p class="sf-track-error">Please enter a valid phone number.</p>';
            wp_die();
        }

        // High performance WooCommerce Query to locate orders matching this billing phone key
        $order_query = new \WC_Order_Query(array(
            'limit'         => 10, // Adjust to pull more or fewer records per scan cycle
            'orderby'       => 'date',
            'order'         => 'DESC',
            'billing_phone' => $input_phone,
        ));

        $orders = $order_query->get_orders();

        if (empty($orders)) {
            echo '<p class="sf-track-error">No order history found associated with that phone number.</p>';
            wp_die();
        }

        echo sprintf('<p class="sf-tracker-count">Found <strong>%d</strong> order(s) matching your records:</p>', count($orders));

        // Loop through discovered order histories and print details
        foreach ($orders as $order) :
            $order_id     = $order->get_id();
            $status_label = wc_get_order_status_name($order->get_status());
            $date_created = wc_format_datetime($order->get_date_created());
            $order_total  = $order->get_formatted_order_total();
        ?>
            <div class="sf-track-order-card">

                <div class="sf-track-card-meta info">
                    <p><strong>Date:</strong> <?php echo esc_html($date_created); ?></p>
                    <p><strong>Status - </strong><span class="sf-card-status status-<?php echo esc_attr($order->get_status()); ?>"><?php echo esc_html($status_label); ?></span></p>
                </div>

                <div class="sf-track-card-header info">
                    <p><strong>Order #</strong><?php echo esc_html($order_id); ?></p>
                    <p><strong>Total Amount:</strong> <?php echo wp_kses_post($order_total); ?></p>
                </div>

                <table class="sf-track-items-table">
                    <thead>
                        <tr>
                            <th>Product Items</th>
                            <th style="width: 50px; text-align: center;">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order->get_items() as $item_id => $item) : ?>
                            <tr>
                                <td><?php echo esc_html($item->get_name()); ?></td>
                                <td style="text-align: center;"><?php echo esc_html($item->get_quantity()); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php
        endforeach;

        wp_die();
    }

    public function track_order_via_phone()
    {
        wp_enqueue_script('jquery');

        ob_start();
        ?>
        <div class="sf-order-tracker-container">
            <form id="sf-phone-tracker-form" method="post">
                <div class="sf-tracker-input-group">
                    <input type="tel" id="tracker_phone" name="billing_phone" placeholder="01XXXXXXXXX" required />
                </div>

                <?php wp_nonce_field('sf_phone_only_track_nonce_action', 'sf_phone_only_track_nonce'); ?>
                <button type="submit" class="button alt" id="sf-tracker-submit-btn"><?php esc_html_e('Find My Orders', 'storefront-child'); ?></button>
            </form>

            <div id="sf-tracker-results" class="sf-tracker-results-box" style="display:none;"></div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#sf-phone-tracker-form').on('submit', function(e) {
                    e.preventDefault();

                    var resultBox = $('#sf-tracker-results');
                    var submitBtn = $('#sf-tracker-submit-btn');

                    submitBtn.prop('disabled', true).text('Searching...');
                    resultBox.hide().html('');

                    var formData = {
                        action: 'sf_process_phone_only_order_tracking',
                        billing_phone: $('#tracker_phone').val(),
                        security: $('#sf_phone_only_track_nonce').val()
                    };

                    $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', formData, function(response) {
                        submitBtn.prop('disabled', false).text('Find My Orders');
                        resultBox.html(response).fadeIn(300);
                    });
                });
            });
        </script>
    <?php
        return ob_get_clean();
    }

    public function display_category_hero_header()
    {
        // Drop out immediately if this is a general shop page or search page, instead of a category grid
        if (! is_product_category()) {
            return;
        }

        $current_category = get_queried_object();
        if (! $current_category || !isset($current_category->term_id)) {
            return;
        }

        // Get the category image
        $image_id = get_term_meta($current_category->term_id, 'header-image', true);
        $image_html   = '';

        if ($image_id) {
            // Output an optimized, accessible HTML image element component
            $image_html = wp_get_attachment_image($image_id, 'full', false, array(
                'class' => 'sf-cat-header-img',
                'alt'   => esc_attr($current_category->name),
            ));
        }

    ?>
        <div class="sf-clean-category-header-block">
            <?php if (! empty($image_html)) : ?>
                <div class="sf-cat-header-image-wrapper">
                    <?php echo $image_html; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php
    }


    public function sf_blocks_checkout_hide_fields($locale)
    {
        $fields_to_hide = ['last_name', 'company', 'postcode', 'city', 'address_2'];

        foreach ($locale as $country_code => $fields) {
            foreach ($fields_to_hide as $field_key) {
                $locale[$country_code][$field_key]['hidden']   = true;
                $locale[$country_code][$field_key]['required'] = false;
            }

            $locale[$country_code]['first_name']['label']       = esc_html__('Full Name', 'storefront-child');
            $locale[$country_code]['first_name']['placeholder'] = esc_html__('Full Name', 'storefront-child');
            $locale[$country_code]['first_name']['priority']    = 10;

            if (isset($locale[$country_code]['phone'])) {
                $locale[$country_code]['phone']['priority'] = 20;
            }

            $locale[$country_code]['state']['label']       = esc_html__('District', 'storefront-child');
            $locale[$country_code]['state']['placeholder'] = esc_html__('District', 'storefront-child');
            $locale[$country_code]['state']['priority']    = 30;
            $locale[$country_code]['state']['hidden']      = false;
            $locale[$country_code]['state']['required']    = true;

            $locale[$country_code]['address_1']['label']       = esc_html__('Address', 'storefront-child');
            $locale[$country_code]['address_1']['placeholder'] = esc_html__('Address', 'storefront-child');
            $locale[$country_code]['address_1']['priority']    = 35;
        }

        return $locale;
    }

    public function sf_custom_default_address_fields($fields)
    {
        if (isset($fields['first_name'])) {
            $fields['first_name']['label']       = esc_html__('Full Name', 'storefront-child');
            $fields['first_name']['placeholder'] = esc_html__('Full Name', 'storefront-child');
            $fields['first_name']['class']       = ['form-row-wide'];
            $fields['first_name']['priority']    = 10;
        }

        if (isset($fields['last_name'])) {
            $fields['last_name']['label']    = '';
            $fields['last_name']['required'] = false;
            $fields['last_name']['hidden']   = true;
            $fields['last_name']['class']    = ['hidden'];
        }

        if (isset($fields['phone'])) {
            $fields['phone']['priority'] = 20;
            $fields['phone']['class']    = ['form-row-wide'];
        }

        if (isset($fields['state'])) {
            $fields['state']['label']       = esc_html__('District', 'storefront-child');
            $fields['state']['placeholder'] = esc_html__('Select District', 'storefront-child');
            $fields['state']['priority']    = 30;
            $fields['state']['class']       = ['form-row-first'];
            $fields['state']['hidden']      = false;
            $fields['state']['required']    = true;
        }

        if (isset($fields['address_1'])) {
            $fields['address_1']['label']       = esc_html__('Address', 'storefront-child');
            $fields['address_1']['placeholder'] = esc_html__('Address (Area, Road, House)', 'storefront-child');
            $fields['address_1']['priority']    = 35;
            $fields['address_1']['class']       = ['form-row-last'];
        }

        foreach (['company', 'postcode', 'city', 'address_2'] as $key) {
            if (isset($fields[$key])) {
                $fields[$key]['required'] = false;
                $fields[$key]['hidden']   = true;
                $fields[$key]['class']    = ['hidden'];
            }
        }

        return $fields;
    }

    public function sf_custom_checkout_fields($fields)
    {
        // Billing fields
        if (isset($fields['billing']['billing_first_name'])) {
            $fields['billing']['billing_first_name']['label']       = esc_html__('Full Name', 'storefront-child');
            $fields['billing']['billing_first_name']['placeholder'] = esc_html__('Full Name', 'storefront-child');
            $fields['billing']['billing_first_name']['class']       = ['form-row-wide'];
            $fields['billing']['billing_first_name']['priority']    = 10;
        }

        if (isset($fields['billing']['billing_last_name'])) {
            $fields['billing']['billing_last_name']['required'] = false;
            $fields['billing']['billing_last_name']['hidden']   = true;
            $fields['billing']['billing_last_name']['class']    = ['hidden'];
            unset($fields['billing']['billing_last_name']);
        }

        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['priority'] = 20;
            $fields['billing']['billing_phone']['class']    = ['form-row-wide'];
        }

        if (isset($fields['billing']['billing_state'])) {
            $fields['billing']['billing_state']['label']       = esc_html__('District', 'storefront-child');
            $fields['billing']['billing_state']['placeholder'] = esc_html__('Select District', 'storefront-child');
            $fields['billing']['billing_state']['priority']    = 30;
            $fields['billing']['billing_state']['class']       = ['form-row-first'];
            $fields['billing']['billing_state']['hidden']      = false;
            $fields['billing']['billing_state']['required']    = true;
        }

        if (isset($fields['billing']['billing_address_1'])) {
            $fields['billing']['billing_address_1']['label']       = esc_html__('Address', 'storefront-child');
            $fields['billing']['billing_address_1']['placeholder'] = esc_html__('Address (Area, Road, House)', 'storefront-child');
            $fields['billing']['billing_address_1']['priority']    = 35;
            $fields['billing']['billing_address_1']['class']       = ['form-row-last'];
        }

        // Shipping fields
        if (isset($fields['shipping']['shipping_first_name'])) {
            $fields['shipping']['shipping_first_name']['label']       = esc_html__('Full Name', 'storefront-child');
            $fields['shipping']['shipping_first_name']['placeholder'] = esc_html__('Full Name', 'storefront-child');
            $fields['shipping']['shipping_first_name']['class']       = ['form-row-wide'];
            $fields['shipping']['shipping_first_name']['priority']    = 10;
        }

        if (isset($fields['shipping']['shipping_last_name'])) {
            $fields['shipping']['shipping_last_name']['required'] = false;
            $fields['shipping']['shipping_last_name']['hidden']   = true;
            $fields['shipping']['shipping_last_name']['class']    = ['hidden'];
            unset($fields['shipping']['shipping_last_name']);
        }

        if (isset($fields['shipping']['shipping_phone'])) {
            $fields['shipping']['shipping_phone']['priority'] = 20;
            $fields['shipping']['shipping_phone']['class']    = ['form-row-wide'];
        }

        if (isset($fields['shipping']['shipping_state'])) {
            $fields['shipping']['shipping_state']['label']       = esc_html__('District', 'storefront-child');
            $fields['shipping']['shipping_state']['placeholder'] = esc_html__('Select District', 'storefront-child');
            $fields['shipping']['shipping_state']['priority']    = 30;
            $fields['shipping']['shipping_state']['class']       = ['form-row-first'];
            $fields['shipping']['shipping_state']['hidden']      = false;
            $fields['shipping']['shipping_state']['required']    = true;
        }

        if (isset($fields['shipping']['shipping_address_1'])) {
            $fields['shipping']['shipping_address_1']['label']       = esc_html__('Address', 'storefront-child');
            $fields['shipping']['shipping_address_1']['placeholder'] = esc_html__('Address (Area, Road, House)', 'storefront-child');
            $fields['shipping']['shipping_address_1']['priority']    = 35;
            $fields['shipping']['shipping_address_1']['class']       = ['form-row-last'];
        }

        // Remove order notes
        if (isset($fields['order']['order_comments'])) {
            unset($fields['order']['order_comments']);
        }

        return $fields;
    }

    public function sf_filter_checkout_posted_data($data)
    {
        if (! empty($data['billing_first_name']) && empty($data['billing_last_name'])) {
            $parts = explode(' ', trim($data['billing_first_name']), 2);
            $data['billing_first_name'] = $parts[0];
            $data['billing_last_name']  = isset($parts[1]) ? $parts[1] : '';
        }
        if (! empty($data['shipping_first_name']) && empty($data['shipping_last_name'])) {
            $parts = explode(' ', trim($data['shipping_first_name']), 2);
            $data['shipping_first_name'] = $parts[0];
            $data['shipping_last_name']  = isset($parts[1]) ? $parts[1] : '';
        }
        return $data;
    }

    public function sf_split_fullname_into_order_names($order, $data)
    {
        $billing_first = $order->get_billing_first_name();
        $billing_last  = $order->get_billing_last_name();
        if (! empty($billing_first) && empty($billing_last)) {
            $parts = explode(' ', trim($billing_first), 2);
            $order->set_billing_first_name($parts[0]);
            $order->set_billing_last_name(isset($parts[1]) ? $parts[1] : '');
        }

        $shipping_first = $order->get_shipping_first_name();
        $shipping_last  = $order->get_shipping_last_name();
        if (! empty($shipping_first) && empty($shipping_last)) {
            $parts = explode(' ', trim($shipping_first), 2);
            $order->set_shipping_first_name($parts[0]);
            $order->set_shipping_last_name(isset($parts[1]) ? $parts[1] : '');
        }
    }

    /**
     * Check if Cash on Delivery is the only available/enabled payment gateway.
     *
     * @return bool
     */
    public function is_cod_only_payment()
    {
        if (! function_exists('WC') || ! WC()->payment_gateways()) {
            return false;
        }

        // Check currently available gateways
        $available = WC()->payment_gateways()->get_available_payment_gateways();
        if (! empty($available)) {
            return (count($available) === 1 && isset($available['cod']));
        }

        // Fallback: check all gateways enabled in settings
        $enabled = [];
        $all_gateways = WC()->payment_gateways()->payment_gateways();
        if (is_array($all_gateways)) {
            foreach ($all_gateways as $gateway) {
                if (isset($gateway->enabled) && 'yes' === $gateway->enabled) {
                    $enabled[$gateway->id] = $gateway;
                }
            }
        }

        return (count($enabled) === 1 && isset($enabled['cod']));
    }

    /**
     * Add a body class if Cash on Delivery is the only available payment gateway.
     *
     * @param array $classes
     * @return array
     */
    public function sf_child_check_cod_only_body_class($classes)
    {
        if (is_checkout() && ! is_order_received_page() && $this->is_cod_only_payment()) {
            $classes[] = 'sf-cod-only-payment';
        }
        return $classes;
    }

    public function add_checkout_button()
    {
        global $product;

        if (! $product || ! $product->is_purchasable() || ! $product->is_in_stock()) {
            return;
        }

        if ($product->is_type('variable')) {
            echo sprintf(
                '<button type="submit" name="buy_now" value="%d" class="button alt sf-buy-now-button">%s</button>',
                esc_attr($product->get_id()),
                esc_html__('Buy Now', 'storefront-child')
            );
            return;
        }

        // Get the clean checkout page link address dynamically
        $checkout_url = wc_get_checkout_url();

        // Append the direct add-to-cart and buy_now parameter mappings
        $buy_now_url = add_query_arg([
            'add-to-cart' => $product->get_id(),
            'buy_now'     => 1,
        ], $checkout_url);

        // Output a semantic HTML anchor button block
        echo sprintf(
            '<a href="%s" class="button alt sf-buy-now-button">%s</a>',
            esc_url($buy_now_url),
            esc_html__('Buy Now', 'storefront-child')
        );
    }

    /**
     * Option 2: Isolate the Buy Now product by backing up the existing cart to session
     * and emptying the active cart before the Buy Now product is added.
     *
     * @param bool $passed_validation
     * @param int  $product_id
     * @return bool
     */
    public function sf_child_handle_buy_now_cart_isolation($passed_validation, $product_id)
    {
        if (! isset($_REQUEST['buy_now']) || ! $passed_validation) {
            return $passed_validation;
        }

        if (! function_exists('WC') || ! WC()->session || ! WC()->cart) {
            return $passed_validation;
        }

        // Ensure customer session cookie is established
        if (! WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }

        // Save existing cart items if not already backed up
        $existing_saved = WC()->session->get('sf_child_saved_cart');
        if (empty($existing_saved) && ! WC()->cart->is_empty()) {
            $saved_cart = [];
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $saved_cart[] = [
                    'product_id'     => $cart_item['product_id'],
                    'quantity'       => $cart_item['quantity'],
                    'variation_id'   => isset($cart_item['variation_id']) ? $cart_item['variation_id'] : 0,
                    'variation'      => isset($cart_item['variation']) ? $cart_item['variation'] : [],
                    'cart_item_data' => array_diff_key(
                        $cart_item,
                        array_flip([
                            'key',
                            'product_id',
                            'variation_id',
                            'variation',
                            'quantity',
                            'data',
                            'data_hash',
                            'line_tax_data',
                            'line_subtotal',
                            'line_subtotal_tax',
                            'line_total',
                            'line_tax',
                        ])
                    ),
                ];
            }
            WC()->session->set('sf_child_saved_cart', $saved_cart);
        }

        WC()->session->set('sf_child_is_buy_now', true);

        // Empty current cart so only the Buy Now item is added
        WC()->cart->empty_cart();

        return $passed_validation;
    }

    /**
     * Ensure Buy Now always redirects directly to checkout.
     *
     * @param string $url
     * @return string
     */
    public function sf_child_buy_now_redirect($url)
    {
        if (isset($_REQUEST['buy_now'])) {
            return wc_get_checkout_url();
        }
        return $url;
    }

    /**
     * Restore previous cart if the customer navigates away from checkout without purchasing.
     */
    public function sf_child_maybe_restore_saved_cart_on_abandon()
    {
        if (! function_exists('WC') || ! WC()->session || ! WC()->cart) {
            return;
        }

        // Do not process in admin, AJAX requests, or REST API calls (such as WooCommerce store block API)
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        $saved_cart = WC()->session->get('sf_child_saved_cart');
        if (empty($saved_cart) || ! is_array($saved_cart)) {
            return;
        }

        // If currently processing a buy_now action, do not restore yet
        if (isset($_REQUEST['buy_now'])) {
            return;
        }

        // If on the checkout page (and not order-received), user is still completing checkout
        if (is_checkout() && ! is_order_received_page()) {
            return;
        }

        // On thank-you page, sf_child_restore_saved_cart_after_purchase handles restoration
        if (is_order_received_page()) {
            return;
        }

        // Customer abandoned checkout; clear temporary item and restore original cart
        WC()->cart->empty_cart();
        foreach ($saved_cart as $item) {
            WC()->cart->add_to_cart(
                $item['product_id'],
                $item['quantity'],
                $item['variation_id'],
                $item['variation'],
                $item['cart_item_data'] ?? []
            );
        }

        WC()->session->__unset('sf_child_saved_cart');
        WC()->session->__unset('sf_child_is_buy_now');
    }

    /**
     * Restore previous cart after the Buy Now order has been successfully placed.
     *
     * @param int $order_id
     */
    public function sf_child_restore_saved_cart_after_purchase($order_id)
    {
        if (! function_exists('WC') || ! WC()->session || ! WC()->cart) {
            return;
        }

        $saved_cart = WC()->session->get('sf_child_saved_cart');
        if (! empty($saved_cart) && is_array($saved_cart)) {
            foreach ($saved_cart as $item) {
                WC()->cart->add_to_cart(
                    $item['product_id'],
                    $item['quantity'],
                    $item['variation_id'],
                    $item['variation'],
                    $item['cart_item_data'] ?? []
                );
            }
        }

        WC()->session->__unset('sf_child_saved_cart');
        WC()->session->__unset('sf_child_is_buy_now');
    }

    public function add_custom_delivery_details()
    {
        global $product;

        // Optional Guard: Drop out early if the product object isn't available
        if (! $product) {
            return;
        }

        if (is_active_sidebar('sf-child-product-delivery-widget')) {
            dynamic_sidebar('sf-child-product-delivery-widget');
        }
    }

    public function sf_child_remove_handheld_footer_bar()
    {
        remove_action('storefront_footer', 'storefront_handheld_footer_bar', 999);
    }

    function custom_woocommerce_template_loop_product_thumbnail()
    {
        echo '<div class="custom-archive-image-wrap">';
        echo woocommerce_template_loop_product_thumbnail();
        echo '</div>';
    }

    function sf_child_render_compare_page_content()
    {
        // Check if WooCommerce is active to prevent errors
        if (! class_exists('WooCommerce')) {
            return '<p>WooCommerce must be installed to compare products.</p>';
        }

        // Capture layout output safely
        ob_start();
    ?>
        <div class="sf-compare-page-content">
            <div id="sf-compare-empty" class="sf-compare-status-notice">
                <p><?php esc_html_e('Your product comparison canvas is currently empty.', 'storefront-child'); ?></p>
            </div>

            <section id="sf-compare-contents">
                <div id="sf-compare-clear-all">
                    <button class="button danger">Clear All</button>
                </div>
                <div class="table-container">
                    <table id="sf-compare-table" class="sf-compare-matrix-table">
                        <thead>
                            <tr id="row-product-triggers" class="sf-compare-row">
                                <th class="sf-label-column"><?php esc_html_e('Action', 'storefront-child'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="row-product-images" class="sf-compare-row">
                                <td class="sf-label-column"><strong><?php esc_html_e('Image', 'storefront-child'); ?></strong></td>
                            </tr>
                            <tr id="row-product-titles" class="sf-compare-row">
                                <td class="sf-label-column"><strong><?php esc_html_e('Product Name', 'storefront-child'); ?></strong></td>
                            </tr>
                            <tr id="row-product-prices" class="sf-compare-row">
                                <td class="sf-label-column"><strong><?php esc_html_e('Price', 'storefront-child'); ?></strong></td>
                            </tr>
                            <tr id="row-product-stock" class="sf-compare-row">
                                <td class="sf-label-column"><strong><?php esc_html_e('Availability', 'storefront-child'); ?></strong></td>
                            </tr>
                            <tr id="row-product-buy" class="sf-compare-row">
                                <td class="sf-label-column"><strong><?php esc_html_e('Purchase', 'storefront-child'); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    <?php
        return ob_get_clean();
    }



    function add_archive_custom_buttons()
    {
        global $product;

        if (! $product) {
            return;
        }

        $product_id = $product->get_id();

        $buttonsHtml = "
            <div class='sf-archive-action-buttons-group' data-product-id='{$product_id}'>
                <a href='#' class='sf-archive-btn sf-compare-btn' title='Compare' > 
                    <span class='sf-icon'>⇄</span>
                </a>
                <a href='#' class='sf-archive-btn sf-quickview-btn' title='Quick View'>
                <span class='sf-icon'>
                        <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' width='16' height='16' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' style='display: inline-block; vertical-align: middle;'>
                            <path d='M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z'></path>
                            <circle cx='12' cy='12' r='3'></circle>
                        </svg>
                    </span> 
                </a>
            </div>
        ";

        echo $buttonsHtml;
    }

    function sf_child_open_custom_product_wrapper()
    {
        echo '<div class="sf-custom-product-gallery-summary-layout-wrapper">';
    }

    function sf_child_close_custom_product_wrapper()
    {
        echo '</div>';
    }
    function sf_child_add_explicit_price_labels($price_html, $product)
    {
        // Only apply this layout changes on the single product description container
        if (! is_product() || ! is_single() || wc_get_loop_prop('name')) {
            return $price_html;
        }

        // 1. SIMPLE & EXTERNAL PRODUCTS
        if ($product->is_type('simple') || $product->is_type('external')) {
            $regular_price = $product->get_regular_price();
            $sale_price    = $product->get_sale_price();

            // If the product is on sale, show both labeled blocks
            if (! empty($sale_price) && $sale_price < $regular_price) {
                return sprintf(
                    '
                    <div class="price">
                        <div class="special">
                            <span class="sf-price-label">%3$s</span>
                            <span class="sf-price-value"><ins>%4$s</ins></span>
                        </div>
                        <div class="regular">
                            <span class="sf-price-label">%1$s</span>
                            <span class="sf-price-value"><del>%2$s</del></span>
                        </div>
                    </div>
                    ',
                    __('Regular Price', 'storefront-child'),
                    wc_price($regular_price),
                    __('Special Price', 'storefront-child'),
                    wc_price($sale_price)
                );
            }
        }

        // 2. VARIABLE PRODUCTS
        if ($product->is_type('variable')) {
            $prices = $product->get_variation_prices();

            if (! empty($prices['regular_price']) && ! empty($prices['sale_price'])) {
                $min_reg_price  = current($prices['regular_price']);
                $min_sale_price = current($prices['sale_price']);

                if ($min_sale_price < $min_reg_price) {
                    return sprintf(
                        '
                        <div class="price">
                            <div class="special">
                                <span class="sf-price-label">%3$s</span>
                                <span class="sf-price-value"><ins>%4$s</ins></span>
                            </div>
                            <div class="regular">
                                <span class="sf-price-label">%1$s</span>
                                <span class="sf-price-value"><del>%2$s</del></span>
                            </div>
                        </div>
                    ',
                        __('Regular Price', 'storefront-child'),
                        wc_price($min_reg_price),
                        __('Special Price', 'storefront-child'),
                        wc_price($min_sale_price)
                    );
                }
            }
        }

        // Fallback default format wrapper if the product is not discounted
        return '<div class="sf-price-row sf-regular-price-row"><span class="sf-price-label">' . __('Price', 'storefront-child') . '</span> <span class="sf-price-value">' . $price_html . '</span></div>';
    }

    public function reposition_storefront_page_title()
    {
        // Stop static pages from rendering their standard title blocks
        remove_action('storefront_page', 'storefront_page_header', 10);

        // Stop homepage template modules from spitting out separate headers
        remove_action('storefront_homepage', 'storefront_homepage_header', 10);

        // Prevent WooCommerce archive grids from injecting their loop header streams
        add_filter('woocommerce_show_page_title', '__return_false', 99);

        add_action('storefront_before_content', [$this, 'render_minimal_page_title'], 5);
    }

    /**
     * Dynamically determines the current page context and renders ONLY the raw text title
     */
    public function render_minimal_page_title()
    {
        // If we are looking at a single product page loop, bail out immediately and render nothing
        if (function_exists('is_product') && is_product()) {
            return;
        }

        $title = '';

        // Determine the correct string based on where the user is browsing
        if (function_exists('is_woocommerce') && is_woocommerce()) {
            $title = woocommerce_page_title(false);
        } elseif (is_page() || is_single()) {
            $title = get_the_title();
        } elseif (is_archive()) {
            $title = get_the_archive_title();
        } elseif (is_search()) {
            $title = sprintf(__('Search Results for: %s', 'storefront-child'), get_search_query());
        }

        // If a title string exists, output it cleanly wrapped inside an isolated container box
        if (! empty($title)) {
            echo '<div class="entry-header">';
            echo '    <div class="col-full">';
            echo '        <h1 class="entry-title">' . esc_html($title) . '</h1>';
            echo '    </div>';
            echo '</div>';
        }
    }

    /**
     * Apply styles
     * 
     * @since 1.0.2
     */
    public function apply_styles()
    {
        $this->site_identity_styling();
        $this->product_archive_dynamic_card_styling();
        $this->header_layout_styling();
        $this->checkout_cod_only_styling();
    }

    /**
     * Inject inline CSS on checkout if COD is the only payment method.
     */
    public function checkout_cod_only_styling()
    {
        if (is_checkout() && ! is_order_received_page() && $this->is_cod_only_payment()) {
            ?>
            <style type="text/css" id="sf-child-cod-only-payment-css">
                #payment-method,
                .wc-block-components-checkout-step--payment,
                .wc-block-components-checkout-step--payment-method,
                .wp-block-woocommerce-checkout-payment-methods-block,
                .wc-block-checkout__payment-methods,
                .wc-block-components-checkout-step:has(#payment-method),
                .woocommerce-checkout #payment ul.payment_methods,
                .woocommerce-checkout #payment .wc_payment_methods {
                    display: none !important;
                }
                .woocommerce-checkout #payment {
                    background: transparent !important;
                    padding: 0 !important;
                    border: none !important;
                }
            </style>
            <?php
        }
    }

    /**
     * Site identity styling
     * 
     * @since 1.0.4
     */
    public function site_identity_styling()
    {
        $text        = get_theme_mod('sf_child_logo_text_color', '#333333');
        $text_accent = get_theme_mod('sf_child_logo_text_accent', '#7eb934');
        $link        = get_theme_mod('sf_child_logo_link_color', '#0066cc');
        $link_hover  = get_theme_mod('sf_child_logo_link_hover_color', '#ff5500');
        $bg          = get_theme_mod('sf_child_logo_bg_color', '#ffffff');
        $bg_accent   = get_theme_mod('sf_child_logo_bg_accent_color', '#f9f9f9');
        $border      = get_theme_mod('sf_child_logo_border_color', '#e5e5e5');
        $button_text = get_theme_mod('sf_child_logo_button_text_color', '#ffffff');
        $button_bg   = get_theme_mod('sf_child_logo_button_bg_color', '#0073aa');
        $button_hover_text = get_theme_mod('sf_child_logo_button_hover_text_color', '#e5e5e5');
        $button_hover_bg = get_theme_mod('sf_child_logo_button_hover_bg_color', '#0f0f0f');

    ?>
        <style type="text/css" id="sf-child-site-identity-expanded-css">
            :root {
                --theme-text: <?php echo esc_attr($text); ?>;
                --theme-text-accent: <?php echo esc_attr($text_accent); ?>;
                --theme-link: <?php echo esc_attr($link); ?>;
                --theme-link-hover: <?php echo esc_attr($link_hover); ?>;
                --theme-bg: <?php echo esc_attr($bg); ?>;
                --theme-bg-accent: <?php echo esc_attr($bg_accent); ?>;
                --theme-border: <?php echo esc_attr($border); ?>;
                --theme-button-text: <?php echo esc_attr($button_text); ?>;
                --theme-button-bg: <?php echo esc_attr($button_bg); ?>;
                --theme-button-hover-text: <?php echo esc_attr($button_hover_text); ?>;
                --theme-button-hover-bg: <?php echo esc_attr($button_hover_bg); ?>;
            }
        </style>
    <?php

    }

    /**
     * Adds dynamic inline styles for product archive cards based on Customizer settings.
     * This function outputs a style block with CSS that applies the user-selected background and text
     * 
     * @since 1.0.0
     * @access public
     */
    public function product_archive_dynamic_card_styling()
    {
        // Grab values from database, falling back to defaults if they haven't been adjusted yet
        $bg_color   = get_theme_mod('sf_child_card_bg_color', '#ffffff');
        $text_color = get_theme_mod('sf_child_card_text_color', '#2d2d2d');
        $button_bg_color = get_theme_mod('sf_child_card_button_bg_color', '#0073aa');
        $button_text_color = get_theme_mod('sf_child_card_button_text_color', '#ffffff');
        $border_radius = get_theme_mod('sf_child_card_border_radius', '0.5em');
    ?>
        <style type="text/css" id="sf-child-dynamic-archive-cards">
            /* Apply custom background and layout padding fixes to archive listing cards */
            ul.products li:not(.product-category).product,
            ul.wc-block-grid__products li.wc-block-grid__product,
            .wc-block-product.product,
            ul.wc-block-grid__products li.wc-block-grid__product .wc-block-grid__product-onsale,
            ul.products li:not(.product-category).product .onsale,
            .wp-block-woocommerce-product-sale-badge .wc-block-components-product-sale-badge {
                background-color: <?php echo esc_attr($bg_color); ?> !important;
                border-radius: <?php echo esc_attr($border_radius); ?> !important;
            }

            /* Apply custom text color parameters across core text element layers */
            .wc-block-grid__products .wc-block-grid__product .add_to_cart_button,
            ul.products li:not(.product-category).product .woocommerce-loop-product__title,
            ul.products li:not(.product-category).product .price,
            ul.products li:not(.product-category).product .onsale,
            ul.wc-block-grid__products li.wc-block-grid__product .wc-block-grid__product-onsale,
            .wp-block-woocommerce-product-sale-badge .wc-block-components-product-sale-badge,
            ul.wc-block-grid__products li.wc-block-grid__product .wc-block-grid__product-title,
            .wc-block-product.product .wp-block-post-title a,
            ul.wc-block-grid__products li.wc-block-grid__product .price,
            .wc-block-product.product .wp-block-woocommerce-product-price {
                color: <?php echo esc_attr($text_color); ?> !important;
            }

            .wc-block-grid__products .wc-block-grid__product .add_to_cart_button,
            ul.wc-block-grid__products li.wc-block-grid__product .wc-block-grid__product-add-to-cart,
            ul.products li:not(.product-category).product .add_to_cart_button,
            .product .add_to_cart_button {
                color: <?php echo esc_attr($button_text_color); ?> !important;
                background-color: <?php echo esc_attr($button_bg_color); ?> !important;
                border-radius: <?php echo esc_attr($border_radius); ?> !important;
            }
        </style>
    <?php

    }

    /**
     * Header layout styling
     * 
     * @since 1.0.2
     */
    public function header_layout_styling()
    {
        $text_color = get_theme_mod('storefront_header_text_color', '#7eb934');
        $link_color = get_theme_mod('storefront_header_link_color', '#7eb934');
        $border_bottom_color = get_theme_mod('sf_child_header_border_color', '#7eb934');
        $badge_bg_color = get_theme_mod('sf_child_header_utility_badge_bg_color', '#7eb934');
        $badge_text_color = get_theme_mod('sf_child_header_utility_badge_text_color', '#ffffff');
    ?>
        <style type="text/css" id="sf-child-header-dynamic-css">
            header.site-header {
                color: <?php echo esc_attr($text_color); ?> !important;
                border-bottom-color: <?php echo esc_attr($border_bottom_color); ?> !important;
            }

            header.site-header .search-field-input {
                color: <?php echo esc_attr($text_color); ?> !important;
                border-color: <?php echo esc_attr($text_color); ?> !important;
            }

            header.site-header .search-field-input::placeholder {
                color: <?php echo esc_attr($text_color); ?> !important;
                opacity: 1;
            }

            header.site-header .search-submit-btn {
                color: <?php echo esc_attr($text_color); ?> !important;
                border-color: <?php echo esc_attr($text_color); ?> !important;
            }

            header.site-header a {
                color: <?php echo esc_attr($link_color); ?> !important;
            }

            header.site-header .store-front-child-header-wrapper .header-col-utilities>div>a .utility-counter-badge {
                background-color: <?php echo esc_attr($badge_bg_color); ?> !important;
                color: <?php echo esc_attr($badge_text_color); ?> !important;
            }
        </style>
<?php
    }
}
