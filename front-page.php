<?php
/**
 * Homepage fase 2: minder visuele ruis, twee geuren centraal, 2+1 als campagne.
 * Alle productdata live uit WooCommerce; uitverkochte producten verschijnen niet.
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
$hero        = $sim_products[0] ?? null;
$sim_vanilla = null;
$sim_apple   = null;
foreach ( $sim_products as $p ) {
	if ( 'creamy-vanilla-autoparfum' === $p->get_slug() ) {
		$sim_vanilla = $p;
	}
	if ( 'juicy-green-apple-autoparfum' === $p->get_slug() ) {
		$sim_apple = $p;
	}
}
$sim_promo = function_exists( 'sim_promo_active' ) && sim_promo_active();

/*
 * Prijs- en aanbodregel: live uit WooCommerce, nooit een doorgestreepte prijs.
 * De 2+1 set (Creamy Vanilla + Juicy Green Apple, extra Apple gratis) tonen we alleen
 * als de gratis fles ook echt mee kan: sim_promo_reconcile() begrenst die op de
 * Apple-voorraad, dus bij beheerde voorraad moeten er minstens 2 Apples zijn.
 */
$sim_drempel = 17.90; // gratis verzending vanaf; gelijk aan de verzendinstelling en de voortgangsbalk in de winkelwagen
$sim_set_ok  = $sim_promo && $sim_vanilla && $sim_apple
	&& ( ! $sim_apple->managing_stock() || (int) $sim_apple->get_stock_quantity() >= 2 );
$sim_losse   = array();
foreach ( array( $sim_vanilla, $sim_apple ) as $p ) {
	if ( $p ) {
		$sim_losse[] = (float) wc_get_price_to_display( $p );
	}
}
$sim_set_som    = $sim_set_ok ? array_sum( $sim_losse ) : 0.0;
$sim_set_gratis = $sim_set_som > 0 && (int) round( $sim_set_som * 100 ) >= (int) round( $sim_drempel * 100 );
$sim_gelijk     = 1 === count( array_unique( array_map( fn( $x ) => (int) round( $x * 100 ), $sim_losse ) ) );
?>

<!-- 01 HERO: op mobiel eerst kop, prijs en knop; het podium volgt eronder -->
<section class="hero wrap">
	<div class="hero-copy">
		<span class="mono-label">Autoparfum &middot; 60 ml sprays</span>
		<h1>Autoparfum dat je rit <span class="accent">verandert.</span></h1>
		<?php if ( $sim_losse ) : ?>
		<p class="hero-prijs">
			<span><?php echo $sim_gelijk ? 'Per spray van 60 ml: ' : 'Vanaf '; ?><b><?php echo wp_kses_post( wc_price( min( $sim_losse ) ) ); ?></b><?php echo $sim_gelijk ? '' : ' per spray van 60 ml'; ?></span>
			<?php if ( $sim_set_ok ) : ?>
			<span class="hero-set">2+1 set: 3 flesjes voor <b><?php echo wp_kses_post( wc_price( $sim_set_som ) ); ?></b><?php echo $sim_set_gratis ? ' met gratis verzending' : ''; ?></span>
			<?php endif; ?>
		</p>
		<?php endif; ?>
		<p class="lead">Creamy Vanilla, warm en zoet. Juicy Green Apple, fris en fruitig.</p>
		<div class="cta-row">
			<?php if ( $sim_set_ok ) : ?>
			<a class="btn btn-primary" href="<?php echo esc_url( sim_duo_url() ); ?>">Bekijk de 2+1 set</a>
			<a class="btn btn-outline" href="#geuren">Bekijk de geuren</a>
			<?php else : ?>
			<a class="btn btn-primary" href="#geuren">Bekijk de geuren</a>
			<?php endif; ?>
		</div>
		<div class="hero-facts">
			<span>60 ml per spray</span>
			<span>Tot 2 dagen geur per spraybeurt</span>
			<span>Vlekvrij getest op leer, stof en kunststof</span>
		</div>
	</div>
	<div class="stage" data-tilt="9" style="--tint: rgba(230,217,189,0.14)">
		<div class="backplate"></div>
		<div class="gridlines"></div>
		<div class="tilt"><div class="float"><?php echo $hero ? sim_product_visual( $hero, 'woocommerce_single', true ) : ''; // phpcs:ignore ?></div></div>
		<div class="shadow"></div>
		<span class="spec">TF&nbsp;/&nbsp;01&nbsp;&middot;&nbsp;60&nbsp;ML</span>
	</div>
