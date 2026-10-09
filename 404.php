<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="light page-wrap" style="min-height: 55vh;">
	<div class="wrap narrow" style="text-align: center; padding-top: 6vh;">
		<span class="mono-label">Foutcode 404</span>
		<h1 style="margin: 18px 0 14px;">Deze pagina is vervlogen.</h1>
		<p class="lead" style="margin: 0 auto 34px;">Het adres bestaat niet (meer). Geen zorgen, de geuren staan gewoon klaar.</p>
		<div class="cta-row" style="justify-content: center;">
			<a class="btn btn-primary" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">Naar de shop</a>
			<a class="btn btn-outline" href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">Vind jouw geur</a>
		</div>
	</div>
	<?php
	// Herstel in een tap: toon de leverbare geuren direct op de 404.
	if ( function_exists( 'wc_get_products' ) ) :
		$sim_404_prod = wc_get_products( array( 'status' => 'publish', 'stock_status' => 'instock', 'limit' => 3, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
		if ( $sim_404_prod ) : ?>
	<div class="wrap" style="margin-top: 56px; text-align: left;">
		<div class="cards">
			<?php
			foreach ( $sim_404_prod as $sim_404_p ) {
				$post_object = get_post( $sim_404_p->get_id() );
				setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore
				wc_get_template_part( 'content', 'product' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
	<?php endif; endif; ?>
</section>
<?php get_footer(); ?>
