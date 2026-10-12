<?php
/**
 * Geurwijzer als eigen pagina (template-hierarchie: slug "geurwijzer").
 * Zelfde CSS-only keuzemechanisme als op de homepage, met wat meer context.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$sim_slugs    = array( 'creamy-vanilla-autoparfum', 'juicy-green-apple-autoparfum', 'shiny-new-car-autoparfum' );
$sim_products = array();
foreach ( $sim_slugs as $sim_slug ) {
	$post_obj = get_page_by_path( $sim_slug, OBJECT, 'product' );
	if ( $post_obj ) {
		$p = wc_get_product( $post_obj->ID );
		if ( $p && 'publish' === $p->get_status() && $p->is_in_stock() ) {
			$sim_products[] = $p;
		}
	}
}
$duo_post = get_page_by_path( 'tropical-fresh-duo', OBJECT, 'product' );
$sim_duo  = $duo_post ? wc_get_product( $duo_post->ID ) : null;
$sim_keys = array( 'r-warm', 'r-fris' );

// 2+1 alleen noemen zolang de actie loopt (beide geuren leverbaar).
$sim_promo = function_exists( 'sim_promo_active' ) && sim_promo_active();

// Shiny New Car alleen als koopadvies tonen wanneer hij echt leverbaar is.
$sim_snc_post = get_page_by_path( 'shiny-new-car-autoparfum', OBJECT, 'product' );
$sim_snc      = $sim_snc_post ? wc_get_product( $sim_snc_post->ID ) : null;
$sim_snc_ok   = $sim_snc && 'publish' === $sim_snc->get_status() && $sim_snc->is_in_stock();
?>

<section class="light wijzer page-wrap">
	<div class="wrap">
		<nav class="crumbs" aria-label="Kruimelpad" style="padding-bottom: 18px;">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &nbsp;/&nbsp; Geurwijzer
		</nav>
		<div class="section-head">
			<span class="mono-label">Geurwijzer</span>
			<h1>Welke autogeur past bij jou?</h1>
			<p class="lead">E&eacute;n vraag, direct antwoord. Kies wat je zoekt in je auto en we zetten de juiste geur voor je klaar.</p>
			<p class="lead">Kort samengevat: <strong>Creamy Vanilla</strong> is warm en zoet en past bij wie comfort zoekt. <strong>Juicy Green Apple</strong> is fris en fruitig. Beide zijn sprays van 60 ml met tot twee dagen geur per spraybeurt<?php echo $sim_promo ? '; neem je ze samen in de 2+1 set, dan krijg je er een extra Juicy Green Apple gratis bij' : ''; ?>.</p>
		</div>

		<p style="font-weight:600; margin-bottom:16px;">Wat zoek je in je auto?</p>
		<input type="radio" name="gw" id="gw-warm">
		<input type="radio" name="gw" id="gw-fris">
		<div class="options">
			<label class="opt" for="gw-warm"><b>Warm &amp; zoet</b><span>Huiselijk en comfortabel, het hele jaar door.</span></label>
			<label class="opt" for="gw-fris"><b>Fris &amp; fruitig</b><span>Heldere energie, alsof het raam net open stond.</span></label>
		</div>
		<p style="margin-top: 14px; font-size: 13.5px; color: var(--ink-soft);">Zoek je die nieuwe-autogeur?
		<?php if ( $sim_snc_ok ) : ?>
			<a href="<?php echo esc_url( $sim_snc->get_permalink() ); ?>" style="color: var(--chloro-deep); text-decoration: underline;">Bekijk Shiny New Car</a>, of lees <a href="<?php echo esc_url( home_url( '/autoparfum/nieuwe-auto-geur/' ) ); ?>" style="color: var(--chloro-deep); text-decoration: underline;">hoe je de nieuwe-autogeur terughaalt</a>.
		<?php else : ?>
			Shiny New Car is tijdelijk uitverkocht. Lees ondertussen <a href="<?php echo esc_url( home_url( '/autoparfum/nieuwe-auto-geur/' ) ); ?>" style="color: var(--chloro-deep); text-decoration: underline;">hoe je de nieuwe-autogeur terughaalt</a>.
		<?php endif; ?>
		</p>
		<?php foreach ( $sim_products as $i => $p ) : ?>
		<div class="result <?php echo esc_attr( $sim_keys[ $i ] ?? '' ); ?>">
			<?php echo sim_product_visual( $p, 'woocommerce_thumbnail' ); // phpcs:ignore ?>
			<div>
				<span class="mono-label">Jouw match</span>
				<h3><?php echo esc_html( sim_short_name( $p ) ); ?></h3>
				<p><?php echo esc_html( sim_product_character( $p ) ); ?><br>
				<?php echo $p->is_in_stock() ? wp_kses_post( wc_price( wc_get_price_to_display( $p ) ) . ' &middot; 60 ml' ) : 'Tijdelijk uitverkocht.'; ?></p>
			</div>
			<a class="btn btn-primary" href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php echo $p->is_in_stock() ? 'Bekijk deze geur' : 'Bericht mij'; ?></a>
		</div>
		<?php endforeach; ?>
	</div>
</section>

<?php
// Zonder 2+1 is er altijd een van beide geuren uitverkocht (zie sim_promo_active):
// dan geen "allebei", maar een neutrale kop boven de leverbare geuren.
$sim_all = $sim_products;
if ( $sim_duo && $sim_promo ) {
	$sim_all[] = $sim_duo;
}
?>
<?php if ( $sim_all ) : ?>
<section class="light collection" style="padding-top: 0;">
	<div class="wrap">
		<div class="section-head">
			<?php if ( $sim_promo ) : ?>
			<span class="mono-label">Niet kiezen?</span>
			<h2>Dan gewoon allebei.</h2>
			<p class="lead">Met de 2+1 set neem je Creamy Vanilla en Juicy Green Apple samen en krijg je automatisch een extra Juicy Green Apple gratis. Twee karakters, drie flesjes.</p>
			<?php else : ?>
			<span class="mono-label">De collectie</span>
			<h2>Kies je geur.</h2>
			<?php endif; ?>
		</div>
		<div class="cards">
			<?php
			foreach ( $sim_all as $p ) :
				$post_object = get_post( $p->get_id() );
				setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore
				wc_get_template_part( 'content', 'product' );
			endforeach;
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