</section>

<!-- TRUST -->
<div class="trust">
	<div class="wrap">
		<div class="item"><b>Gratis verzending</b><span>bij bestellingen vanaf &euro; 17,90</span></div>
		<div class="item"><b>Bezorging</b><span>doorgaans in 2 tot 4 werkdagen</span></div>
		<div class="item"><b>14 dagen retourrecht</b><span>ongebruikt en verzegeld</span></div>
		<div class="item"><b>Vlekvrij getest</b><span>op leer, stof en kunststof</span></div>
	</div>
</div>

<!-- 02 2+1 SET (hoofdaanbod) -->
<?php if ( $sim_set_ok ) : ?>
<section id="actie" class="campaign">
	<div class="wrap frame">
		<div class="copy">
			<span class="mono-label">2+1 set &middot; geen code nodig</span>
			<h2 class="camp-title">2 kiezen.<br>3 ontvangen.</h2>
			<p class="lead">Combineer Creamy Vanilla met Juicy Green Apple en ontvang een extra Juicy Green Apple van ons. De gratis fles komt automatisch in je winkelwagen.</p>
			<div class="camp-sum mono">
				<span>01 &middot; Creamy Vanilla 60 ml</span>
				<span>02 &middot; Juicy Green Apple 60 ml</span>
				<span class="vrij">03 &middot; Juicy Green Apple 60 ml &middot; gratis</span>
			</div>
			<p class="camp-prijs"><b><?php echo wp_kses_post( wc_price( $sim_set_som ) ); ?></b> voor 3 flesjes<?php echo $sim_set_gratis ? ' &middot; gratis verzending' : ''; ?></p>
			<a class="btn btn-primary" href="<?php echo esc_url( sim_duo_url() ); ?>">Bekijk de 2+1 set</a>
		</div>
		<div class="camp-media"
			data-sim-video="<?php echo esc_url( get_template_directory_uri() . '/assets/video/tropicalfresh-promovideo-v3.mp4' ); ?>"
			data-sim-poster="<?php echo esc_url( get_template_directory_uri() . '/assets/video/promovideo-poster-v3.jpg' ); ?>">
			<div class="bottles">
				<div class="b"><?php echo sim_product_visual( $sim_vanilla, 'woocommerce_thumbnail' ); // phpcs:ignore ?></div>
				<span class="plus">+</span>
				<div class="b"><?php echo sim_product_visual( $sim_apple, 'woocommerce_thumbnail' ); // phpcs:ignore ?></div>
				<span class="plus">+</span>
				<div class="b is-vrij">
					<?php echo sim_product_visual( $sim_apple, 'woocommerce_thumbnail' ); // phpcs:ignore ?>
					<span class="vrij-tag">Gratis</span>
				</div>
			</div>
		</div>
	</div>
</section>
<script>
/* Campagnevideo op alle schermformaten; alleen databesparing en reduced-motion
   houden de flesjes-visual (dan wordt niets extra's gedownload). */
(function () {
	var m = document.querySelector('.camp-media');
	if (!m || !window.matchMedia) return;
	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
	if (navigator.connection && navigator.connection.saveData) return;
	var done = false;
	function inject() {
		if (done) return;
		done = true;
		var v = document.createElement('video');
		v.muted = true; v.loop = true; v.playsInline = true;
		v.setAttribute('playsinline', ''); v.setAttribute('muted', '');
		v.preload = 'metadata';
		v.poster = m.getAttribute('data-sim-poster');
		v.src = m.getAttribute('data-sim-video');
		v.className = 'camp-video';
		v.setAttribute('aria-label', 'Promovideo van Tropical Fresh autoparfum');
		m.classList.add('has-video');
		m.appendChild(v);
		/* geluid staat standaard uit; de bezoeker kan hem zelf aanzetten */
		var k = document.createElement('button');
		k.type = 'button';
		k.className = 'camp-sound';
		k.textContent = 'Geluid aan';
		k.setAttribute('aria-pressed', 'false');
		k.addEventListener('click', function () {
			v.muted = !v.muted;
			k.textContent = v.muted ? 'Geluid aan' : 'Geluid uit';
			k.setAttribute('aria-pressed', v.muted ? 'false' : 'true');
			if (!v.muted) { v.play().catch(function () {}); }
		});
		m.appendChild(k);
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (es) {
				es.forEach(function (e) {
					if (e.isIntersecting) { v.play().catch(function () {}); } else { v.pause(); }
				});
			}, { threshold: 0.25 }).observe(v);
		} else {
			v.autoplay = true;
			v.play && v.play().catch(function () {});
		}
	}
	inject();
})();
</script>
<?php endif; ?>

