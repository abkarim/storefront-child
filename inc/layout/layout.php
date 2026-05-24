<?php

namespace StoreFront_Child;

class Layout
{
    public function __construct()
    {
        add_action('init', [$this, "reposition_storefront_page_title"]);
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
}
