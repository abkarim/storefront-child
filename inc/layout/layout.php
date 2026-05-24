<?php

namespace StoreFront_Child;

class Layout
{
    public function __construct()
    {
        add_action('init', [$this, "reposition_storefront_page_title"]);

        add_action("wp_head", [$this, "product_archive_dynamic_card_styling"]);
    }

    public function reposition_storefront_page_title()
    {
        // Remove the title header from its default narrow position
        remove_action('storefront_page', 'storefront_page_header', 10);

        // Open Storefront's native structural layout wrapper BEFORE the title renders
        add_action('storefront_before_content', [$this, 'custom_open_title_wrapper'], 4);

        // Render the standard title at priority 5 (before breadcrumbs at 10)
        add_action('storefront_before_content', 'storefront_page_header', 5);

        // Close the layout wrapper immediately after the title renders
        add_action('storefront_before_content', [$this, 'custom_close_title_wrapper'], 6);
    }

    /**
     * Opens the custom title wrapper
     * it helps to maintain the same layout as the rest of the page, while allowing the title to be styled differently.
     * 
     * @since 1.0.0
     */
    public function custom_open_title_wrapper()
    {
        echo '<div class="custom-title-banner"><div class="col-full">';
    }

    /**
     * Closes the custom title wrapper
     * it helps to maintain the same layout as the rest of the page, while allowing the title to be styled differently.
     * 
     * @since 1.0.0
     */
    public function custom_close_title_wrapper()
    {
        echo '</div></div>';
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
