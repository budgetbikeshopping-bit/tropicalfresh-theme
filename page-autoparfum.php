<?php
/**
 * SEO-pijlerpagina /autoparfum/ (template-hierarchie: slug "autoparfum").
 * Redactioneel, feitelijk, met interne links naar de geuren en de geurwijzer.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$sim_slugs    = array( 'creamy-vanilla-autoparfum', 'juicy-green-apple-autoparfum' );
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
?>
<section class="light page-wrap">
	<div class="wrap narrow">
		<nav class="crumbs" aria-label="Kruimelpad" style="padding-bottom: 18px;">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &nbsp;/&nbsp; Autoparfum
		</nav>
		<span class="mono-label">Autoparfum</span>
		<h1 style="margin: 16px 0 18px;">Autoparfum voor een frisse auto</h1>
		<div class="page-content">
			<p>Autoparfum is parfum dat gemaakt is voor het interieur van je auto: een geconcentreerde geurspray die je op matten, stoelen of kunststof spuit. Twee tot drie sprays zijn genoeg om de hele auto van geur te voorzien, en anders dan bij een geurhanger bepaal jij zelf hoe sterk het ruikt.</p>

			<h2>Spray of geurhanger?</h2>
			<p>Een geurhanger werkt passief: hij hangt aan je spiegel en geeft continu dezelfde geurintensiteit af tot hij op is. Een autoparfum-spray werkt actief. Je doseert zelf, de geur is er direct, en er bungelt niets in je zicht. Onze sprays zijn getest op leer, stof en kunststof en laten geen vlekken of resten achter.</p>

			<h2>Zo gebruik je autoparfum</h2>
			<ol>
				<li>Spray twee tot drie keer in het interieur, bijvoorbeeld op de matten of onder de stoel.</li>
				<li>Laat de geur zich verdelen terwijl je rijdt; de auto hoeft er niet voor dicht of open.</li>
				<li>Geniet tot twee dagen per spraybeurt. Wil je meer intensiteit, spray dan vaker; te sterk is met &eacute;&eacute;n spray minder zo opgelost.</li>
			</ol>
			<p>Met normaal gebruik gaat een flesje van 60 ml ongeveer twee maanden mee.</p>

			<h2>Welke geur kies je?</h2>
			<p>Onze collectie is bewust klein. <a href="<?php echo esc_url( home_url( '/product/creamy-vanilla-autoparfum/' ) ); ?>">Creamy Vanilla</a> is warm, romig en huiselijk, op zijn best op koude dagen en rustige ritten. <a href="<?php echo esc_url( home_url( '/product/juicy-green-apple-autoparfum/' ) ); ?>">Juicy Green Apple</a> is fris, helder en energiek, alsof het raam net open stond. Twijfel je? De <a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">geurwijzer</a> helpt je in &eacute;&eacute;n vraag kiezen, en met de <a href="<?php echo esc_url( home_url( '/product/tropical-fresh-duo/' ) ); ?>">Tropical Fresh Duo</a> haal je ze allebei in huis; je krijgt er dan automatisch een extra Juicy Green Apple bij.</p>

			<h2>Veelgestelde vragen over autoparfum</h2>
			<h3>Hoe lang blijft autoparfum ruiken?</h3>
			<p>Tot twee dagen per spraybeurt, afhankelijk van je auto, ventilatie en hoeveel je sprayt.</p>
			<h3>Is autoparfum veilig voor mijn interieur?</h3>
			<p>Onze sprays zijn getest op leer, stof en kunststof en zijn vlekvrij. Spray niet direct op schermen of glas; dat is niet nodig voor de werking.</p>
			<h3>Wat is het verschil met een luchtverfrisser?</h3>
			<p>Een luchtverfrisser maskeert vooral. Autoparfum is een geconcentreerde geurcompositie die je doseert als parfum, met een topnoot die je direct ruikt en een basis die blijft hangen.</p>
			<h3>Hoeveel kost autoparfum?</h3>
			<p>De actuele prijzen vind je bij <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">de geuren</a>; vanaf &euro; 17,90 is de verzending gratis.</p>

			<h2>Verder lezen</h2>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/vieze-geur-auto-verwijderen/' ) ); ?>">Vieze geur in je auto verwijderen: het stappenplan</a> &ndash; van rooklucht tot hondengeur, eerst de bron weg.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/beste-autoparfum-kiezen/' ) ); ?>">Beste autoparfum kiezen</a> &ndash; spray, geurhanger en ventilatieclip eerlijk vergeleken.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/nieuwe-auto-geur/' ) ); ?>">De nieuwe-autogeur terughalen</a> &ndash; waar die showroomlucht vandaan komt en hoe je hem terugbrengt.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-cadeau/' ) ); ?>">Autoparfum als cadeau</a> &ndash; voor wie het past en hoe je een geur kiest voor iemand anders.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/hondengeur-uit-de-auto/' ) ); ?>">Hondengeur uit de auto halen</a> &ndash; grondig schoonmaken, drogen en fris houden als je hond meerijdt.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/condens-vocht-auto/' ) ); ?>">Vocht en condens in de auto</a> &ndash; waar het vocht zit, hoe je de auto droogt en de muffe lucht voorkomt.</li>
			</ul>
		</div>
	</div>
</section>

<?php if ( $sim_products ) : ?>
<section class="light collection" style="padding-top: 0;">
	<div class="wrap">
		<div class="section-head">
			<span class="mono-label">De collectie</span>
			<h2>Kies je geur.</h2>
		</div>
		<div class="cards">
			<?php
			foreach ( $sim_products as $p ) {
				$sim_post_obj = get_post( $p->get_id() );
				setup_postdata( $GLOBALS['post'] =& $sim_post_obj ); // phpcs:ignore
				wc_get_template_part( 'content', 'product' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
