<?php

namespace StoreFront_Child;

class Layout
{
    public function __construct()
    {
        add_action('wp', [$this, "reposition_storefront_page_title"]);

        add_action("wp_head", [$this, "product_archive_dynamic_card_styling"]);
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
            ul.products li:not(.product-category).product .onsale {
                background-color: <?php echo esc_attr($bg_color); ?> !important;
                border-radius: <?php echo esc_attr($border_radius); ?> !important;
            }

            /* Apply custom text color parameters across core text element layers */
            .wc-block-grid__products .wc-block-grid__product .add_to_cart_button,
            ul.products li:not(.product-category).product .woocommerce-loop-product__title,
            ul.products li:not(.product-category).product .price,
            ul.products li:not(.product-category).product .onsale,
            ul.wc-block-grid__products li.wc-block-grid__product .wc-block-grid__product-title,
            ul.wc-block-grid__products li.wc-block-grid__product .price {
                color: <?php echo esc_attr($text_color); ?> !important;
            }

            .wc-block-grid__products .wc-block-grid__product .add_to_cart_button,
            ul.wc-block-grid__products li.wc-block-grid__product .wc-block-grid__product-add-to-cart,
            ul.products li:not(.product-category).product .add_to_cart_button {
                color: <?php echo esc_attr($button_text_color); ?> !important;
                background-color: <?php echo esc_attr($button_bg_color); ?> !important;
                border-radius: <?php echo esc_attr($border_radius); ?> !important;
            }
        </style>
<?php

    }
}
