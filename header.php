<?php

/**
 * The header for our theme (Overridden in Child Theme).
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package storefront-child
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<?php wp_body_open(); ?>

	<?php do_action('storefront_before_site'); ?>

	<div id="page" class="hfeed site">
		<?php do_action('storefront_before_header'); ?>

		<header id="masthead" class="site-header" role="banner" style="<?php storefront_header_styles(); ?>">
			<div class="col-full">
				<?php
				/**
				 * Instead of running the generic Storefront hook, we load your premium 
				 * multi-column template part here directly.
				 */
				if (locate_template('templates/header.php')) {
					get_template_part('templates/header');
				} else {
					// Safe structural fallback in case the template file is ever misplaced
					do_action('storefront_header');
				}
				?>
			</div>
		</header><?php
					/**
					 * @hooked storefront_header_widget_region - 10
					 * @hooked woocommerce_breadcrumb - 10
					 */
					do_action('storefront_before_content');
					?>

		<section id="sf-child-quick-view" class="hidden">
			<section class="content col-full">
				<header>
					<span class="close">
						&#10006;
					</span>
				</header>
				<div class="dynamic-content"></div>
			</section>
		</section>


		<div id="content" class="site-content" tabindex="-1">
			<div class="col-full">

				<?php
				do_action('storefront_content_top');
