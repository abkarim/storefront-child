<?php

namespace StoreFront_Child;

class Layout
{
    public function __construct()
    {
        add_action('wp', [$this, "reposition_storefront_page_title"]);

        add_action("wp_head", [$this, "apply_styles"]);


        // disable storefront's default header elements since we're replacing them with a custom template part
        remove_action('storefront_header', 'storefront_site_branding', 20);
        remove_action('storefront_header', 'storefront_product_search', 40);
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
            }

            body {
                color: var(--theme-text);
            }

            a {
                color: var(--theme-link);
            }

            a:hover {
                color: var(--theme-link-hover) !important;
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
