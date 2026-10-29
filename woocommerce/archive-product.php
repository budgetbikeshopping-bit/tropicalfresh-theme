<?php
/**
 * Shoppagina: SEO- en conversielandingspagina in plaats van een kale grid.
 */
defined( 'ABSPATH' ) || exit;

get_header();

// Zelfde drempel als de verzendvoortgang in de winkelwagen en de pijler (functions.php).
$sim_drempel = 17.90;

// 2+1 set alleen noemen zolang de actie loopt en de set leverbaar is.
$sim_promo    = function_exists( 'sim_promo_active' ) && sim_promo_active();
$sim_duo_post = get_page_by_path( 'tropical-fresh-duo', OBJECT, 'product' );
$sim_duo      = $sim_duo_post ? wc_get_product( $sim_duo_post->ID ) : null;
$sim_duo_ok   = $sim_promo && $sim_duo && 'publish' === $sim_duo->get_status() && $sim_duo->is_in_stock();
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
				// Het beeld van de eerste kaart is het LCP-element: direct laden, niet lazy.
				$GLOBALS['sim_eerste_kaart_eager'] = true;
				while ( have_posts() ) :
					the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;
				$GLOBALS['sim_eerste_kaart_eager'] = false;
				?>
			</div>
			<?php do_action( 'woocommerce_after_shop_loop' ); ?>
		<?php else : ?>
			<p class="lead">Er zijn op dit moment geen producten beschikbaar.</p>
		<?php endif; ?>

		<?php if ( is_shop() || is_tax( 'product_cat', 'autoparfum' ) ) : ?>
		<div class="cat-tekst">
			<h2>Autoparfum kopen bij Tropical Fresh</h2>
			<p>Onze autoparfums zijn sprays van 60 ml: twee tot drie sprays in het interieur en je hebt tot twee dagen geur per spraybeurt. Geen hangertje aan je spiegel; elke geur is vlekvrij getest op leer, stof en kunststof. Jij bepaalt zelf de intensiteit door vaker of minder vaak te sprayen.</p>
			<p>De collectie is bewust klein: Creamy Vanilla is warm en zoet, Juicy Green Apple fris en fruitig. Twijfel je? De <a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">geurwijzer</a> helpt je in &eacute;&eacute;n vraag kiezen. Vanaf <?php echo wp_kses_post( wc_price( $sim_drempel ) ); ?> is de verzending in Nederland gratis en je hebt 14 dagen retourrecht. Tropical Fresh is een Nederlands merk uit Leiden.</p>

			<h2>Koopgids: welk auto geurtje past bij jou?</h2>
			<p>Auto geurtjes zijn er in veel vormen: een boompje aan de spiegel, een clip op het rooster, een geurhanger of een spray. Bij Tropical Fresh kies je bewust voor de spray. Je spuit een paar keer op de matten of onder de stoel, de geur verdeelt zich tijdens het rijden en er hangt niets in je zicht. Hoe die vormen zich tot elkaar verhouden, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/beste-autoparfum-kiezen/' ) ); ?>">beste autoparfum kiezen</a>.</p>
			<ul>
				<li><strong>Je wilt iets warms voor koude dagen en rustige ritten:</strong> Creamy Vanilla, zacht en zoet.</li>
				<li><strong>Je wilt een schone, frisse indruk, bijvoorbeeld na een schoonmaakbeurt:</strong> Juicy Green Apple, groen en fruitig.</li>
				<?php if ( $sim_duo_ok ) : ?>
				<li><strong>Je wilt wisselen per stemming of seizoen:</strong> de <a href="<?php echo esc_url( $sim_duo->get_permalink() ); ?>">2+1 set</a> met beide geuren, zodat je altijd een flesje bij de hand hebt.</li>
				<?php endif; ?>
			</ul>
			<p>Ruikt je auto nu nog naar rook, hond of vocht? Pak dan eerst de bron aan, want een geur erboven maskeert dat hooguit even. Het <a href="<?php echo esc_url( home_url( '/autoparfum/vieze-geur-auto-verwijderen/' ) ); ?>">stappenplan voor vieze geur in de auto</a> helpt je op weg en op de <a href="<?php echo esc_url( home_url( '/autoparfum/' ) ); ?>">autoparfum-pagina</a> staan alle gidsen bij elkaar.</p>

			<h3>Zo bestel je autoparfum</h3>
			<p>Kies hierboven je geur of de set en zet hem in je mandje. Bij het afrekenen betaal je met iDEAL, Wero of kaart. Vanaf <?php echo wp_kses_post( wc_price( $sim_drempel ) ); ?> verzenden we gratis binnen Nederland en je hebt 14 dagen om je bestelling terug te sturen als de geur je toch niet bevalt. Wil je weten waar en hoeveel je spuit zodra het pakje binnen is? Lees dan <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-spuiten-waar-en-hoeveel/' ) ); ?>">autoparfum spuiten: waar, hoeveel en wanneer</a>.</p>

			<h2>Veelgestelde vragen over auto geurtjes</h2>
			<h3>Wat is het verschil tussen een auto geurtje en autoparfum?</h3>
			<p>Auto geurtje is de verzamelnaam voor alles wat je auto laat ruiken, van boompje tot spray. Autoparfum is de sprayvorm: je doseert zelf, spuit op de matten in plaats van iets aan de spiegel te hangen en bepaalt zo hoe sterk het ruikt.</p>
			<h3>Hoe lang ruik je een auto geurtje van Tropical Fresh?</h3>
			<p>Tot twee dagen per spraybeurt. Veel ventileren, warmte en een grote cabine verkorten dat, een paar extra sprays op de matten verlengen het. Meer over de duur lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/hoe-lang-blijft-autoparfum-hangen/' ) ); ?>">hoe lang blijft autoparfum hangen</a>.</p>
			<h3>Welke geur kies ik als ik twijfel?</h3>
			<p>Doe de <a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">geurwijzer</a>: in &eacute;&eacute;n vraag zie je of warm en zoet of fris en fruitig beter bij je rit past. Twijfel je dan nog, dan is een flesje van elk de makkelijkste manier om het zelf te ontdekken.</p>
			<h3>Is de spray veilig voor leer en stof?</h3>
			<p>Ja, elke geur is vlekvrij getest op leer, stof en kunststof. Spray niet op schermen of glas, dat is niet nodig voor de werking.</p>
			<h3>Wat is een goed auto luchtje als je iets zoekt dat lang blijft?</h3>
			<p>Een auto luchtje dat je zelf doseert. Met een spray bepaal je per rit hoeveel je spuit: tot twee dagen per spraybeurt is de basis, en een paar extra sprays op de matten rekken dat op. Een boompje of hanger geeft je die keuze niet.</p>
			<h3>Hoe krijg ik een vieze autogeur weg voordat ik ga sprayen?</h3>
			<p>Eerst de bron weg: natte matten, etensresten of een vol interieurfilter. Zolang die er zit, komt de autogeur steeds terug, hoe lekker het luchtje ook ruikt. Het <a href="<?php echo esc_url( home_url( '/autoparfum/vieze-geur-auto-verwijderen/' ) ); ?>">stappenplan voor vieze geur</a> en de <a href="<?php echo esc_url( home_url( '/autoparfum/' ) ); ?>">autoparfum-gids</a> helpen je stap voor stap.</p>
			<h3>Wanneer is de verzending gratis?</h3>
			<p>Vanaf <?php echo wp_kses_post( wc_price( $sim_drempel ) ); ?> binnen Nederland. Daaronder zie je de verzendkosten bij het afrekenen voordat je betaalt.</p>
		</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
