<?php

namespace StoreFront_Child;

class Customization
{
    public function __construct()
    {
        add_action('customize_register', [$this, 'customize_archive_products']);
        add_action("customize_register", [$this, "customize_store_front_section"]);

        add_action('customize_controls_print_footer_scripts', [$this, 'inject_global_iris_palette']);
        add_action('after_setup_theme', [$this, 'sf_child_register_editor_color_palette'], 11);

        add_action('customize_controls_print_footer_scripts', [$this, 'enqueue_customizer_section_redirect_script']);
        add_filter('woocommerce_sale_flash', [$this, 'sf_child_replace_sale_with_percentage'], 10, 3);
        add_filter('woocommerce_sale_badge_text', [$this, 'custom_sale_badge_text'], 10, 2);

        add_action('widgets_init', [$this, 'sf_register_product_delivery_widget_zone']);

        // Add header image section in category for woocommerce
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('product_cat_add_form_fields', [$this, 'add_category_header_image_field'], 20, 2);
        add_action('product_cat_edit_form_fields', [$this, 'edit_category_header_image_field'], 20, 2);
        add_action('created_product_cat', [$this, 'save_product_cat_header_image']);
        add_action('edited_product_cat', [$this, 'save_product_cat_header_image']);
    }

    function save_product_cat_header_image($term_id)
    {
        if (isset($_POST['header-image'])) {
            update_term_meta($term_id, 'header-image', sanitize_text_field($_POST['header-image']));
        }
    }

    public function add_category_header_image_field()
    {
?>
        <div class="form-field term-header-image-wrap">
            <label for="header-image-id"><?php _e('Header Image', 'storefront-child'); ?></label>
            <input type="hidden" id="header-image-id" name="header-image" value="">
            <div id="header-image-wrapper" style="margin-bottom: 10px;">
                <img id="header-image-preview" src="" style="max-width: 150px; display: none;" />
            </div>
            <p>
                <button type="button" class="button upload-header-image-button"><?php _e('Header Image', 'storefront-child'); ?></button>
                <button type="button" class="button remove-header-image-button" style="display:none;"><?php _e('Remove Image', 'storefront-child'); ?></button>
            </p>
        </div>
    <?php
    }

    public function edit_category_header_image_field($term)
    {
        $image_id = get_term_meta($term->term_id, 'header-image', true);
        $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
    ?>
        <tr class="form-field term-header-image-wrap">
            <th scope="row"><label for="header-image-id"><?php _e('Header Image', 'storefront-child'); ?></label></th>
            <td>
                <input type="hidden" id="header-image-id" name="header-image" value="<?php echo esc_attr($image_id); ?>">
                <div id="header-image-wrapper" style="margin-bottom: 10px;">
                    <img id="header-image-preview" src="<?php echo esc_url($image_url); ?>" style="max-width: 150px; display: <?php echo $image_url ? 'block' : 'none'; ?>;" />
                </div>
                <p>
                    <button type="button" class="button upload-header-image-button"><?php _e('Header Image', 'storefront-child'); ?></button>
                    <button type="button" class="button remove-header-image-button" style="<?php echo $image_url ? '' : 'display:none;'; ?>"><?php _e('Remove Image', 'storefront-child'); ?></button>
                </p>
            </td>
        </tr>
        <?php
    }

    public function enqueue_admin_scripts($hook)
    {
        if ('edit-tags.php' === $hook || 'term.php' === $hook) {
            $screen = get_current_screen();
            if ($screen && 'product_cat' === $screen->taxonomy) {
                // Force-load native WordPress Media Library assets
                wp_enqueue_media();

                // Inject script directly into footer so jQuery and wp.media are fully loaded
                add_action('admin_footer', function () {
        ?>
                    <script type="text/javascript">
                        jQuery(document).ready(function($) {
                            var frame;

                            $(document).on('click', '.upload-header-image-button', function(e) {
                                e.preventDefault();

                                // Re-use frame if already created
                                if (frame) {
                                    frame.open();
                                    return;
                                }

                                // Create the media frame
                                frame = wp.media({
                                    title: 'Select or Upload Header Image',
                                    button: {
                                        text: 'Use this image'
                                    },
                                    multiple: false
                                });

                                // When an image is selected, run callback
                                frame.on('select', function() {
                                    var attachment = frame.state().get('selection').first().toJSON();
                                    $('#header-image-id').val(attachment.id);
                                    $('#header-image-preview').attr('src', attachment.url).show();
                                    $('.remove-header-image-button').show();
                                });

                                frame.open();
                            });

                            $(document).on('click', '.remove-header-image-button', function(e) {
                                e.preventDefault();
                                $('#header-image-id').val('');
                                $('#header-image-preview').attr('src', '').hide();
                                $(this).hide();
                            });
                        });
                    </script>
        <?php
                });
            }
        }
    }

