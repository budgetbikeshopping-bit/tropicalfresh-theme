<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-tf.svg' ); ?>" type="image/svg+xml">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="announce"><span>Gratis verzending vanaf <b>&euro;&nbsp;17,90</b></span><span class="a-extra"> &nbsp;&middot;&nbsp; Snelle levering in Nederland</span><span class="a-extra"> &nbsp;&middot;&nbsp; 14 dagen retourrecht</span></div>

<header class="site-head">
	<div class="wrap bar">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Tropical Fresh home">
			<?php echo sim_logo_svg(); // phpcs:ignore ?>
			<span class="name">TROPICAL<b>&#8202;FRESH</b></span>
		</a>
		<nav class="site-nav" aria-label="Hoofdmenu">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Geuren</a>
			<a href="<?php echo esc_url( home_url( '/autoparfum/' ) ); ?>">Autoparfum</a>
			<a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">Geurwijzer</a>
			<a href="<?php echo esc_url( home_url( '/over-ons/' ) ); ?>">Over ons</a>
		</nav>
		<div class="head-tools">
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="hide-m">Account</a>
			<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="cart-btn">Tas<span class="count sim-cart-count"><?php echo esc_html( WC()->cart ? WC()->cart->get_cart_contents_count() : 0 ); ?></span></a>
			<button class="menu-btn" type="button" aria-expanded="false" aria-controls="mobnav">Menu</button>
		</div>
	</div>
</header>

<nav class="mobnav" id="mobnav" aria-label="Mobiel menu" aria-hidden="true">
	<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Geuren</a>
	<a href="<?php echo esc_url( home_url( '/autoparfum/' ) ); ?>">Autoparfum</a>
	<a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">Geurwijzer</a>
	<a href="<?php echo esc_url( home_url( '/over-ons/' ) ); ?>">Over ons</a>
	<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Account</a>
</nav>

<main id="main">
