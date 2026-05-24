<?php

namespace StoreFront_Child;

class Customization
{
    public function __construct()
    {
        add_action('customize_register', [$this, 'customize_archive_products']);
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
}
