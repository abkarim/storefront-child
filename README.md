# Storefront Child

This is a child theme for the Storefront WordPress theme.

Installation:

1. Copy the `storefront-child` folder into `wp-content/themes/`.
2. Activate the theme in the WordPress admin under Appearance → Themes.

What this initial scaffold includes:

- `style.css` — child theme header and base CSS.
- `functions.php` — enqueues parent and child styles, basic theme supports.
- `inc/customizations.php` — modular file for custom hooks and features.

Next steps:

- Add custom styles to `style.css` or add separate CSS files and enqueue them in `functions.php`.
- Describe which features you'd like (shortcodes, widgets, template overrides) and I'll implement them.

## Features 
### Product
- added option to show discount percentage instead of on sale (accessible from theme customize )
- layout editor (accessible from theme customize )
<img width="1358" height="631" alt="image" src="https://github.com/user-attachments/assets/9a09717f-c5d1-438f-b7e8-4cfb290af0a9" />

- Hided view cart button after adding to cart in button

### Compare page
<img width="1357" height="676" alt="image" src="https://github.com/user-attachments/assets/9d1fe7bc-24f0-4f92-8378-3da641ba04a6" />

#### Create a page name Compare

#### Add shortcode [woocommerce_sf_child_compare_products]

### File type 
- added svg upload

### Styles
- added branding colors in site identity editor section, also this is used in color pallette to pick options from 

## Header 
- Custom header 
- Styling options added

## Footer 
- removed the default storefront link with credits
- adjusted spacings
