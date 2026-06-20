<?php

namespace StoreFront_Child;

class Layout
{
    public function __construct()
    {
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




        // disable storefront's default header elements since we're replacing them with a custom template part
        remove_action('storefront_header', 'storefront_site_branding', 20);
        remove_action('storefront_header', 'storefront_product_search', 40);

        /**
         * Register the [woocommerce_sf_child_compare_products] Shortcode
         */
        add_shortcode('woocommerce_sf_child_compare_products', [$this, 'sf_child_render_compare_page_content']);

        add_action('init', [$this, 'sf_child_remove_handheld_footer_bar']);
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
