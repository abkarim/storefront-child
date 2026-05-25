<?php

namespace StoreFront_Child;

class Customization
{
    public function __construct()
    {
        add_action('customize_register', [$this, 'customize_archive_products']);
        add_action('customize_controls_print_footer_scripts', [$this, 'enqueue_customizer_section_redirect_script']);
        add_filter('woocommerce_sale_flash', [$this, 'sf_child_replace_sale_with_percentage'], 10, 3);
    }

    /**
     * Adds custom color controls for product cards on archive pages in the WordPress Customize.
     * 
     * @since 1.0.0
     */
    public function customize_archive_products(\WP_Customize_Manager $wp_customize)
    {
        $wp_customize->add_section('sf_child_archive_card_section', [
            'title'      => __('Product Archive Cards', 'storefront-child'),
            'priority'   => 80, // Places it cleanly near Storefront's native styling controls
        ]);

        $wp_customize->add_setting('sf_child_enable_discount_percentage', [
            'default'           => true, // Enabled by default
            'sanitize_callback' => 'wp_validate_boolean', // Safe core boolean sanitization
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control('sf_child_enable_discount_percentage_control', [
            'label'    => __('Display Discount Percentage instead of "Sale!"', 'storefront-child'),
            'section'  => 'sf_child_archive_card_section',
            'settings' => 'sf_child_enable_discount_percentage',
            'type'     => 'checkbox',
        ]);

        $wp_customize->add_setting('sf_child_card_bg_color', [
            'default'           => '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color', // Core WP sanitization for safety
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, 'sf_child_card_bg_color_control', [
            'label'    => __('Product Card Background', 'storefront-child'),
            'section'  => 'sf_child_archive_card_section',
            'settings' => 'sf_child_card_bg_color',
        ]));

        $wp_customize->add_setting('sf_child_card_text_color', [
            'default'           => '#2d2d2d',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, 'sf_child_card_text_color_control', [
            'label'    => __('Product Card Text Color', 'storefront-child'),
            'section'  => 'sf_child_archive_card_section',
            'settings' => 'sf_child_card_text_color',
        ]));

        // Add cart button color control
        $wp_customize->add_setting('sf_child_card_button_bg_color', [
            'default'           => '#0073aa',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, 'sf_child_card_button_bg_color_control', [
            'label'    => __('Product Card Button Background', 'storefront-child'),
            'section'  => 'sf_child_archive_card_section',
            'settings' => 'sf_child_card_button_bg_color',
        ]));
        $wp_customize->add_setting('sf_child_card_button_text_color', [
            'default'           => '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, 'sf_child_card_button_text_color_control', [
            'label'    => __('Product Card Button Text Color', 'storefront-child'),
            'section'  => 'sf_child_archive_card_section',
            'settings' => 'sf_child_card_button_text_color',
        ]));

        // Border radius 
        $wp_customize->add_setting('sf_child_card_border_radius', [
            'default'           => '0.5em',
            'sanitize_callback' => 'sanitize_text_field', // Simple sanitization for text input
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control('sf_child_card_border_radius_control', [
            'label'    => __('Product Card Border Radius', 'storefront-child'),
            'section'  => 'sf_child_archive_card_section',
            'settings' => 'sf_child_card_border_radius',
            'type'     => 'text',
        ]);
    }

    /**
     * Injects a footer script to auto-redirect the live preview window to the shop page 
     * when the 'Product Archive Cards' section expands.
     */
    public function enqueue_customizer_section_redirect_script()
    {
        // Fetch the standard WooCommerce shop page URL dynamically
        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');

        if (! $shop_url) {
            return;
        }
?>
        <script type="text/javascript">
            (function($) {
                $(document).ready(function() {
                    // Ensure the customizer API is fully ready and loaded
                    if (typeof wp !== 'undefined' && typeof wp.customize !== 'undefined') {

                        // Listen to state changes on our custom card section
                        wp.customize.section('sf_child_archive_card_section', function(section) {
                            section.expanded.bind(function(isExpanded) {

                                // If the user clicks and expands our card styling section
                                if (isExpanded) {
                                    var currentPreviewUrl = wp.customize.previewer.previewUrl();
                                    var targetShopUrl = <?php echo wp_json_encode(esc_url_raw($shop_url)); ?>;

                                    // Strip trailing slashes or URL parameters for clean matching validation
                                    var cleanCurrent = currentPreviewUrl.replace(/\/$/, "");
                                    var cleanTarget = targetShopUrl.replace(/\/$/, "");

                                    // If the frame isn't already focused on the shop or archive loop, force load it!
                                    if (cleanCurrent !== cleanTarget && !currentPreviewUrl.includes('post_type=product')) {
                                        wp.customize.previewer.previewUrl(targetShopUrl);
                                    }
                                }
                            });
                        });

                    }
                });
            })(jQuery);
        </script>
<?php
    }

    /**
     * Calculate and display the exact discount percentage badge for sale items (Tax-Aware)
     */
    public function sf_child_replace_sale_with_percentage($html, $post, $product)
    {

        // Check if the user turned ON the discount percentage toggle layout option
        $show_percentage = get_theme_mod('sf_child_enable_discount_percentage', true);

        // If the checkbox is unchecked in the customizer, bail early and return standard text
        if (! $show_percentage) {
            return $html;
        }

        // 1. Handle Variable Products
        if ($product->is_type('variable')) {
            $percentages = [];
            $prices = $product->get_variation_prices();

            foreach ($prices['regular_price'] as $id => $regular_price) {
                $sale_price = $prices['sale_price'][$id];

                if ($regular_price > 0 && $sale_price < $regular_price) {
                    $percentages[] = round((($regular_price - $sale_price) / $regular_price) * 100);
                }
            }

            if (! empty($percentages)) {
                $percentage = max($percentages);
                return '<span class="onsale">-' . $percentage . '%</span>';
            }
        }
        // 2. Handle Simple, External, and Bookable Products
        else {
            $regular_price = (float) $product->get_regular_price();
            $sale_price    = (float) $product->get_sale_price();

            if ($regular_price > 0 && $sale_price > 0 && $sale_price < $regular_price) {
                $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
                return '<span class="onsale">-' . $percentage . '%</span>';
            }
        }

        return $html; // Fallback container return
    }
}