    public function sf_register_product_delivery_widget_zone()
    {
        register_sidebar(array(
            'name'          => __('Product Delivery Details Widget', 'storefront-child'),
            'id'            => 'sf-child-product-delivery-widget',
            'description'   => __('Widgets added here will render inside the single product summary page slot.', 'storefront-child'),
            'before_widget' => '<div id="%1$s" class="widget %2$s sf-custom-product-delivery-details">',
            'after_widget'  => '</div>',
            'before_title'  => '<p class="title">',
            'after_title'   => '</p>',
        ));
    }

    /**
     * Register dynamic theme mods inside the block editor configuration setup
     * 
     * @since 1.0.4
     */
    public function sf_child_register_editor_color_palette()
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

        add_theme_support('editor-color-palette', [
            [
                'name'  => __('Theme Text', 'storefront-child'),
                'slug'  => 'theme-text',
                'color' => $text,
            ],
            [
                'name'  => __('Theme Text Accent', 'storefront-child'),
                'slug'  => 'theme-text-accent',
                'color' => $text_accent,
            ],
            [
                'name'  => __('Theme Link', 'storefront-child'),
                'slug'  => 'theme-link',
                'color' => $link,
            ],
            [
                'name'  => __('Theme Link Hover', 'storefront-child'),
                'slug'  => 'theme-link-hover',
                'color' => $link_hover,
            ],
            [
                'name'  => __('Theme Background', 'storefront-child'),
                'slug'  => 'theme-bg',
                'color' => $bg,
            ],
            [
                'name'  => __('Theme Background Accent', 'storefront-child'),
                'slug'  => 'theme-bg-accent',
                'color' => $bg_accent,
            ],
            [
                'name'  => __('Theme Border', 'storefront-child'),
                'slug'  => 'theme-border',
                'color' => $border,
            ],
            [
                'name'  => __('Theme Button Text', 'storefront-child'),
                'slug'  => 'theme-button-text',
                'color' => $button_text,
            ],
            [
                'name'  => __('Theme Button Background', 'storefront-child'),
                'slug'  => 'theme-button-bg',
                'color' => $button_bg,
            ],
            [
                'name'  => __('Theme Button Hover Text', 'storefront-child'),
                'slug'  => 'theme-button-hover-text',
                'color' => $button_hover_text,
            ],
            [
                'name'  => __('Theme Button Hover Background', 'storefront-child'),
                'slug'  => 'theme-button-hover-bg',
                'color' => $button_hover_bg,
            ],
        ]);
    }

    /**
     * Overrides the default core Iris Color Picker swatch configurations
     * 
     * @since 1.0.4
     */
    public function inject_global_iris_palette()
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

        $palette_json = [$text, $text_accent, $link, $link_hover, $bg, $bg_accent, $border, $button_text, $button_bg, $button_hover_text, $button_hover_bg];
        ?>
        <script type="text/javascript" id="sf-child-global-iris-override">
            jQuery(document).ready(function($) {
                $('.wp-picker-container').iris({
                    mode: 'hsl',
                    controls: {
                        horiz: 'h', // square horizontal displays hue
                        vert: 's', // square vertical displays saturdation
                        strip: 'l' // slider displays lightness
                    },
                    palettes: <?php echo wp_json_encode($palette_json); ?> // Inject our dynamic palette from PHP
                })
            });
        </script>
    <?php
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
     * Add customization controls in parent themes
     * 
     * @since 1.0.2
     */
    public function customize_store_front_section(\WP_Customize_Manager $wp_customize)
    {
        /**
         * Site identity 
         */
        // Configuration map processing array for batch loading
        $color_settings = [
            'sf_child_logo_text_color'      => [
                'label'   => __('Text Color', 'storefront-child'),
                'default' => '#333333',
                'priority' => 20
            ],
            'sf_child_logo_text_accent'     => [
                'label'   => __('Text Accent Color', 'storefront-child'),
                'default' => '#7eb934',
                'priority' => 21
            ],
            'sf_child_logo_link_color'      => [
                'label'   => __('Link Color', 'storefront-child'),
                'default' => '#0066cc',
                'priority' => 22
            ],
            'sf_child_logo_link_hover_color' => [
                'label'   => __('Link Hover Color', 'storefront-child'),
                'default' => '#ff5500',
                'priority' => 23
            ],
            'sf_child_logo_bg_color'        => [
                'label'   => __('Background Color', 'storefront-child'),
                'default' => '#ffffff',
                'priority' => 24
            ],
            'sf_child_logo_bg_accent_color' => [
                'label'   => __('Background Accent Color', 'storefront-child'),
                'default' => '#f9f9f9',
                'priority' => 25
            ],
            'sf_child_logo_border_color'    => [
                'label'   => __('Border Color', 'storefront-child'),
                'default' => '#e5e5e5',
                'priority' => 26
            ],
            'sf_child_logo_button_text_color'    => [
                'label'   => __('Button text Color', 'storefront-child'),
                'default' => '#ffffff',
                'priority' => 27
            ],
            'sf_child_logo_button_bg_color'    => [
                'label'   => __('Button Background Color', 'storefront-child'),
                'default' => '#0f0f0f',
                'priority' => 28
            ],
            'sf_child_logo_button_hover_text_color'    => [
                'label'   => __('Button hover text Color', 'storefront-child'),
                'default' => '#e5e5e5',
                'priority' => 27
            ],
            'sf_child_logo_button_hover_bg_color'    => [
                'label'   => __('Button hover Background Color', 'storefront-child'),
                'default' => '#0f0f0f',
                'priority' => 28
            ],

        ];

        // Loop through our structural data matrix to instantiate customizer controls efficiently
        foreach ($color_settings as $id => $props) {
            $wp_customize->add_setting($id, [
                'default'           => $props['default'],
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            ]);

            $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, $id . '_ctrl', [
                'label'    => $props['label'],
                'section'  => 'title_tagline',
                'settings' => $id,
                'priority' => $props['priority'],
            ]));
        }


        /**
         * Header 
         */
        // Border color control for header bottom border
        $wp_customize->add_setting('sf_child_header_border_color', [
            'default'           => '#e5e5e5',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, 'sf_child_header_border_color_control', [
            'label'    => __('Border Color', 'storefront-child'),
            'section'  => 'header_image', // <-- Targets Storefront's native Header section
            'settings' => 'sf_child_header_border_color',
            'priority' => 35,             // Places it cleanly right below the background color control
        ]));

        // Badge bg and text color controls for the header utility icons
        $wp_customize->add_setting('sf_child_header_utility_badge_bg_color', [
            'default'           => '#7eb934',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, 'sf_child_header_utility_badge_bg_color_control', [
            'label'    => __('Header Utility Badge Background', 'storefront-child'),
            'section'  => 'header_image',
            'settings' => 'sf_child_header_utility_badge_bg_color',
            'priority' => 36,
        ]));
        $wp_customize->add_setting('sf_child_header_utility_badge_text_color', [
            'default'           => '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, 'sf_child_header_utility_badge_text_color_control', [
            'label'    => __('Header Utility Badge Text Color', 'storefront-child'),
            'section'  => 'header_image',
            'settings' => 'sf_child_header_utility_badge_text_color',
            'priority' => 37,
        ]));
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
     * 
     * @since 1.0.0
     */
    public function sf_child_replace_sale_with_percentage($html, $post, $product)
    {
        // Check if the user turned ON the discount percentage toggle layout option
        $show_percentage = get_theme_mod('sf_child_enable_discount_percentage', true);

        // If the checkbox is unchecked in the customizer, bail early and return standard text
        if (! $show_percentage) {
            return $html;
        }

        // Run the string replacement through our master logic rule above
        $new_text = $this->custom_sale_badge_text('', $product);

        if (! empty($new_text)) {
            // Keeps your exact markup styling wrapper but injects the percentage text
            return '<span class="onsale">' . esc_html($new_text) . '</span>';
        }

        return $html;
    }

    /**
     * Show custom sale badge text with calculated discount percentage for simple and variable products 
     * 
     * @since 1.0.4
     */
    function custom_sale_badge_text($sale_text, $product)
    {

        // Check if the user turned ON the discount percentage toggle layout option
        $show_percentage = get_theme_mod('sf_child_enable_discount_percentage', true);

        // If the checkbox is unchecked in the customizer, bail early and return standard text
        if (! $show_percentage) {
            return $sale_text;
        }

        // Safety check to ensure the product object exists
        if (! $product) {
            return $sale_text;
        }

        // 1. SIMPLE & EXTERNAL PRODUCTS
        if ($product->is_type('simple') || $product->is_type('external')) {
            $regular_price = (float) $product->get_regular_price();
            $sale_price    = (float) $product->get_sale_price();

            if ($regular_price > 0) {
                $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
                return sprintf(__('-%s%%', 'storefront-child'), $percentage);
            }
        }

        // 2. VARIABLE PRODUCTS
        if ($product->is_type('variable')) {
            $percentages = [];
            $prices = $product->get_variation_prices();

            // Ensure regular prices array exists and isn't empty
            if (! empty($prices['regular_price'])) {
                foreach ($prices['regular_price'] as $id => $regular_price) {
                    // Double check that this specific variation actually has a matching sale price set
                    if (isset($prices['sale_price'][$id])) {
                        $sale_price = $prices['sale_price'][$id];
                        $regular_price = (float) $regular_price;
                        $sale_price = (float) $sale_price;

                        if ($regular_price > 0 && $sale_price < $regular_price) {
                            $percentages[] = round((($regular_price - $sale_price) / $regular_price) * 100);
                        }
                    }
                }
            }

            if (! empty($percentages)) {
                $percentage = max($percentages); // Grab highest discount percentage variant
                return sprintf(__('-%s%%', 'storefront-child'), $percentage);
            }
        }

        return $sale_text;
    }
}
