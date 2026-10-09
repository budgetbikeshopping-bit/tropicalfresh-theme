<?php
/**
 * Shoppagina: SEO- en conversielandingspagina in plaats van een kale grid.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="light collection page-wrap">
	<div class="wrap">
		<div class="section-head">
			<span class="mono-label">Shop</span>
			<?php if ( is_shop() || ( is_product_taxonomy() && is_tax( 'product_cat', 'autoparfum' ) ) ) : ?>
				<h1>Onze geuren.</h1>
				<p class="lead">Twee verschillende karakters. Vind de geur die bij jouw rit past. Sprays van 60 ml, tot twee dagen geur per spraybeurt.</p>
			<?php else : ?>
				<h1><?php woocommerce_page_title(); ?></h1>
				<?php do_action( 'woocommerce_archive_description' ); ?>
			<?php endif; ?>
		</div>

		<?php woocommerce_output_all_notices(); ?>

		<?php if ( woocommerce_product_loop() ) : ?>
			<div class="cards">
				<?php
				while ( have_posts() ) :
					the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;
				?>
			</div>
			<?php do_action( 'woocommerce_after_shop_loop' ); ?>
		<?php else : ?>
			<p class="lead">Er zijn op dit moment geen producten beschikbaar.</p>
		<?php endif; ?>

		<?php if ( is_shop() || is_tax( 'product_cat', 'autoparfum' ) ) : ?>
		<div class="cat-tekst">
			<h2>Autoparfum kopen bij Tropical Fresh</h2>
			<p>Onze autoparfums zijn sprays van 60 ml: twee tot drie sprays in het interieur en de geur blijft tot twee dagen hangen. Geen hangertje aan je spiegel, geen vlekken; elke geur is getest op leer, stof en kunststof. Met normaal gebruik gaat een flesje ongeveer twee maanden mee, en jij bepaalt zelf de intensiteit door vaker of minder vaak te sprayen.</p>
			<p>De collectie is bewust klein: Creamy Vanilla voor warm en huiselijk en Juicy Green Apple voor fris en energiek. Twijfel je? De <a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">geurwijzer</a> helpt je in &eacute;&eacute;n vraag kiezen. Vanaf &euro; 17,90 is de verzending gratis en je hebt altijd 14 dagen retourrecht. Tropical Fresh is een Nederlands merk uit Leiden.</p>
		</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
