<?php
/**
 * Productkaart in shop- en related-loops.
 */
defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$sim_class_map = array(
	'creamy-vanilla-autoparfum'    => 'is-vanilla',
	'juicy-green-apple-autoparfum' => 'is-apple',
	'shiny-new-car-autoparfum'     => 'is-newcar',
);
?>
<article class="card <?php echo esc_attr( $sim_class_map[ $product->get_slug() ] ?? '' ); ?>">
	<a class="cover" href="<?php echo esc_url( $product->get_permalink() ); ?>" aria-label="Bekijk <?php echo esc_attr( $product->get_name() ); ?>"></a>
	<div class="code">
		<span><?php echo esc_html( $product->get_sku() ?: 'AUTOPARFUM' ); ?></span>
		<?php if ( $product->is_in_stock() ) : ?>
			<span class="stock-ok">Op voorraad</span>
		<?php else : ?>
			<span class="stock-no">Tijdelijk uitverkocht</span>
		<?php endif; ?>
	</div>
	<div class="photo"><?php echo sim_product_visual( $product, 'woocommerce_thumbnail' ); // phpcs:ignore ?></div>
	<h3><?php echo esc_html( sim_short_name( $product ) ); ?></h3>
	<p class="character"><?php echo esc_html( sim_product_character( $product ) ); ?></p>
	<div class="foot">
		<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
		<?php if ( $product->is_in_stock() && $product->is_type( 'simple' ) && $product->is_purchasable() ) : ?>
			<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1"
				class="btn btn-primary add_to_cart_button ajax_add_to_cart"
				data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" rel="nofollow">Voeg toe</a>
		<?php else : ?>
			<a class="btn btn-outline" href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo $product->is_in_stock() ? 'Bekijk' : 'Bericht mij'; ?></a>
		<?php endif; ?>
	</div>
</article>
