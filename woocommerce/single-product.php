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
	$sim_is_duo = 'tropical-fresh-duo' === $product->get_slug();

	/*
	 * Verzendbedragen: dezelfde waarden als de verzendinstelling in WooCommerce
	 * (gratis vanaf 17,90, daaronder 4,95). Eén plek in dit template, zodat alle
	 * teksten op de productpagina hetzelfde zeggen.
	 */
	$sim_gratis_vanaf  = (float) apply_filters( 'sim_gratis_verzending_vanaf', 17.90 );
	$sim_verzendkosten = (float) apply_filters( 'sim_verzendkosten', 4.95 );

	/*
	 * 2+1 set: Creamy Vanilla + Juicy Green Apple, de extra Juicy Green Apple komt
	 * gratis via de winkelwagenlogica. De setprijs is wat de klant echt betaalt
	 * (de twee betaalde flesjes), zonder doorgestreepte van-prijs. Drie flesjes
	 * beloven we alleen als het gratis flesje ook echt mee kan: sim_promo_reconcile
	 * begrenst het gratis flesje op de Apple-voorraad, dus beheerde voorraad moet
	 * minstens 2 zijn (1 betaald + 1 gratis).
	 */
	$sim_ids       = function_exists( 'sim_promo_ids' ) ? sim_promo_ids() : array( 'vanilla' => 0, 'apple' => 0 );
	$sim_set_v     = $sim_ids['vanilla'] ? wc_get_product( $sim_ids['vanilla'] ) : null;
	$sim_set_a     = $sim_ids['apple'] ? wc_get_product( $sim_ids['apple'] ) : null;
	$sim_set_prijs = 0.0;
	$sim_set_ok    = false;
	if ( $sim_set_v && $sim_set_a ) {
		$sim_set_prijs = (float) wc_get_price_to_display( $sim_set_v ) + (float) wc_get_price_to_display( $sim_set_a );
		$sim_set_ok    = function_exists( 'sim_promo_active' ) && sim_promo_active()
			&& $sim_set_v->is_purchasable() && $sim_set_a->is_purchasable()
			&& ( ! $sim_set_a->managing_stock() || (int) $sim_set_a->get_stock_quantity() >= 2 );
	}
	// Op centen afgerond vergelijken, net als de gratis-verzendingsregel van WooCommerce,
	// zodat een afrondingsverschil in de floats de drempel niet ongemerkt mist.
	$sim_set_gratis_verzending = round( $sim_set_prijs, 2 ) >= round( $sim_gratis_vanaf, 2 );
	$sim_set_url               = home_url( '/?sim_duo=1' );

	// Koopkeuze (1 flesje of de 2+1 set) op de productpagina's van Vanilla en Apple.
	$sim_in_promo = in_array( $product->get_id(), array( (int) $sim_ids['vanilla'], (int) $sim_ids['apple'] ), true );
	$sim_keuze    = $sim_set_ok && $sim_in_promo && $product->is_type( 'simple' )
		&& $product->is_purchasable() && $product->is_in_stock();
	$sim_los_prijs = (float) wc_get_price_to_display( $product );

	$sim_inhoud_map = array(
		'autoparfum-valuepack' => '3 &times; 60 ml',
		'tropical-fresh-duo'   => $sim_set_ok ? '3 &times; 60 ml' : '2 &times; 60 ml',
	);
	$sim_is_set = isset( $sim_inhoud_map[ $product->get_slug() ] );
	$sim_inhoud = $sim_inhoud_map[ $product->get_slug() ] ?? '60 ml';

	$sim_verzend_regel = sprintf(
		'Gratis verzending in Nederland vanaf %s, daaronder %s. België: € 7,95',
		wc_price( $sim_gratis_vanaf ),
		wc_price( $sim_verzendkosten )
	);
	?>

	<?php do_action( 'woocommerce_before_single_product' ); // notices ?>

	<nav class="crumbs wrap" aria-label="Kruimelpad">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a> &nbsp;/&nbsp;
		<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Autoparfum</a> &nbsp;/&nbsp;
		<?php echo esc_html( $sim_short ); ?>
	</nav>

	<section class="pdp wrap">
		<div class="stage<?php echo ( $sim_is_duo && $sim_set_ok ) ? ' is-set' : ''; ?>" data-tilt="9" style="--tint: <?php echo esc_attr( $sim_tint ); ?>">
			<div class="backplate"></div>
			<div class="gridlines"></div>
			<?php if ( $sim_is_duo && $sim_set_ok ) : ?>
				<?php
				// De set is drie flesjes; het duo-beeld toont er twee. Daarom hier de drie
				// losse productfoto's naast elkaar, het derde flesje gemarkeerd als gratis.
				$sim_img_dir = get_template_directory_uri() . '/assets/img/';
				?>
				<div class="tilt"><div class="float">
					<div class="set-flesjes">
						<span class="fl"><img src="<?php echo esc_url( $sim_img_dir . 'cutout_vanilla.webp?v=' . SIM_VERSION ); ?>" width="900" height="900" alt="Creamy Vanilla autoparfum 60 ml" fetchpriority="high"></span>
						<span class="fl"><img src="<?php echo esc_url( $sim_img_dir . 'cutout_apple.webp?v=' . SIM_VERSION ); ?>" width="900" height="900" alt="Juicy Green Apple autoparfum 60 ml" fetchpriority="high"></span>
						<span class="fl is-vrij"><img src="<?php echo esc_url( $sim_img_dir . 'cutout_apple.webp?v=' . SIM_VERSION ); ?>" width="900" height="900" alt="Extra Juicy Green Apple autoparfum 60 ml, gratis in de 2+1 set"><span class="vrij-tag">Gratis</span></span>
					</div>
				</div></div>
			<?php else : ?>
				<div class="tilt"><div class="float"><?php echo sim_product_visual( $product, 'woocommerce_single', true ); // phpcs:ignore ?></div></div>
			<?php endif; ?>
			<div class="shadow"></div>
			<span class="spec">TF&nbsp;/&nbsp;<?php echo esc_html( $product->get_sku() ?: '60 ML' ); ?></span>
		</div>

		<div class="pdp-info">
			<span class="mono-label">
				Autoparfum &middot; <?php echo $sim_is_set ? $sim_inhoud : '60 ml spray'; // phpcs:ignore ?> &middot;
				<?php echo $product->is_in_stock() ? 'Op voorraad' : 'Tijdelijk uitverkocht'; ?>
			</span>
			<h1><?php echo esc_html( $sim_short ); ?></h1>
			<div class="price-row">
				<span class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
				<span class="price-note">incl. btw</span>
			</div>
			<?php if ( $sim_is_duo && $sim_set_ok ) : ?>
				<p class="duo-uitleg"><b>2+1 set:</b> 3 flesjes van 60 ml, je betaalt er 2<?php echo $sim_set_gratis_verzending ? ', met gratis verzending' : ''; ?>.</p>
				<?php
				// Waarde-anker: wat dezelfde drie flesjes los kosten (Vanilla + 2x Apple, eigen losse prijzen),
				// geen doorgestreepte van-prijs.
				if ( $sim_set_ok && $sim_set_a ) :
					$sim_los_drie = $sim_set_prijs + (float) wc_get_price_to_display( $sim_set_a );
					?>
				<p class="duo-anker">Los gekocht kosten deze drie flesjes <?php echo wp_kses_post( wc_price( $sim_los_drie ) ); ?>. In de set betaal je <?php echo wp_kses_post( wc_price( $sim_set_prijs / 3 ) ); ?> per flesje.</p>
				<?php endif; ?>
			<?php endif; ?>

			<?php
			// Duo = vaste combi-set: geen losse aantallen per geur. We laten de
			// structured data staan maar vervangen het grouped-formulier door
			// een set-overzicht met een knop die de complete set toevoegt.
			if ( $sim_is_duo ) {
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			}
			ob_start();
			do_action( 'woocommerce_single_product_summary' ); // add-to-cart + structured data
			$sim_summary = ob_get_clean();
			if ( $sim_is_duo ) {
				add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
			}
			?>
			<div class="buy-block" id="koop">
				<?php if ( $sim_keuze ) : ?>
					<?php
					// Koopkeuze direct onder de prijs: 1 flesje of de 2+1 set, elk met een
					// eigen knop. Geen voorselectie; de klant kiest zelf.
					$sim_los_verzending = round( $sim_los_prijs, 2 ) < round( $sim_gratis_vanaf, 2 )
						? sprintf( '%s + %s verzending', wc_price( $sim_los_prijs ), wc_price( $sim_verzendkosten ) )
						: sprintf( '%s, gratis verzending', wc_price( $sim_los_prijs ) );
					$sim_set_inhoud = 'juicy-green-apple-autoparfum' === $product->get_slug()
						? 'Deze Juicy Green Apple en een Creamy Vanilla, plus een extra Juicy Green Apple gratis.'
						: 'Deze Creamy Vanilla en een Juicy Green Apple, plus een extra Juicy Green Apple gratis.';
					?>
					<div class="koopkeuze">
						<div class="keuze is-los">
							<p class="keuze-kop"><b>1 flesje</b><span><?php echo wp_kses_post( $sim_los_verzending ); ?></span></p>
							<?php echo $sim_summary; // phpcs:ignore -- WooCommerce-hookuitvoer (koopformulier) ?>
						</div>
						<div class="keuze is-set">
							<p class="keuze-kop"><b><span class="set-tag">2+1 set</span> 3 flesjes</b><span><?php echo wp_kses_post( wc_price( $sim_set_prijs ) ); ?><?php echo $sim_set_gratis_verzending ? ' &middot; gratis verzending' : ''; ?></span></p>
							<p class="set-inhoud"><?php echo esc_html( $sim_set_inhoud ); ?></p>
							<a class="btn btn-primary set-add" rel="nofollow" href="<?php echo esc_url( $sim_set_url ); ?>"><span>3 flesjes toevoegen &middot; <?php echo wp_kses_post( wc_price( $sim_set_prijs ) ); ?></span></a>
						</div>
					</div>
				<?php else : ?>
					<?php echo $sim_summary; // phpcs:ignore -- WooCommerce-hookuitvoer (koopformulier) ?>
				<?php endif; ?>
				<?php if ( $sim_is_duo && $product->is_in_stock() ) : ?>
					<div class="duo-set">
						<div class="set-rows">
							<span>01 &middot; Creamy Vanilla &middot; 60 ml</span>
							<span>02 &middot; Juicy Green Apple &middot; 60 ml</span>
							<?php if ( $sim_set_ok ) : ?>
								<span class="vrij">03 &middot; Juicy Green Apple &middot; 60 ml &middot; gratis</span>
							<?php endif; ?>
						</div>
						<?php // Aantal en prijs alleen in de knop als de 2+1 echt geleverd kan worden. ?>
						<a class="btn btn-primary duo-add" rel="nofollow" href="<?php echo esc_url( $sim_set_url ); ?>"><span><?php echo $sim_set_ok ? '3 flesjes toevoegen &middot; ' . wp_kses_post( wc_price( $sim_set_prijs ) ) : 'Voeg de complete set toe'; // phpcs:ignore ?></span></a>
						<?php if ( $sim_set_ok ) : ?>
							<p class="duo-set-note">Vaste combi-set. Het gratis flesje verschijnt automatisch in je winkelwagen.</p>
						<?php endif; ?>
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
			</div>
			<?php if ( $product->is_in_stock() ) : ?>
				<?php // Levertijd volgt de algemene voorwaarden letterlijk (art. 8: doorgaans 2 tot 4 werkdagen). ?>
				<ul class="koop-info">
					<li>Doorgaans binnen 2 tot 4 werkdagen geleverd</li>
					<li>Veilig betalen met iDEAL | Wero of creditcard</li>
					<li><?php echo wp_kses_post( $sim_verzend_regel ); ?></li>
					<li>14 dagen retourrecht</li>
				</ul>
				<?php
				// Echte foto's direct bij de koopknop (audit 18 okt): de bezoeker ziet meteen het echte flesje.
				$sim_koop_echt = array(
					'creamy-vanilla-autoparfum'    => array( 'e01', 'e02' ),
					'juicy-green-apple-autoparfum' => array( 'e03', 'e04' ),
					'tropical-fresh-duo'           => array( 'e05', 'e06' ),
				);
				$sim_ke = $sim_koop_echt[ $product->get_slug() ] ?? null;
				if ( $sim_ke && function_exists( 'sim_pack_img' ) ) :
					?>
				<div class="koop-echt">
					<?php foreach ( $sim_ke as $sim_ke_key ) : ?>
						<figure><?php echo sim_pack_img( $sim_ke_key, '120px' ); // phpcs:ignore ?></figure>
					<?php endforeach; ?>
					<a class="koop-echt-link" href="#van-dichtbij">Meer echte foto's</a>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $product->get_short_description() ) : ?>
				<div class="lead"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
			<?php endif; ?>

			<div class="spec-strip">
				<div class="cell"><span>Inhoud</span><b><?php echo $sim_inhoud; // phpcs:ignore ?></b></div>
				<div class="cell"><span>Werking</span><b>Tot 2 dagen per spraybeurt</b></div>
				<div class="cell"><span>Geschikt voor</span><b>Leer, stof, kunststof</b></div>
			</div>

			<div class="pdp-accs">
				<details class="acc" open>
					<summary>Beschrijving</summary>
					<div class="body"><?php the_content(); ?></div>
				</details>
				<details class="acc">
					<summary>Zo gebruik je de spray</summary>
					<div class="body">
						<p>Deze autoparfum spray werkt het best op materiaal dat de geur vasthoudt en langzaam loslaat. Spuit daarom op de vloermatten of de stof onder de stoelen, niet op schermen, glas of het dashboard in de zon.</p>
						<ol>
							<li>Houd het flesje zo'n 20 centimeter boven de mat en spuit gelijkmatig, niet op een punt.</li>
							<li>Twee tot drie sprays in totaal zijn genoeg voor een gewone personenauto: een op elke voormat, eventueel een lichte op de achterbank.</li>
							<li>Reken op tot 2 dagen geur per spraybeurt. Wordt de geur zachter, spuit dan opnieuw in plaats van meer tegelijk.</li>
						</ol>
						<p>Vlekvrij getest op leer, stof en kunststof. Bij oudere of gevoelige bekleding test je eerst een onopvallend plekje onder de stoel. Meer uitleg lees je in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-spuiten-waar-en-hoeveel/' ) ); ?>">autoparfum spuiten: waar, hoeveel en wanneer</a> en in <a href="<?php echo esc_url( home_url( '/autoparfum/autoparfum-veilig-leer-stof-kunststof/' ) ); ?>">autoparfum veilig gebruiken op leer, stof en kunststof</a>.</p>
					</div>
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
					<div class="body">Doorgaans binnen 2 tot 4 werkdagen geleverd, door heel Nederland. <?php echo wp_kses_post( $sim_verzend_regel ); ?>. Niet tevreden? Je hebt 14 dagen retourrecht na ontvangst. Aanmelden kan via de <a href="<?php echo esc_url( home_url( '/retourneren/' ) ); ?>">retourpagina</a>.</div>
				</details>
				<?php
				$sim_reviews_n = (int) $product->get_review_count();
				if ( comments_open() || $sim_reviews_n > 0 ) :
					/*
					 * Echtheid van reviews (Omnibus): de zin volgt de werkelijke instellingen,
					 * zodat hij nooit meer belooft dan de winkel doet.
					 */
					if ( 'yes' === get_option( 'woocommerce_review_rating_verification_required' ) ) {
						$sim_rv_echt = 'Alleen klanten die dit product bij ons kochten, kunnen een review plaatsen.';
					} elseif ( 'yes' === get_option( 'woocommerce_review_rating_verification_label' ) ) {
						$sim_rv_echt = 'Reviews met het label "geverifieerde eigenaar" zijn geschreven door klanten die dit product bij ons kochten. Bij andere reviews kunnen we niet controleren of de schrijver het product heeft gekocht.';
					} else {
						$sim_rv_echt = 'We controleren niet of de schrijver van een review dit product bij ons heeft gekocht.';
					}
					if ( '1' === (string) get_option( 'comment_moderation' ) ) {
						$sim_rv_echt .= ' Alle reviews worden voor plaatsing handmatig bekeken.';
					}
					// Zolang er 0 reviews zijn tonen we geen lege 'Reviews (0)'. Het blok blijft
					// wel in de pagina: lux.js opent het alsnog als iemand via een reviewlink komt.
					?>
				<details class="acc" id="reviews"<?php echo 0 === $sim_reviews_n ? ' hidden' : ''; ?>>
					<summary><?php echo $sim_reviews_n > 0 ? esc_html( sprintf( 'Reviews (%d)', $sim_reviews_n ) ) : 'Schrijf de eerste review'; ?></summary>
					<div class="body sim-reviews">
						<?php if ( $sim_reviews_n > 0 ) : ?>
							<p class="rv-echtheid"><?php echo esc_html( $sim_rv_echt ); ?></p>
						<?php endif; ?>
						<?php comments_template(); ?>
					</div>
				</details>
				<?php endif; ?>
			</div>

			<?php // Sociale kanalen tijdelijk uit (14 okt): profielen met 0-1 volgers ondermijnen vertrouwen; aan met de filter sim_toon_volgblokken. ?>
			<?php if ( apply_filters( 'sim_toon_volgblokken', false ) ) : ?>
			<div class="pdp-social">
				<span class="mono-label">Volg Tropical Fresh</span>
				<div class="ps-links">
					<a href="https://www.tiktok.com/@tropicalfresh_nl" target="_blank" rel="noopener">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-2.59-2.59c.27 0 .53.04.78.12V9.77a5.76 5.76 0 0 0-.78-.05 5.69 5.69 0 1 0 5.69 5.68V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.3 4.3 0 0 1-3.25-1.48z"/></svg>
						<span>TikTok <i>@tropicalfresh_nl</i></span>
					</a>
					<a href="https://www.instagram.com/tropicalfresh.nl/" target="_blank" rel="noopener">
						<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor" stroke="none"/></svg>
						<span>Instagram <i>@tropicalfresh.nl</i></span>
					</a>
				</div>
				<p class="ps-yt">Ook op <a href="https://www.youtube.com/channel/UCqnlCEmGxS0Z-6bOJiOKSmA" target="_blank" rel="noopener">YouTube</a>.</p>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( $product->is_in_stock() && ( $sim_is_duo || $product->is_purchasable() ) ) : ?>
	<!-- mobiele sticky-koopbalk: verschijnt zodra geen koopknop van de pagina meer in beeld is -->
	<?php if ( $sim_keuze ) : ?>
	<div class="stickybuy is-keuze">
		<?php // Zonder JavaScript werkt de link zoals voorheen; lux.js stuurt hem door het echte koopformulier. ?>
		<a class="btn btn-outline" rel="nofollow" data-sim-koop="1" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"><span>1 flesje &middot; <?php echo wp_kses_post( wc_price( $sim_los_prijs ) ); ?></span></a>
		<a class="btn btn-primary" rel="nofollow" href="<?php echo esc_url( $sim_set_url ); ?>"><span>3 flesjes &middot; <?php echo wp_kses_post( wc_price( $sim_set_prijs ) ); ?></span></a>
	</div>
	<?php else : ?>
	<div class="stickybuy">
		<div><span class="t"><?php echo esc_html( ( $sim_is_duo && $sim_set_ok ) ? '2+1 set, 3 flesjes' : $sim_short ); ?></span><span class="p price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span></div>
		<?php if ( $sim_is_duo ) : ?>
			<a class="btn btn-primary" rel="nofollow" href="<?php echo esc_url( $sim_set_url ); ?>">Toevoegen</a>
		<?php else : ?>
			<a class="btn btn-primary" rel="nofollow" data-sim-koop="" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>">Toevoegen</a>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	<?php endif; ?>

	<?php
	// Echte productfoto's per geur (eigen opnames van de flesjes, vrijgesteld op een studio-
	// achtergrond) plus de foto van de verzenddoosjes. De AI-sfeerbeelden (pack 8 sep) staan hier
	// niet meer: die hadden verminkte etiketteksten en wekten wantrouwen.
	$sim_gal = array(
		'creamy-vanilla-autoparfum'    => array( 'e01', 'e02' ),
		'juicy-green-apple-autoparfum' => array( 'e03', 'e04' ),
		'tropical-fresh-duo'           => array( 'e05', 'e06' ),
	);
	$sim_g = $sim_gal[ $product->get_slug() ] ?? null;
	if ( $sim_g && function_exists( 'sim_pack_img' ) ) :
		?>
	<section class="pdp-galerij" id="van-dichtbij">
		<div class="wrap">
			<div class="section-head">
				<span class="mono-label">Echte foto's</span>
				<h2>Van dichtbij.</h2>
			</div>
			<div class="gal-grid">
				<?php foreach ( $sim_g as $sim_g_key ) : ?>
					<figure><?php echo sim_pack_img( $sim_g_key, '(max-width: 767px) 46vw, (max-width: 1347px) 23vw, 300px' ); // phpcs:ignore ?></figure>
				<?php endforeach; ?>
				<figure class="gal-breed">
					<?php echo sim_pack_img( 'e07', '(max-width: 767px) 92vw, (max-width: 1347px) 46vw, 620px' ); // phpcs:ignore ?>
					<figcaption>Zo zitten de flesjes in het doosje.</figcaption>
				</figure>
			</div>
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
