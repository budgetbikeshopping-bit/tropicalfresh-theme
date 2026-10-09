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
			<p class="lead">Kort samengevat: <strong>Creamy Vanilla</strong> is warm, romig en huiselijk en past bij wie comfort zoekt. <strong>Juicy Green Apple</strong> is fris en energiek, alsof het raam net open stond. Beide zijn 60 ml sprays die tot twee dagen per spraybeurt meegaan; combineer ze en je krijgt een extra Juicy Green Apple gratis.</p>
		</div>

		<p style="font-weight:600; margin-bottom:16px;">Wat zoek je in je auto?</p>
		<input type="radio" name="gw" id="gw-warm">
		<input type="radio" name="gw" id="gw-fris">
		<div class="options">
			<label class="opt" for="gw-warm"><b>Warm &amp; zoet</b><span>Huiselijk en comfortabel, het hele jaar door.</span></label>
			<label class="opt" for="gw-fris"><b>Fris &amp; fruitig</b><span>Heldere energie, alsof het raam net open stond.</span></label>
		</div>
		<p style="margin-top: 14px; font-size: 13.5px; color: var(--ink-soft);">Zoek je die nieuwe-auto sensatie? <a href="<?php echo esc_url( home_url( '/product/shiny-new-car-autoparfum/' ) ); ?>" style="color: var(--chloro-deep); text-decoration: underline;">Shiny New Car komt terug</a>; laat daar je e-mailadres achter en je hoort het als eerste. Lees ondertussen <a href="<?php echo esc_url( home_url( '/autoparfum/nieuwe-auto-geur/' ) ); ?>" style="color: var(--chloro-deep); text-decoration: underline;">hoe je de nieuwe-autogeur terughaalt</a>.</p>
		<?php foreach ( $sim_products as $i => $p ) : ?>
		<div class="result <?php echo esc_attr( $sim_keys[ $i ] ?? '' ); ?>">
			<?php echo sim_product_visual( $p, 'woocommerce_thumbnail' ); // phpcs:ignore ?>
			<div>
				<span class="mono-label">Jouw match</span>
				<h3><?php echo esc_html( sim_short_name( $p ) ); ?></h3>
				<p><?php echo esc_html( sim_product_character( $p ) ); ?><br>
				<?php echo $p->is_in_stock() ? wp_kses_post( $p->get_price_html() . ' &middot; 60 ml' ) : 'Tijdelijk uitverkocht. Meld je aan op de productpagina, dan hoor je het als eerste.'; ?></p>
			</div>
			<a class="btn btn-primary" href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php echo $p->is_in_stock() ? 'Bekijk deze geur' : 'Bericht mij'; ?></a>
		</div>
		<?php endforeach; ?>
	</div>
</section>

<section class="light collection" style="padding-top: 0;">
	<div class="wrap">
		<div class="section-head">
			<span class="mono-label">Niet kiezen?</span>
			<h2>Dan gewoon allebei.</h2>
			<p class="lead">Combineer Creamy Vanilla met Juicy Green Apple en ontvang automatisch een extra Juicy Green Apple gratis. Twee karakters, drie flesjes.</p>
		</div>
		<div class="cards">
			<?php
			$sim_all = $sim_products;
			if ( $sim_duo ) {
				$sim_all[] = $sim_duo;
			}
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

<?php get_footer(); ?>
