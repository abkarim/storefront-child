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

### Order Tracking 
Track order via phone number 

#### Create a page name `Track Orders`
<img width="1294" height="540" alt="image" src="https://github.com/user-attachments/assets/f369a557-8c7d-4304-a8ba-4a17e888e370" />

#### Add shortcode [track_order_via_phone_sf_child]

### Product
- added option to show discount percentage instead of on sale (accessible from theme customize )
- layout editor (accessible from theme customize )
<img width="1358" height="631" alt="image" src="https://github.com/user-attachments/assets/9a09717f-c5d1-438f-b7e8-4cfb290af0a9" />

- Disabled view cart button after adding to cart via button

### Compare page
<img width="1357" height="676" alt="image" src="https://github.com/user-attachments/assets/9d1fe7bc-24f0-4f92-8378-3da641ba04a6" />

#### Create a page name Compare

#### Add shortcode [woocommerce_sf_child_compare_products]

### Categories 
add product categories list via wp block editor in page. Add `slide` class to animate it
<img width="1317" height="262" alt="image" src="https://github.com/user-attachments/assets/3a20549b-48bc-463e-8eee-cb15775d4e7a" />

### Category archive 
###### Shows category header image category page 

<img width="1300" height="475" alt="image" src="https://github.com/user-attachments/assets/5fdf6b3f-77f2-419a-9e72-2c604e240a38" />

###### Upload image to category image section \n

<img width="619" height="559" alt="image" src="https://github.com/user-attachments/assets/441c75b1-fa3e-49cf-b8e3-1f84de259adb" />




### File type 
- added svg upload

### Styles
- added branding colors in site identity editor section, also this is used in color pallette to pick options from 
- `breakout-col` add this class to make element full width

## Header 
- Custom header 
- Styling options added

## Footer 
- removed the default storefront link with credits
- adjusted spacings