<!-- 03 TWEE GEUREN -->
<section id="geuren" class="light duo-mods">
	<div class="wrap">
		<div class="section-head reveal">
			<span class="mono-label">Onze geuren</span>
			<h2>Twee karakters.</h2>
		</div>

		<?php
		$sim_mods = array(
			'creamy-vanilla-autoparfum'    => array( 'Warm.', 'Zacht.', 'Comfortabel.' ),
			'juicy-green-apple-autoparfum' => array( 'Fris.', 'Helder.', 'Energiek.' ),
		);
		$sim_i = 0;
		foreach ( $sim_products as $p ) :
			if ( ! isset( $sim_mods[ $p->get_slug() ] ) ) {
				continue;
			}
			$sim_woorden = $sim_mods[ $p->get_slug() ];
			$sim_even    = ( 0 === $sim_i % 2 );
			$sim_i++;
			?>
		<article class="mod reveal <?php echo $sim_even ? '' : 'is-omgekeerd'; ?>">
			<div class="mod-stage stage" data-tilt="7" style="--tint: <?php echo 'creamy-vanilla-autoparfum' === $p->get_slug() ? 'rgba(214,187,132,0.28)' : 'rgba(146,196,138,0.28)'; ?>">
				<div class="halo-licht"></div>
				<div class="tilt"><div class="float"><?php echo sim_product_visual( $p ); // phpcs:ignore ?></div></div>
				<div class="shadow"></div>
			</div>
			<div class="mod-copy">
				<span class="mono-label"><?php echo esc_html( sprintf( '%02d / %s', $sim_i, strtoupper( preg_replace( '/^(Creamy|Juicy)\s+/i', '', sim_short_name( $p ) ) ) ) ); ?></span>
				<h3><?php echo esc_html( sim_short_name( $p ) ); ?></h3>
				<?php if ( $sim_set_ok && 'juicy-green-apple-autoparfum' === $p->get_slug() ) : ?>
					<span class="promo-chip">2+1: extra Apple gratis</span>
				<?php endif; ?>
				<p class="woorden"><?php echo esc_html( implode( ' ', $sim_woorden ) ); ?></p>
				<div class="mod-foot">
					<span class="price"><?php echo wp_kses_post( wc_price( wc_get_price_to_display( $p ) ) ); ?><span class="per">60 ml</span></span>
					<div class="mod-acties">
						<a href="<?php echo esc_url( $p->add_to_cart_url() ); ?>" data-quantity="1"
							class="btn btn-primary add_to_cart_button ajax_add_to_cart"
							data-product_id="<?php echo esc_attr( $p->get_id() ); ?>" rel="nofollow">In winkelwagen</a>
						<a class="btn btn-outline" href="<?php echo esc_url( $p->get_permalink() ); ?>">Ontdek de geur</a>
					</div>
				</div>
			</div>
		</article>
		<?php endforeach; ?>
	</div>
</section>

<!-- STATEMENT -->
<section class="statement-strip">
	<div class="wrap reveal"><p class="display">Stap in. <em>Adem uit.</em></p></div>
</section>

<!-- 04+05 WAAROM + HOE -->
<section class="light method">
	<div class="wrap">
		<div class="cols">
			<div class="why reveal">
				<span class="mono-label">Waarom spray</span>
				<h2 style="margin: 16px 0 20px;">Geen hangertje aan je spiegel.</h2>
				<p class="lead">Een spray laat niets aan je interieur bungelen. Jij bepaalt zelf de intensiteit: meer sprays voor een vollere geur, minder voor subtiel.</p>
				<p class="lead">Elke geur is vlekvrij getest op leer, stof en kunststof.</p>
			</div>
			<div class="steps reveal">
				<div class="step"><span class="n">STAP 01</span><div><h3>Spray</h3><p>Twee tot drie keer in het interieur, bijvoorbeeld op de matten of onder de stoel.</p></div></div>
				<div class="step"><span class="n">STAP 02</span><div><h3>Laat verdelen</h3><p>De geur verspreidt zich vanzelf door de auto terwijl je rijdt.</p></div></div>
				<div class="step"><span class="n">STAP 03</span><div><h3>Geniet van de rit</h3><p>Tot twee dagen geur per spraybeurt. Daarna spray je gewoon opnieuw.</p></div></div>
			</div>
		</div>
	</div>
