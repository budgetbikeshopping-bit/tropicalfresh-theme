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

<?php
// Gidsblok onder de wijzer: hoe je een autogeur kiest, plus FAQ (zelfde drempel als de pijler).
$sim_drempel = 17.90;
?>
<section class="light page-wrap" style="padding-top: 0;">
	<div class="wrap narrow">
		<div class="page-content">
			<h2>Zo kies je een autogeur die bij je past</h2>
			<p>Een autogeur kies je anders dan een parfum voor jezelf. Je ruikt hem in een kleine, gesloten ruimte, vaak urenlang, en meestal terwijl je op de weg let. Daarom valt een geur die je thuis geweldig vindt in de auto soms te zwaar uit, en komt een geur die je in de winkel nauwelijks opvalt onderweg juist goed tot zijn recht. Drie vragen helpen je kiezen.</p>

			<h3>1. Wat voor ritten maak je?</h3>
			<p>Bij korte stadsritten wil je een geur die snel merkbaar is en niet gaat overheersen zodra je even stilstaat. Een frisse, fruitige geur zoals Juicy Green Apple is dan een veilige keuze. Maak je lange ritten of rijd je veel in de avond, dan voelt een warme, zoete geur zoals Creamy Vanilla vaak rustiger. Rijd je met wisselende passagiers of in een leaseauto, kies dan de lichtste variant en doseer zuinig: &eacute;&eacute;n spray minder is zo gedaan.</p>

			<h3>2. Hoe ruikt je auto nu?</h3>
			<p>Een autogeur komt alleen goed uit in een schone auto. Hangt er al een vieze lucht, dan vermengt die zich met de spray en wordt het resultaat alleen maar vreemder. Pak dus eerst de bron aan met ons <a href="<?php echo esc_url( home_url( '/autoparfum/vieze-geur-auto-verwijderen/' ) ); ?>">stappenplan tegen vieze geur in de auto</a> en spuit daarna pas. Ruikt de auto neutraal, dan kun je meteen kiezen.</p>

			<h3>3. Welk seizoen is het?</h3>
			<p>In de herfst en winter blijven de ramen dicht en staat de verwarming aan; een warme geur voelt dan vertrouwd. In het voorjaar en de zomer, met het raam open en meer frisse lucht, past een groene, fruitige geur beter. Met twee flesjes in het handschoenenkastje wissel je per seizoen of per stemming. Wat je per seizoen verder anders doet, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-per-seizoen/' ) ); ?>">autoparfum per seizoen</a>.</p>

			<h2>Autogeur, auto luchtje of autoparfum: wat is het verschil?</h2>
			<p>In de praktijk zijn autogeur, auto luchtje en auto geurtje verzamelnamen voor alles wat je auto laat ruiken: het boompje aan de spiegel, de clip op het rooster, de geurhanger en de spray. Autoparfum is de sprayvorm. Het verschil zit in de regie. Een hanger of clip geeft continu geur af, of je dat nu wilt of niet; een spray doseer je zelf, zoals een parfum. Twee tot drie sprays op de matten of onder de stoel zijn genoeg, en per spraybeurt heb je tot twee dagen geur. Onze sprays zijn vlekvrij getest op leer, stof en kunststof en zitten in een flesje van 60 ml. Welke vorm bij jouw ritten past, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/beste-autoparfum-kiezen/' ) ); ?>">de beste autoparfum kiezen: spray, hanger of clip</a>.</p>

			<h2>Twee karakters, &eacute;&eacute;n collectie</h2>
			<h3>Creamy Vanilla: warm en zoet</h3>
			<p>Romig, zacht en huiselijk. Vanille past bij wie comfort zoekt in de auto: rustige ritten, koude ochtenden, een cabine die als een eigen plek voelt. Een geur die niet opdringt en goed samengaat met een interieur van leer of donkere stof.</p>
			<h3>Juicy Green Apple: fris en fruitig</h3>
			<p>Groen, sappig en helder, alsof het raam net open stond. De keuze voor wie de auto vooral schoon en wakker wil laten ruiken: na een grondige schoonmaakbeurt, in de zomer, of na een periode met hond, sport of fastfood aan boord.</p>

			<h2>Veelgestelde vragen over autogeur</h2>
			<h3>Welke autogeur blijft het langst hangen?</h3>
			<p>Dat hangt meer af van je auto dan van de geur: ventilatie, temperatuur en hoeveel je spuit bepalen het meest. Reken bij beide geuren op tot twee dagen per spraybeurt. Wat precies invloed heeft, staat in <a href="<?php echo esc_url( home_url( '/autoparfum/hoe-lang-blijft-autoparfum-hangen/' ) ); ?>">hoe lang blijft autoparfum hangen</a>.</p>
			<h3>Kan ik twee autogeuren combineren?</h3>
			<p>Liever niet door elkaar in dezelfde rit; dat wordt snel een onduidelijke mix. Wissel liever af: de ene week warm, de andere fris.<?php echo $sim_promo && $sim_duo ? ' Met de <a href="' . esc_url( $sim_duo->get_permalink() ) . '">2+1 set</a> heb je beide geuren in huis.' : ''; ?> Hoe je spuit en doseert, lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-spuiten-waar-en-hoeveel/' ) ); ?>">autoparfum spuiten: waar, hoeveel en wanneer</a>.</p>
			<h3>Welke autogeur kies ik als cadeau?</h3>
			<p>Kies je voor iemand anders, ga dan af op wat die persoon thuis of in de auto al prettig vindt: Creamy Vanilla voor wie van warm houdt, Juicy Green Apple voor wie van fris houdt.<?php echo $sim_promo && $sim_duo ? ' Twijfel je, dan is de set met beide geuren de veiligste keuze.' : ''; ?> Meer tips staan in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-cadeau/' ) ); ?>">autoparfum cadeau geven</a>.</p>
			<h3>Is een autogeur veilig voor leer en stof?</h3>
			<p>Onze sprays zijn vlekvrij getest op leer, stof en kunststof. Spuit niet op schermen of glas en test bij twijfel eerst een onzichtbaar plekje.</p>
			<h3>Wat als de geur me toch niet bevalt?</h3>
			<p>Je hebt 14 dagen retourrecht. Vanaf <?php echo wp_kses_post( wc_price( $sim_drempel ) ); ?> is de verzending in Nederland gratis. Alles over gebruik, dosering en onze geuren lees je op de <a href="<?php echo esc_url( home_url( '/autoparfum/' ) ); ?>">autoparfum-pagina</a>.</p>
		</div>
	</div>
</section>

<?php get_footer(); ?>
