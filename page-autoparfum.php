<?php
/**
 * SEO-pijlerpagina /autoparfum/ (template-hierarchie: slug "autoparfum").
 * Redactioneel, feitelijk, met interne links naar de geuren en de geurwijzer.
 * Direct onder de H1 een koopstrook met de leverbare geuren en de 2+1 set,
 * met actuele prijzen uit WooCommerce (nooit hardcoded, nooit doorgestreept).
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

$sim_karakter = array(
	'creamy-vanilla-autoparfum'    => 'Warm en zoet',
	'juicy-green-apple-autoparfum' => 'Fris en fruitig',
);
$sim_kleur    = array(
	'creamy-vanilla-autoparfum'    => 'is-vanilla',
	'juicy-green-apple-autoparfum' => 'is-apple',
);

// Zelfde drempel als de verzendvoortgang in de winkelwagen (functions.php).
$sim_drempel = 17.90;

// 2+1 set: alleen tonen zolang de actie loopt; prijs = som van de betaalde flesjes,
// net als de prijsweergave van de Duo in functions.php.
$sim_promo     = function_exists( 'sim_promo_active' ) && sim_promo_active();
$sim_duo_post  = get_page_by_path( 'tropical-fresh-duo', OBJECT, 'product' );
$sim_duo       = $sim_duo_post ? wc_get_product( $sim_duo_post->ID ) : null;
$sim_set_prijs = 0.0;
if ( $sim_promo && $sim_duo && 'publish' === $sim_duo->get_status() ) {
	foreach ( $sim_duo->get_children() as $sim_child_id ) {
		$sim_child = wc_get_product( $sim_child_id );
		if ( $sim_child && $sim_child->is_purchasable() && $sim_child->is_in_stock() ) {
			$sim_set_prijs += (float) wc_get_price_to_display( $sim_child );
		}
	}
}

// Losse prijzen voor de koopstrook en de prijsvraag in de FAQ.
$sim_prijzen = array();
foreach ( $sim_products as $p ) {
	$sim_prijzen[ $p->get_slug() ] = (float) wc_get_price_to_display( $p );
}
?>
<section class="light page-wrap">
	<div class="wrap narrow">
		<nav class="crumbs" aria-label="Kruimelpad" style="padding-bottom: 18px;">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &nbsp;/&nbsp; Autoparfum
		</nav>
		<span class="mono-label">Autoparfum</span>
		<h1 style="margin: 16px 0 18px;">Autoparfum voor een frisse auto</h1>

		<?php if ( $sim_products ) : ?>
		<div class="koopstrook" role="group" aria-label="Onze geuren en prijzen">
			<?php foreach ( $sim_products as $p ) : ?>
			<a class="ks-item <?php echo esc_attr( $sim_kleur[ $p->get_slug() ] ?? '' ); ?>" href="<?php echo esc_url( $p->get_permalink() ); ?>">
				<span class="ks-stip" aria-hidden="true"></span>
				<span class="ks-naam"><b><?php echo esc_html( sim_short_name( $p ) ); ?></b><span><?php echo esc_html( $sim_karakter[ $p->get_slug() ] ?? '' ); ?> &middot; 60 ml</span></span>
				<span class="ks-prijs"><?php echo wp_kses_post( wc_price( $sim_prijzen[ $p->get_slug() ] ) ); ?></span>
				<span class="ks-pijl" aria-hidden="true">&rarr;</span>
			</a>
			<?php endforeach; ?>
			<?php if ( $sim_set_prijs > 0 ) : ?>
			<a class="ks-item is-set" href="<?php echo esc_url( $sim_duo->get_permalink() ); ?>">
				<span class="ks-stip" aria-hidden="true"></span>
				<span class="ks-naam"><b>2+1 set</b><span>3 flesjes: beide geuren plus een extra Juicy Green Apple gratis<?php echo round( $sim_set_prijs, 2 ) >= $sim_drempel ? ', met gratis verzending' : ''; ?></span></span>
				<span class="ks-prijs"><?php echo wp_kses_post( wc_price( $sim_set_prijs ) ); ?></span>
				<span class="ks-pijl" aria-hidden="true">&rarr;</span>
			</a>
			<?php endif; ?>
			<p class="ks-noot">Gratis verzending in Nederland vanaf <?php echo wp_kses_post( wc_price( $sim_drempel ) ); ?> &middot; 14 dagen retourrecht</p>
		</div>
		<?php endif; ?>

		<div class="page-content">
			<p>Autoparfum is een geurspray voor het interieur van je auto: je spuit hem op de matten, de stoelen of kunststof. Anders dan bij een geurhanger bepaal jij zelf hoe sterk het ruikt; begin met twee tot drie sprays.</p>
			<p>Mensen noemen het ook een auto luchtje, autogeur of auto geurtje, en in webshops kom je het Engelse car parfum tegen. Het gaat steeds om hetzelfde: een geur voor in de auto die jij zelf aanbrengt, in plaats van een boompje dat aan de spiegel hangt.</p>

			<h2>Spray of geurhanger?</h2>
			<p>Een geurhanger werkt passief: hij hangt aan je spiegel en geeft continu geur af tot hij op is. Een autoparfum-spray werkt actief: je doseert zelf wanneer en hoeveel, en er bungelt niets in je zicht. Onze sprays zijn vlekvrij getest op leer, stof en kunststof.</p>

			<h2>Zo gebruik je autoparfum</h2>
			<ol>
				<li>Spray twee tot drie keer in het interieur, bijvoorbeeld op de matten of onder de stoel.</li>
				<li>Laat de geur zich verdelen terwijl je rijdt; de auto hoeft er niet voor dicht of open.</li>
				<li>Je hebt tot twee dagen geur per spraybeurt. Wil je het sterker, spray dan vaker; te sterk is met &eacute;&eacute;n spray minder zo opgelost.</li>
			</ol>

			<h2>Eerst fris, dan geur</h2>
			<?php
			$sim_vent = wp_get_attachment_image(
				102,
				'medium',
				false,
				array(
					'alt'     => 'Rond ventilatierooster in een donker autodashboard',
					'loading' => 'lazy',
					'sizes'   => '(max-width: 400px) 100vw, 360px',
				)
			);
			if ( $sim_vent ) {
				echo '<figure class="art-beeld">' . $sim_vent . '</figure>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
			<p>Autoparfum komt het best tot zijn recht in een auto die al schoon en droog is. Ruikt je auto muf, naar rook of naar je hond, dan maskeert een geur dat hooguit even. Pak daarom eerst de bron aan en voeg pas daarna een nieuwe geur toe.</p>
			<ul>
				<li><strong>Muffe lucht of beslagen ruiten:</strong> bijna altijd vocht. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/condens-vocht-auto/' ) ); ?>">vocht en condens uit de auto haalt</a>.</li>
				<li><strong>Muffe lucht uit de roosters zodra de ventilator aangaat:</strong> dan zit het in het ventilatiesysteem. Lees waarom je <a href="<?php echo esc_url( home_url( '/autoparfum/airco-stinkt-muffe-geur-auto/' ) ); ?>">airco stinkt en hoe je dat oplost</a>.</li>
				<li><strong>Rooklucht:</strong> die zit in bekleding, hemel en interieurfilter. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/rooklucht-uit-de-auto/' ) ); ?>">rooklucht uit de auto haalt</a>.</li>
				<li><strong>Een andere hardnekkige vieze geur:</strong> volg het <a href="<?php echo esc_url( home_url( '/autoparfum/vieze-geur-auto-verwijderen/' ) ); ?>">stappenplan om vieze geur te verwijderen</a>.</li>
				<li><strong>Hondengeur:</strong> grondig zuigen, reinigen en dekens goed laten drogen. Alles staat in <a href="<?php echo esc_url( home_url( '/autoparfum/hondengeur-uit-de-auto/' ) ); ?>">hondengeur uit de auto halen</a>.</li>
				<li><strong>Net een gebruikte auto gekocht:</strong> eerst het interieurfilter vervangen en per materiaal reinigen. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/tweedehands-auto-geur-verwijderen/' ) ); ?>">de geur van een tweedehands auto verwijdert</a>.</li>
				<li><strong>Etenslucht na een snelle hap onderweg:</strong> vet en kruimels zitten in de stof en de naden. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/eetgeur-fastfood-uit-auto-halen/' ) ); ?>">eetgeur en fastfoodlucht uit de auto haalt</a>.</li>
				<li><strong>Zweetlucht na het sporten:</strong> natte sportkleding, schoenen en een dichte tas trekken in matten en kofferbak. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/zweetlucht-sportgeur-auto/' ) ); ?>">zweetlucht en sportgeur uit de auto haalt</a>.</li>
				<li><strong>Zure lucht na gemorste melk of drank:</strong> eiwit en suiker zakken in de vulling van stoel of mat en gaan daar gisten. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/zure-geur-gemorste-melk-auto/' ) ); ?>">een zure melklucht uit de auto haalt</a>.</li>
				<li><strong>Muffe lucht uit de kofferbak:</strong> natte spullen en vocht onder de vloerplaat blijven in een dichte laadruimte hangen. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/kofferbak-stinkt-geur-verwijderen/' ) ); ?>">een stinkende kofferbak weer fris krijgt</a>.</li>
				<li><strong>Stoelen die zelf stinken:</strong> zweet, gemorste drank en vocht trekken in de zitting en komen bij warm weer terug. Lees hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/autostoelen-geur-verwijderen/' ) ); ?>">geur uit autostoelen van stof en leer haalt</a>.</li>
			</ul>
			<p>Is de auto fris, dan doen een paar sprays op de matten of onder de stoel de rest.</p>

			<h2>Welke geur kies je?</h2>
			<p>Onze collectie is bewust klein. <a href="<?php echo esc_url( home_url( '/product/creamy-vanilla-autoparfum/' ) ); ?>">Creamy Vanilla</a> is warm en zoet, fijn op koude dagen en rustige ritten. <a href="<?php echo esc_url( home_url( '/product/juicy-green-apple-autoparfum/' ) ); ?>">Juicy Green Apple</a> is fris en fruitig. Twijfel je? De <a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">geurwijzer</a> helpt je in &eacute;&eacute;n vraag kiezen.
			<?php if ( $sim_set_prijs > 0 ) : ?>
				Of neem ze allebei met de <a href="<?php echo esc_url( $sim_duo->get_permalink() ); ?>">2+1 set</a>: je krijgt er dan automatisch een extra Juicy Green Apple gratis bij.
			<?php endif; ?>
			</p>
			<h3>Wisselen per seizoen</h3>
			<p>In de herfst en winter, als de ramen dicht blijven en de verwarming aan staat, voelt een warme geur als Creamy Vanilla vertrouwd. In het voorjaar en de zomer, of na een grondige schoonmaakbeurt, past een frisse groene geur als Juicy Green Apple vaak beter. Met twee flesjes in het handschoenenkastje wissel je per seizoen of per stemming. Wat je per seizoen verder anders doet met vocht, hitte en doseren, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-per-seizoen/' ) ); ?>">autoparfum per seizoen</a>.</p>
			<p>Wil je weten welke vorm bij jouw ritten past, lees dan hoe je <a href="<?php echo esc_url( home_url( '/autoparfum/beste-autoparfum-kiezen/' ) ); ?>">de beste autoparfum kiest</a>. Zoek je een geur voor iemand anders, dan helpen onze tips voor <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-cadeau/' ) ); ?>">autoparfum als cadeau</a>.</p>

			<h2>Veelgestelde vragen over autoparfum</h2>
			<h3>Hoe lang blijft autoparfum ruiken?</h3>
			<p>Tot twee dagen per spraybeurt, afhankelijk van je auto, ventilatie en hoeveel je sprayt. Wat de duur precies bepaalt, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/hoe-lang-blijft-autoparfum-hangen/' ) ); ?>">hoe lang blijft autoparfum hangen</a>.</p>
			<h3>Is autoparfum veilig voor mijn interieur?</h3>
			<p>Onze sprays zijn vlekvrij getest op leer, stof en kunststof. Spray niet direct op schermen of glas; dat is niet nodig voor de werking. Wat je per materiaal wel en niet doet, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-veilig-leer-stof-kunststof/' ) ); ?>">autoparfum veilig gebruiken op leer, stof en kunststof</a>.</p>
			<h3>Wat is het verschil met een luchtverfrisser?</h3>
			<p>Een klassieke luchtverfrisser of geurhanger geeft continu geur af, of je dat nu wilt of niet. Autoparfum doseer je zelf, net als een parfum: jij bepaalt hoeveel en waar je sprayt.</p>
			<h3>Is een auto luchtje hetzelfde als autoparfum?</h3>
			<p>In de praktijk gebruiken mensen auto luchtje, autogeur en auto geurtje voor elke geur in de auto, van een boompje aan de spiegel tot een spray. Autoparfum is de sprayvorm: je spuit hem zelf op de matten of onder de stoel, doseert hoeveel je wilt en er hangt niets in je zicht.</p>
			<h3>Waar spray je autoparfum het beste?</h3>
			<p>Op de matten, onder de stoelen of op stoffen bekleding. Daar verdeelt de geur zich rustig door de auto terwijl je rijdt. Schermen en glas sla je over. Hoeveel sprays genoeg zijn en wanneer je het best spuit, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-spuiten-waar-en-hoeveel/' ) ); ?>">autoparfum spuiten: waar, hoeveel en wanneer</a>.</p>
			<h3>Helpt autoparfum tegen een muffe lucht?</h3>
			<p>Alleen als afwerking. Een muffe lucht komt vrijwel altijd door vocht, dus droog de auto eerst. Anders komt de muffe lucht terug zodra de geur is uitgewerkt.</p>
			<h3>Hoeveel kost autoparfum?</h3>
			<p>
			<?php if ( $sim_prijzen && 1 === count( array_unique( $sim_prijzen ) ) ) : ?>
				Bij Tropical Fresh kost een flesje van 60 ml <?php echo wp_kses_post( wc_price( reset( $sim_prijzen ) ) ); ?>.
			<?php elseif ( $sim_prijzen ) : ?>
				<?php
				$sim_delen = array();
				foreach ( $sim_products as $p ) {
					$sim_delen[] = esc_html( sim_short_name( $p ) ) . ' kost ' . wc_price( $sim_prijzen[ $p->get_slug() ] );
				}
				echo wp_kses_post( implode( ' en ', $sim_delen ) ) . ', per flesje van 60 ml.';
				?>
			<?php else : ?>
				De actuele prijzen vind je bij <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">de geuren</a>.
			<?php endif; ?>
			<?php if ( $sim_set_prijs > 0 ) : ?>
				De <a href="<?php echo esc_url( $sim_duo->get_permalink() ); ?>">2+1 set</a> met drie flesjes kost <?php echo wp_kses_post( wc_price( $sim_set_prijs ) ); ?>.
			<?php endif; ?>
				Vanaf <?php echo wp_kses_post( wc_price( $sim_drempel ) ); ?> is de verzending in Nederland gratis.
			</p>

			<h2>Verder lezen</h2>
			<ul>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/vieze-geur-auto-verwijderen/' ) ); ?>">Vieze geur in je auto verwijderen: het stappenplan</a>. Van rooklucht tot hondengeur, eerst de bron weg.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/beste-autoparfum-kiezen/' ) ); ?>">Beste autoparfum kiezen</a>. Spray, geurhanger en ventilatieclip eerlijk vergeleken.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/nieuwe-auto-geur/' ) ); ?>">De nieuwe-autogeur terughalen</a>. Waar die showroomlucht vandaan komt en hoe je hem terugbrengt.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-cadeau/' ) ); ?>">Autoparfum als cadeau</a>. Voor wie het past en hoe je een geur kiest voor iemand anders.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/hondengeur-uit-de-auto/' ) ); ?>">Hondengeur uit de auto halen</a>. Grondig schoonmaken, drogen en fris houden als je hond meerijdt.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/condens-vocht-auto/' ) ); ?>">Vocht en condens in de auto</a>. Waar het vocht zit, hoe je de auto droogt en de muffe lucht voorkomt.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/hoe-lang-blijft-autoparfum-hangen/' ) ); ?>">Hoe lang blijft autoparfum hangen?</a> Wat warmte, ventilatie en geurgewenning met je autogeur doen.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/rooklucht-uit-de-auto/' ) ); ?>">Rooklucht uit de auto halen</a>. Reinigen, interieurfilter vervangen en de restgeur laten absorberen.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/interieur-schoonmaken-checklist/' ) ); ?>">Interieur van je auto schoonmaken: de checklist</a>. Zone voor zone van boven naar beneden, en pas daarna een geur.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-per-seizoen/' ) ); ?>">Autoparfum per seizoen</a>. Lente, zomer, herfst en winter: wat je per seizoen anders doet voor een frisse auto.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-spuiten-waar-en-hoeveel/' ) ); ?>">Autoparfum spuiten: waar, hoeveel en wanneer</a>. De beste plekken, het juiste aantal sprays en het goede moment.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/tweedehands-auto-geur-verwijderen/' ) ); ?>">Tweedehands auto geur verwijderen</a>. Van de geur van de vorige eigenaar naar een neutrale, frisse auto in zes stappen.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/airco-stinkt-muffe-geur-auto/' ) ); ?>">Airco stinkt: muffe geur uit de ventilatie halen</a>. Interieurfilter, verdamper en afvoer: zo komt er weer schone lucht uit de roosters.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/eetgeur-fastfood-uit-auto-halen/' ) ); ?>">Eetgeur en fastfoodlucht uit de auto halen</a>. De bron vinden, per materiaal reinigen, de restgeur neutraliseren en pas daarna een geur.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/zweetlucht-sportgeur-auto/' ) ); ?>">Zweetlucht en sportgeur uit de auto halen</a>. Vochtige sportspullen eruit, per materiaal reinigen, de restgeur absorberen en pas daarna een frisse spray.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/zure-geur-gemorste-melk-auto/' ) ); ?>">Zure melklucht en gemorste drank uit de auto halen</a>. Direct deppen met koud water, per materiaal reinigen, zuiveringszout of enzymreiniger en pas na het drogen een spray.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/kofferbak-stinkt-geur-verwijderen/' ) ); ?>">Kofferbak stinkt: muffe geur uit de kofferbak halen</a>. Leegmaken, de bron onder de vloerplaat vinden, drogen, neutraliseren en pas daarna een spray.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/autostoelen-geur-verwijderen/' ) ); ?>">Geur uit autostoelen verwijderen: stof en leer</a>. Bron zoeken, stofzuigen, per materiaal deppen, volledig drogen en pas daarna een frisse afwerking.</li>
				<li><a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-veilig-leer-stof-kunststof/' ) ); ?>">Autoparfum veilig gebruiken op leer, stof en kunststof</a>. Afstand houden, klein beginnen, een testplek kiezen en per materiaal weten wat je wel en niet doet.</li>
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
			$sim_kaarten = $sim_products;
			if ( $sim_set_prijs > 0 ) {
				$sim_kaarten[] = $sim_duo;
			}
			foreach ( $sim_kaarten as $p ) {
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