</section>

<!-- 06 VERHAAL -->
<section id="verhaal" class="light story">
	<div class="wrap inner">
		<div class="reveal">
			<span class="mono-label">Ons verhaal</span>
			<h2 style="margin: 16px 0 20px;">Klein merk. E&eacute;n obsessie.</h2>
			<p class="lead">Tropical Fresh is een Nederlands merk uit Leiden met &eacute;&eacute;n overtuiging: je auto is een ruimte waar je echt tijd doorbrengt, dus de geur verdient dezelfde aandacht als het interieur.</p>
			<p class="lead">Daarom houden we de collectie klein en is elke geur vlekvrij getest op leer, stof en kunststof.</p>
		</div>
		<div class="facts reveal">
			<div><span>Merk</span><b>Tropical Fresh B.V.</b></div>
			<div><span>Thuisbasis</span><b>Leiden, Nederland</b></div>
			<div><span>Formaat</span><b>60 ml spray</b></div>
			<div><span>Contact</span><b>info@tropicalfresh.nl</b></div>
		</div>
	</div>
</section>

<!-- 07 FAQ -->
<section class="light faq">
	<div class="wrap">
		<div class="section-head reveal">
			<span class="mono-label">Veelgestelde vragen</span>
			<h2>Voordat je bestelt.</h2>
		</div>
		<div class="list reveal">
			<details class="acc"><summary>Hoe gebruik ik autoparfum?</summary><div class="body">Spray twee tot drie keer in het interieur van je auto, bijvoorbeeld op de matten of onder de stoel. Je hebt tot twee dagen geur per spraybeurt. Meer uitleg vind je op onze <a href="<?php echo esc_url( home_url( '/autoparfum/' ) ); ?>">autoparfum-pagina</a>.</div></details>
			<?php if ( $sim_set_ok ) : ?>
			<details class="acc"><summary>Hoe werkt de 2+1 set?</summary><div class="body">Leg Creamy Vanilla en Juicy Green Apple samen in je winkelwagen; wij voegen automatisch een extra Juicy Green Apple van &euro; 0,00 toe. Zo krijg je 3 flesjes voor <?php echo wp_kses_post( wc_price( $sim_set_som ) ); ?><?php echo $sim_set_gratis ? ' met gratis verzending' : ''; ?>. Geen code nodig, en de 2+1 geldt per combinatie. Met de <a href="<?php echo esc_url( sim_duo_url() ); ?>">2+1 set</a> leg je beide geuren in &eacute;&eacute;n keer in je winkelwagen.</div></details>
			<?php endif; ?>
			<details class="acc"><summary>Waar spray ik het?</summary><div class="body">Bijvoorbeeld op de matten of onder de stoel. Elke geur is vlekvrij getest op leer, stof en kunststof.</div></details>
			<details class="acc"><summary>Hoe lang blijft de geur hangen?</summary><div class="body">Tot twee dagen geur per spraybeurt. Daarna spray je gewoon opnieuw.</div></details>
			<details class="acc"><summary>Hoe snel wordt mijn bestelling verzonden?</summary><div class="body">Je bestelling wordt doorgaans binnen 2 tot 4 werkdagen bezorgd, overal in Nederland. Vanaf &euro; 17,90 is de verzending gratis; daaronder betaal je &euro; 4,95 verzendkosten.</div></details>
			<details class="acc"><summary>Wat als een geur niet bij mij past?</summary><div class="body">Je hebt 14 dagen retourrecht. Ongebruikte producten in originele, verzegelde verpakking kun je aanmelden via onze retourpagina.</div></details>
		</div>
	</div>
</section>

<!-- 08 VOLG ONS: na de FAQ, zodat bezoekers eerst producten en antwoorden zien -->
<?php $sim_kanalen = sim_sociale_kanalen(); ?>
<section class="light volg">
	<div class="wrap volg-inner">
		<div class="reveal">
			<span class="mono-label">Volg ons</span>
			<h2>Tropical Fresh op TikTok en Instagram.</h2>
		</div>
		<div class="reveal">
			<?php echo sim_sociale_links( 'sociaal', array( 'tiktok', 'instagram' ), true ); // phpcs:ignore ?>
			<p class="volg-yt">Ook op YouTube: <a href="<?php echo esc_url( $sim_kanalen['youtube']['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $sim_kanalen['youtube']['handle'] ); ?></a></p>
		</div>
	</div>
</section>

<?php get_footer(); ?>
