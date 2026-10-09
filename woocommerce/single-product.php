<?php
/**
 * Productpagina: eigen opmaak, met de originele WooCommerce-koopflow
 * (add-to-cart, notices en structured data via de standaard hooks).
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	global $product;

	$sim_tints = array(
		'creamy-vanilla-autoparfum'    => 'rgba(230,217,189,0.15)',
		'juicy-green-apple-autoparfum' => 'rgba(146,196,138,0.11)',
		'shiny-new-car-autoparfum'     => 'rgba(148,168,184,0.11)',
		'autoparfum-valuepack'         => 'rgba(163,230,53,0.06)',
	);
	$sim_tint   = $sim_tints[ $product->get_slug() ] ?? 'rgba(230,217,189,0.12)';
	$sim_short  = sim_short_name( $product );
	$sim_inhoud_map = array(
		'autoparfum-valuepack' => '3 &times; 60 ml',
		'tropical-fresh-duo'   => '3 &times; 60 ml',
	);
	$sim_is_set = isset( $sim_inhoud_map[ $product->get_slug() ] );
	$sim_inhoud = $sim_inhoud_map[ $product->get_slug() ] ?? '60 ml';
	$sim_is_duo = 'tropical-fresh-duo' === $product->get_slug();
	$sim_in_promo = function_exists( 'sim_promo_active' ) && sim_promo_active()
		&& in_array( $product->get_id(), array_values( sim_promo_ids() ), true );
	?>

	<?php do_action( 'woocommerce_before_single_product' ); // notices ?>

	<nav class="crumbs wrap" aria-label="Kruimelpad">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &nbsp;/&nbsp;
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Autoparfum</a> &nbsp;/&nbsp;
		<?php echo esc_html( $sim_short ); ?>
	</nav>

	<section class="pdp wrap">
		<div class="stage" data-tilt="9" style="--tint: <?php echo esc_attr( $sim_tint ); ?>">
			<div class="backplate"></div>
			<div class="gridlines"></div>
			<div class="tilt"><div class="float"><?php echo sim_product_visual( $product, 'woocommerce_single', true ); // phpcs:ignore ?></div></div>
			<div class="shadow"></div>
			<span class="spec">TF&nbsp;/&nbsp;<?php echo esc_html( $product->get_sku() ?: '60 ML' ); ?></span>
		</div>

		<div class="pdp-info">
			<span class="mono-label">
				Autoparfum &middot; <?php echo $sim_is_set ? $sim_inhoud : '60 ml spray'; // phpcs:ignore ?> &middot;
				<?php echo $product->is_in_stock() ? 'Op voorraad' : 'Tijdelijk uitverkocht'; ?>
			</span>
			<h1><?php echo esc_html( $sim_short ); ?></h1>
			<?php if ( $sim_in_promo ) : ?>
				<span class="promo-chip">Onderdeel van de 2+1 actie</span>
			<?php endif; ?>
			<div class="price-row">
				<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				<span class="price-note">incl. btw</span>
			</div>
			<?php if ( $sim_is_duo ) : ?>
				<p class="duo-uitleg">Je betaalt twee flesjes: Creamy Vanilla en Juicy Green Apple. De derde, een extra Juicy Green Apple, gaat automatisch gratis mee in je winkelwagen.</p>
			<?php endif; ?>
			<?php if ( $product->get_short_description() ) : ?>
				<div class="lead"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
			<?php endif; ?>

			<div class="spec-strip">
				<div class="cell"><span>Inhoud</span><b><?php echo $sim_inhoud; // phpcs:ignore ?></b></div>
				<div class="cell"><span>Werking</span><b>Tot 2 dagen per spraybeurt</b></div>
				<div class="cell"><span>Geschikt voor</span><b>Leer, stof, kunststof</b></div>
			</div>

			<div class="buy-block">
				<?php
				// Duo = vaste combi-set: geen losse aantallen per geur. We laten de
				// structured data staan maar vervangen het grouped-formulier door
				// een set-overzicht met een knop die de complete set toevoegt.
				if ( $sim_is_duo ) {
					remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
				}
				do_action( 'woocommerce_single_product_summary' ); // add-to-cart + structured data
				if ( $sim_is_duo ) {
					add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
				}
				?>
				<?php if ( $sim_is_duo && $product->is_in_stock() ) : ?>
					<div class="duo-set">
						<div class="set-rows">
							<span>01 &middot; Creamy Vanilla &middot; 60 ml</span>
							<span>02 &middot; Juicy Green Apple &middot; 60 ml</span>
							<span class="vrij">03 &middot; Juicy Green Apple &middot; 60 ml &middot; gratis</span>
						</div>
						<a class="btn btn-primary duo-add" rel="nofollow" href="<?php echo esc_url( home_url( '/?sim_duo=1' ) ); ?>">Voeg de complete set toe</a>
						<p class="duo-set-note">Vaste combi-set. Het gratis flesje verschijnt automatisch in je winkelwagen.</p>
					</div>
				<?php endif; ?>
				<?php if ( ! $product->is_in_stock() ) : ?>
					<p class="oos-note">Laat je e-mailadres achter en we mailen je zodra deze geur er weer is.</p>
					<?php echo sim_email_form( 'sim_stock', 'Bericht mij', $product->get_name() ); // phpcs:ignore ?>
					<?php
					// Bied direct een leverbaar alternatief aan in plaats van een dood spoor.
					foreach ( wc_get_related_products( $product->get_id(), 4 ) as $sim_alt_id ) {
						$sim_alt = wc_get_product( $sim_alt_id );
						if ( $sim_alt && $sim_alt->is_in_stock() ) {
							printf(
								'<p class="oos-alt">Nu wel leverbaar: <a href="%s">%s</a></p>',
								esc_url( $sim_alt->get_permalink() ),
								esc_html( sim_short_name( $sim_alt ) )
							);
							break;
						}
					}
					?>
					<?php if ( 'shiny-new-car-autoparfum' === $product->get_slug() ) : ?>
						<p class="oos-alt">Lees ondertussen <a href="<?php echo esc_url( home_url( '/autoparfum/nieuwe-auto-geur/' ) ); ?>">waar de nieuwe-autogeur vandaan komt en hoe je hem terughaalt</a>.</p>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( $sim_in_promo && $product->is_in_stock() ) : ?>
					<p class="promo-koop">
						<?php if ( 'juicy-green-apple-autoparfum' === $product->get_slug() ) : ?>
							2+1 actie: combineer met Creamy Vanilla en ontvang een extra Juicy Green Apple gratis.
						<?php else : ?>
							2+1 actie: combineer met Juicy Green Apple en ontvang er nog een Juicy Green Apple gratis bij.
						<?php endif; ?>
						<a href="<?php echo esc_url( sim_duo_url() ); ?>">Bekijk de 2+1 set</a>
					</p>
				<?php endif; ?>
			</div>
			<p class="ship-note">Bestel voor <b>&euro; 17,90</b> of meer en de verzending is gratis; daaronder is de verzending &euro; 4,95.</p>

			<div class="pdp-accs">
				<details class="acc" open>
					<summary>Beschrijving</summary>
					<div class="body"><?php the_content(); ?></div>
				</details>
				<details class="acc">
					<summary>Details</summary>
					<div class="body">
						<?php if ( $product->get_sku() ) : ?><p>Artikelnummer: <?php echo esc_html( $product->get_sku() ); ?></p><?php endif; ?>
						<?php wc_display_product_attributes( $product ); ?>
						<p>Merk: Tropical Fresh &middot; Inhoud: <?php echo $sim_inhoud; // phpcs:ignore ?></p>
					</div>
				</details>
				<details class="acc">
					<summary>Verzending &amp; retour</summary>
					<div class="body">Snelle levering door heel Nederland. Gratis verzending vanaf &euro; 17,90, daaronder &euro; 4,95. Niet tevreden? Je hebt 14 dagen retourrecht op ongebruikte producten in originele, verzegelde verpakking. Aanmelden kan via de retourpagina.</div>
				</details>
				<?php if ( comments_open() || $product->get_review_count() > 0 ) : ?>
				<details class="acc" id="reviews">
					<summary>Reviews (<?php echo esc_html( $product->get_review_count() ); ?>)</summary>
					<div class="body sim-reviews"><?php comments_template(); ?></div>
				</details>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( $product->is_in_stock() && ( $sim_is_duo || $product->is_purchasable() ) ) : ?>
	<!-- mobiele sticky-koopbalk: verschijnt zodra het koopblok uit beeld scrolt -->
	<div class="stickybuy">
		<div><span class="t"><?php echo esc_html( $sim_short ); ?></span><span class="p price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span></div>
		<a class="btn btn-primary" rel="nofollow" href="<?php echo esc_url( $sim_is_duo ? home_url( '/?sim_duo=1' ) : $product->add_to_cart_url() ); ?>">Toevoegen</a>
	</div>
	<?php endif; ?>

	<?php
	// Campagne-/sfeerbeelden per geur (pack 8 sep, allemaal 60 ml). Het primaire
	// productshot blijft de originele cutout; dit is aanvullend beeld.
	$sim_gal = array(
		'creamy-vanilla-autoparfum'    => array( 'hero' => '01', 'grid' => array( '02', '03', '04', '05' ) ),
		'juicy-green-apple-autoparfum' => array( 'hero' => '06', 'grid' => array( '07', '09' ), 'actie' => '08' ),
		'tropical-fresh-duo'           => array( 'hero' => '08', 'grid' => array( '09', '07' ) ),
	);
	$sim_g = $sim_gal[ $product->get_slug() ] ?? null;
	if ( $sim_g && function_exists( 'sim_pack_img' ) ) : ?>
	<section class="pdp-galerij">
		<div class="wrap">
			<div class="section-head">
				<span class="mono-label">In beeld</span>
				<h2>Van dichtbij.</h2>
			</div>
			<div class="gal-hero"><?php echo sim_pack_img( $sim_g['hero'], '92vw' ); // phpcs:ignore ?></div>
			<?php if ( ! empty( $sim_g['grid'] ) ) : ?>
			<div class="gal-grid<?php echo count( $sim_g['grid'] ) < 3 ? ' is-2' : ''; ?>">
				<?php foreach ( $sim_g['grid'] as $sim_g_key ) : ?>
					<figure><?php echo sim_pack_img( $sim_g_key, '(max-width: 767px) 46vw, 23vw' ); // phpcs:ignore ?></figure>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<?php if ( ! empty( $sim_g['actie'] ) && function_exists( 'sim_promo_active' ) && sim_promo_active() ) : ?>
			<div class="gal-actie">
				<?php echo sim_pack_img( $sim_g['actie'], '92vw' ); // phpcs:ignore ?>
				<a class="btn btn-primary" href="<?php echo esc_url( sim_duo_url() ); ?>">Bekijk de 2+1 set</a>
			</div>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="others">
		<div class="wrap">
			<div class="section-head">
				<span class="mono-label">Andere geuren</span>
				<h2>Wissel eens af.</h2>
			</div>
			<?php
			$sim_related = wc_get_related_products( $product->get_id(), 3 );
			if ( $sim_related ) : ?>
				<div class="cards">
					<?php
					foreach ( $sim_related as $sim_rel_id ) :
						$post_object = get_post( $sim_rel_id );
						setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore
						wc_get_template_part( 'content', 'product' );
					endforeach;
					wp_reset_postdata();
					?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php
endwhile;

get_footer();
