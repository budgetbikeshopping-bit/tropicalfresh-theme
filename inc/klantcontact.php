<?php
/**
 * Klantcontact: nieuwsbrief, voorraadmelding, afrekenen, bedankpagina,
 * 2+1-upsell in de winkelwagen en het reviewverzoek na levering.
 *
 * Uitgangspunten:
 * - De formulieren werken ook vanaf LiteSpeed-gecachte pagina's. Gasten krijgen
 *   geen nonce in de HTML: die verloopt na 12 tot 24 uur, terwijl de cache 7 dagen
 *   leeft, en een gastnonce is voor iedereen gelijk (dus geen echte CSRF-bescherming).
 *   Misbruik remmen we af met een honeypot, een tijdsval, een limiet per IP en
 *   hoogstens een welkomstmail per adres. Ingelogde bezoekers krijgen een verse,
 *   ongecachete pagina en worden wel op de nonce getoetst.
 * - Geen pop-ups en geen nieuwe automatische marketingmails.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- algemeen ---------- */

/** Drempel voor gratis verzending (incl. btw); volgt een centrale constante als die bestaat. */
function sim_verzend_drempel() {
	return defined( 'SIM_GRATIS_VANAF' ) ? (float) SIM_GRATIS_VANAF : 17.90;
}

/** Bedrijfsgegevens voor onder aan klantmails. */
function sim_klant_bedrijfsregel() {
	return 'Tropical Fresh is een handelsnaam van Budget Bike XL B.V. · Flevoweg 6E, 2318 BZ Leiden · KvK 68069758 · btw NL857288799B01 · info@tropicalfresh.nl';
}

/** Persoonlijke afmeldlink; dezelfde hmac als de afmeldhandler in functions.php. */
function sim_klant_afmeldlink( $email ) {
	$email = strtolower( $email );
	return home_url( '/?sim_afmelden=' . rawurlencode( base64_encode( $email ) ) . '&k=' . sim_afmeld_hash( $email ) );
}

/** Staat dit adres op de afmeldlijst (suppressielijst)? */
function sim_klant_afgemeld( $email ) {
	return in_array( strtolower( $email ), (array) get_option( 'sim_afmeldingen', array() ), true );
}

/* ---------- formulieren: nieuwsbrief en voorraadmelding ---------- */

/** Toegestane formulierplekken; elke plek heeft een eigen anker: id="sim-<bron>". */
function sim_form_bronnen() {
	return array( 'nieuwsbrief', 'nieuwsbrief-artikel', 'voorraad' );
}

/** Terug naar de pagina van het formulier, met de status en een anker naar dat formulier. */
function sim_form_terug( $status, $bron ) {
	$terug = wp_get_referer();
	$terug = $terug ? strtok( $terug, '#' ) : home_url( '/' );
	wp_safe_redirect( add_query_arg( array( 'sim' => $status, 'sim_f' => $bron ), $terug ) . '#sim-' . $bron );
	exit;
}

/**
 * Hoogstens 5 inzendingen per IP per uur. De site staat niet achter een CDN of
 * proxy, dus REMOTE_ADDR is het echte bezoekers-IP. We bewaren alleen een hash.
 */
function sim_form_te_vaak() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( '' === $ip ) {
		return false;
	}
	$sleutel = 'sim_rl_' . substr( wp_hash( $ip ), 0, 24 );
	$nu      = time();
	$teller  = get_transient( $sleutel );
	if ( ! is_array( $teller ) || $nu - (int) $teller['t'] >= HOUR_IN_SECONDS ) {
		$teller = array(
			't' => $nu,
			'n' => 0,
		);
	}
	if ( (int) $teller['n'] >= 5 ) {
		return true;
	}
	$teller['n'] = (int) $teller['n'] + 1;
	set_transient( $sleutel, $teller, max( 60, HOUR_IN_SECONDS - ( $nu - (int) $teller['t'] ) ) );
	return false;
}

/** Melding bij een status: array( tekst, is_fout ), of null. */
function sim_form_melding( $status, $bron ) {
	$nieuwsbrief = 0 === strpos( $bron, 'nieuwsbrief' );
	switch ( $status ) {
		case 'ok':
			return array(
				$nieuwsbrief
					? 'Gelukt, je staat op de lijst. Nieuwe inschrijvers krijgen de code per mail; kijk ook even in je spam.'
					: 'Gelukt. We mailen je zodra deze geur er weer is.',
				false,
			);
		case 'invalid':
			return array( 'Dat e-mailadres klopt niet. Controleer het en probeer het opnieuw.', true );
		case 'opnieuw':
			return array( 'Dat ging iets te snel. Probeer het nog een keer.', true );
		case 'te-vaak':
			return array( 'Je hebt het al een paar keer geprobeerd. Probeer het over een uur opnieuw, of mail ons op info@tropicalfresh.nl.', true );
		case 'verlopen':
			return array( 'Er ging iets mis. Ververs de pagina en probeer het opnieuw.', true );
	}
	return null;
}

/** Welkomstmail met de beloofde 10%-code, met afmeldlink en bedrijfsgegevens. */
function sim_welkomstmail( $email ) {
	$tekst = "Hoi,\n\n"
		. "Welkom bij Tropical Fresh. Dit is je welkomstcode voor 10% korting op je eerste bestelling:\n\n"
		. "WELKOM10\n\n"
		. "Vul de code in bij het afrekenen op https://tropicalfresh.nl: klik daar op 'Heb je een kortingscode?'. De code werkt op het hele assortiment en is eenmalig per klant geldig.\n\n"
		. "Tot snel onderweg,\nTropical Fresh\n\n"
		. "Wil je geen mails meer van ons ontvangen? Afmelden kan via deze link:\n"
		. sim_klant_afmeldlink( $email ) . "\n\n"
		. sim_klant_bedrijfsregel();
	return wp_mail(
		$email,
		'Je 10% welkomstkorting bij Tropical Fresh',
		$tekst,
		array( 'From: Tropical Fresh <info@tropicalfresh.nl>' )
	);
}

function sim_handle_form( $type ) {
	$bron = isset( $_POST['sim_bron'] ) ? sanitize_key( wp_unslash( $_POST['sim_bron'] ) ) : '';
	if ( ! in_array( $bron, sim_form_bronnen(), true ) ) {
		$bron = 'nieuwsbrief' === $type ? 'nieuwsbrief' : 'voorraad';
	}
	// Alleen ingelogd: die pagina komt niet uit de publieke cache, dus de nonce is vers.
	if ( is_user_logged_in() && ( ! isset( $_POST['sim_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sim_nonce'] ) ), 'sim_form' ) ) ) {
		sim_form_terug( 'verlopen', $bron );
	}
	// Honeypot: een bot krijgt een nepbevestiging; er wordt niets opgeslagen of gemaild.
	if ( ! empty( $_POST['sim_website'] ) ) {
		sim_form_terug( 'ok', $bron );
	}
	// Tijdsval: het script onderaan vult sim_t met de seconden sinds het laden van de pagina.
	$sim_t = isset( $_POST['sim_t'] ) ? sanitize_text_field( wp_unslash( $_POST['sim_t'] ) ) : '';
	if ( '' !== $sim_t && (int) $sim_t < 2 ) {
		sim_form_terug( 'opnieuw', $bron );
	}
	$email = isset( $_POST['sim_email'] ) ? sanitize_email( wp_unslash( $_POST['sim_email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		sim_form_terug( 'invalid', $bron );
	}
	if ( sim_form_te_vaak() ) {
		sim_form_terug( 'te-vaak', $bron );
	}
	$sleutel = strtolower( $email );
	$extra   = isset( $_POST['sim_product'] ) ? substr( sanitize_text_field( wp_unslash( $_POST['sim_product'] ) ), 0, 120 ) : '';
	$pagina  = wp_get_referer() ?: '-';

	if ( 'nieuwsbrief' === $type ) {
		$lijst    = get_option( 'sim_nieuwsbrief_lijst', array() );
		$bekend   = isset( $lijst[ $sleutel ] );
		$afgemeld = sim_klant_afgemeld( $sleutel );
		// Wie zich eerder afmeldde, zetten we niet zelf terug op de lijst: dat besluit de eigenaar.
		if ( ! $bekend && ! $afgemeld ) {
			$lijst[ $sleutel ] = time();
			update_option( 'sim_nieuwsbrief_lijst', $lijst, false );
		}
		// Hoogstens een welkomstmail per adres, zodat niemand een vreemde inbox kan volspammen.
		$welkom      = 'sim_welkom_' . md5( $sleutel );
		$welkom_mail = '';
		if ( ! $bekend && ! get_transient( $welkom ) ) {
			set_transient( $welkom, time(), 30 * DAY_IN_SECONDS );
			// Mislukt het versturen, dan staat dat in de adminmail: een nieuwe poging stuurt de code niet opnieuw.
			$welkom_mail = sim_welkomstmail( $email )
				? 'De welkomstcode is verstuurd.'
				: 'Let op: de welkomstmail kon niet worden verstuurd. Stuur de code WELKOM10 zelf.';
		} elseif ( ! $bekend ) {
			$welkom_mail = 'De welkomstcode ging de afgelopen 30 dagen al naar dit adres en is niet opnieuw verstuurd.';
		}
		if ( ! $bekend ) {
			wp_mail(
				get_option( 'admin_email' ),
				'[Tropical Fresh] Nieuwsbrief-aanmelding via de site',
				"E-mail: {$email}\nType: nieuwsbrief\nPagina: {$pagina}\n{$welkom_mail}"
					. ( $afgemeld ? "\nLet op: dit adres staat op de afmeldlijst en is daarom niet op de nieuwsbrieflijst gezet." : '' )
			);
		}
	} else {
		$bis    = (array) get_option( 'sim_bis_lijst', array() );
		$dubbel = false;
		foreach ( $bis as $sim_rij ) {
			if ( isset( $sim_rij['email'], $sim_rij['product'] ) && $sim_rij['email'] === $sleutel && $sim_rij['product'] === $extra ) {
				$dubbel = true;
				break;
			}
		}
		if ( ! $dubbel ) {
			$bis[] = array(
				'email'   => $sleutel,
				'product' => $extra,
				'tijd'    => time(),
			);
			update_option( 'sim_bis_lijst', $bis, false );
			wp_mail(
				get_option( 'admin_email' ),
				'[Tropical Fresh] Voorraadmelding-aanvraag: ' . $extra,
				"E-mail: {$email}\nType: voorraad\nProduct: {$extra}\nPagina: {$pagina}"
			);
		}
	}
	sim_form_terug( 'ok', $bron );
}

add_action( 'admin_post_sim_newsletter', fn() => sim_handle_form( 'nieuwsbrief' ) );
add_action( 'admin_post_nopriv_sim_newsletter', fn() => sim_handle_form( 'nieuwsbrief' ) );
add_action( 'admin_post_sim_stock', fn() => sim_handle_form( 'voorraad' ) );
add_action( 'admin_post_nopriv_sim_stock', fn() => sim_handle_form( 'voorraad' ) );

/**
 * Herbruikbaar e-mailformulier. $bron bepaalt het anker en welke melding hier
 * hoort; zonder $bron volgt hij uit de actie (zo blijven bestaande aanroepen werken).
 */
function sim_email_form( $action, $button, $product_name = '', $bron = '' ) {
	if ( ! in_array( $bron, sim_form_bronnen(), true ) ) {
		$bron = 'sim_stock' === $action ? 'voorraad' : 'nieuwsbrief';
	}
	$GLOBALS['sim_form_getoond'] = true;

	$status  = isset( $_GET['sim'] ) ? sanitize_key( wp_unslash( $_GET['sim'] ) ) : '';
	$voor    = isset( $_GET['sim_f'] ) ? sanitize_key( wp_unslash( $_GET['sim_f'] ) ) : 'nieuwsbrief';
	$melding = ( $status && $voor === $bron ) ? sim_form_melding( $status, $bron ) : null;
	$id      = 'sim-' . $bron;
	ob_start();
	?>
	<form class="bis" id="<?php echo esc_attr( $id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<input type="hidden" name="sim_bron" value="<?php echo esc_attr( $bron ); ?>">
		<input type="hidden" name="sim_t" value="">
		<?php if ( is_user_logged_in() ) : ?><input type="hidden" name="sim_nonce" value="<?php echo esc_attr( wp_create_nonce( 'sim_form' ) ); ?>"><?php endif; ?>
		<?php if ( $product_name ) : ?><input type="hidden" name="sim_product" value="<?php echo esc_attr( $product_name ); ?>"><?php endif; ?>
		<input type="text" name="sim_website" value="" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">
		<input type="email" name="sim_email" placeholder="je@email.nl" aria-label="E-mailadres" autocomplete="email" required<?php echo $melding ? ' aria-describedby="' . esc_attr( $id . '-melding' ) . '"' : ''; ?><?php echo 'invalid' === $status && $melding ? ' aria-invalid="true"' : ''; ?>>
		<button class="btn btn-primary" type="submit"><?php echo esc_html( $button ); ?></button>
		<?php if ( $melding ) : ?>
			<p class="sim-melding<?php echo $melding[1] ? ' is-fout' : ''; ?>" id="<?php echo esc_attr( $id . '-melding' ); ?>" role="<?php echo $melding[1] ? 'alert' : 'status'; ?>"><?php echo esc_html( $melding[0] ); ?></p>
		<?php endif; ?>
		<?php if ( 'voorraad' === $bron ) : ?>
			<p class="sim-privacy">Meer over je gegevens lees je in ons <a href="<?php echo esc_url( home_url( '/privacybeleid/' ) ); ?>">privacybeleid</a>.</p>
		<?php else : ?>
			<p class="sim-privacy">Afmelden kan altijd, via de link in onze mails of via info@tropicalfresh.nl. Lees ook ons <a href="<?php echo esc_url( home_url( '/privacybeleid/' ) ); ?>">privacybeleid</a>.</p>
		<?php endif; ?>
	</form>
	<?php
	return ob_get_clean();
}

/** Tijdsval: vul bij verzenden het aantal seconden sinds het laden in (alleen als er een formulier op de pagina staat). */
add_action( 'wp_footer', function () {
	if ( empty( $GLOBALS['sim_form_getoond'] ) ) {
		return;
	}
	?>
	<script>
	document.addEventListener('submit', function (e) {
		var f = e.target;
		if (!f || !f.matches || !f.matches('form.bis')) return;
		var t = f.querySelector('input[name="sim_t"]');
		if (t && window.performance) t.value = Math.floor(performance.now() / 1000);
	}, true);
	</script>
	<?php
}, 50 );

/**
 * Nieuwsbriefblok met het 10%-aanbod als kop. 'footer' is de brede donkere strook
 * boven de footer; 'artikel' is een rustig licht blok onder kennisartikelen.
 * Bewust geen pop-up.
 */
function sim_nieuwsbrief_blok( $variant = 'footer' ) {
	// Staat het blok al onder het artikel, dan niet nog eens hetzelfde aanbod vlak erna in de footerstrook.
	if ( 'artikel' !== $variant && ! empty( $GLOBALS['sim_nl_artikel_getoond'] ) ) {
		return '';
	}
	if ( 'artikel' === $variant ) {
		$GLOBALS['sim_nl_artikel_getoond'] = true;
	}
	$sub = 'Laat je e-mailadres achter en je ontvangt de code per mail. Daarna af en toe nieuws over onze geuren en aanbiedingen.';
	ob_start();
	if ( 'artikel' === $variant ) :
		?>
		<aside class="nl-artikel" aria-labelledby="nl-artikel-kop">
			<span class="mono-label">Nieuwsbrief</span>
			<h2 id="nl-artikel-kop">10% korting op je eerste bestelling.</h2>
			<p class="nl-sub"><?php echo esc_html( $sub ); ?></p>
			<?php echo sim_email_form( 'sim_newsletter', 'Stuur mij de code', '', 'nieuwsbrief-artikel' ); // phpcs:ignore ?>
		</aside>
		<?php
	else :
		?>
		<section class="newsletter" aria-labelledby="nl-kop">
			<div class="wrap inner">
				<div>
					<span class="mono-label">Nieuwsbrief</span>
					<h2 id="nl-kop">10% korting op je eerste bestelling.</h2>
					<p class="nl-sub"><?php echo esc_html( $sub ); ?></p>
				</div>
				<?php echo sim_email_form( 'sim_newsletter', 'Stuur mij de code' ); // phpcs:ignore ?>
			</div>
		</section>
		<?php
	endif;
	return ob_get_clean();
}

/** Inschrijfblok onder de kennisartikelen van /autoparfum/ (kinderpagina's, via page.php). */
add_action( 'sim_na_artikel', function () {
	if ( ! is_page() ) {
		return;
	}
	$hub = get_page_by_path( 'autoparfum' );
	if ( ! $hub || (int) $hub->ID !== (int) wp_get_post_parent_id( get_queried_object_id() ) ) {
		return;
	}
	echo sim_nieuwsbrief_blok( 'artikel' ); // phpcs:ignore
} );

/* ---------- winkelwagen: de 2+1 met een klik compleet ---------- */

/**
 * /?sim_add=apple of /?sim_add=vanilla voegt 1 flesje toe en stuurt door naar de
 * winkelwagen (post/redirect/get zoals sim_duo), zodat verversen niets dubbel toevoegt.
 */
add_action( 'template_redirect', function () {
	if ( empty( $_GET['sim_add'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$wie = sanitize_key( wp_unslash( $_GET['sim_add'] ) );
	$ids = sim_promo_ids();
	if ( ! empty( $ids[ $wie ] ) ) {
		WC()->cart->add_to_cart( $ids[ $wie ], 1 );
	}
	// Deze doorverwijzing hoort nooit in de paginacache.
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'sim_add voegt toe aan de winkelwagen' );
	wp_safe_redirect( wc_get_cart_url() );
	exit;
} );

/**
 * 2+1-melding boven de winkelwagen, met een knop die het ontbrekende flesje direct
 * toevoegt. Belooft alleen wat sim_promo_reconcile echt geeft: een gratis Juicy
 * Green Apple, en alleen als de voorraad dat toelaat.
 */
function sim_winkelwagen_2plus1() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! sim_promo_active() ) {
		return;
	}
	$ids    = sim_promo_ids();
	$v      = 0;
	$a      = 0;
	$gratis = 0;
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( ! empty( $item['sim_gratis'] ) ) {
			$gratis += (int) $item['quantity'];
			continue;
		}
		if ( (int) $item['product_id'] === $ids['vanilla'] ) {
			$v += (int) $item['quantity'];
		}
		if ( (int) $item['product_id'] === $ids['apple'] ) {
			$a += (int) $item['quantity'];
		}
	}
	if ( $gratis > 0 ) {
		$txt = 1 === $gratis
			? 'Je 2+1 set is compleet: de gratis Juicy Green Apple zit erbij.'
			: sprintf( 'Je 2+1 sets zijn compleet: de %d gratis Juicy Green Apples zitten erbij.', $gratis );
		echo '<div class="promo-note is-actief">' . esc_html( $txt ) . '</div>';
		return;
	}
	if ( $v > 0 && $a > 0 ) {
		return; // al gecombineerd maar geen gratis regel: dan is er geen voorraad voor, dus niets beloven
	}

	// Is er na toevoegen nog een Juicy Green Apple over om weg te geven? (zelfde regel als sim_promo_reconcile)
	$apple = wc_get_product( $ids['apple'] );
	$vrij  = function ( $betaalde_apples ) use ( $apple ) {
		return $apple && ( ! $apple->managing_stock() || (int) $apple->get_stock_quantity() - $betaalde_apples >= 1 );
	};

	if ( 0 === $v && 0 === $a ) {
		if ( $vrij( 1 ) ) {
			printf(
				'<div class="promo-note"><p>Combineer Creamy Vanilla met Juicy Green Apple in de 2+1 set: je krijgt er een extra Juicy Green Apple gratis bij.</p><a class="button" rel="nofollow" href="%s">Voeg de 2+1 set toe</a></div>',
				esc_url( home_url( '/?sim_duo=1' ) )
			);
		}
		return;
	}

	$wie   = $v > 0 ? 'apple' : 'vanilla';
	$naam  = 'apple' === $wie ? 'Juicy Green Apple' : 'Creamy Vanilla';
	$ander = wc_get_product( $ids[ $wie ] );
	if ( ! $ander || ! $ander->is_purchasable() || ! $ander->is_in_stock() || ! $vrij( 'apple' === $wie ? $a + 1 : $a ) ) {
		return;
	}

	$cart    = WC()->cart;
	$prijs   = (float) wc_get_price_to_display( $ander );
	$sub     = (float) $cart->get_displayed_subtotal(); // zelfde maatstaf als de verzendbalk
	$drempel = sim_verzend_drempel();
	$tekst   = sprintf( 'Maak er de 2+1 set van: voeg %s toe en je krijgt er een extra Juicy Green Apple gratis bij.', esc_html( $naam ) );
	// Het voordeel alleen voorrekenen als het klopt: onder de drempel nu, erboven na toevoegen, zonder kortingscode.
	if ( $sub < $drempel && $sub + $prijs >= $drempel && ! $cart->get_applied_coupons() ) {
		$verzending = (float) $cart->get_shipping_total() + (float) $cart->get_shipping_tax();
		$tekst      = ( $verzending > 0 ? sprintf( 'Nu %s inclusief verzending. ', wc_price( $cart->get_total( 'edit' ) ) ) : '' )
			. sprintf(
				'Maak er de 2+1 set van: voeg %1$s toe voor %2$s en je krijgt er een extra Juicy Green Apple gratis bij. Je totaal wordt dan %3$s, met gratis verzending.',
				esc_html( $naam ),
				wc_price( $prijs ),
				wc_price( $sub + $prijs )
			);
	}
	printf(
		'<div class="promo-note"><p>%s</p><a class="button" rel="nofollow" href="%s">%s</a></div>',
		wp_kses_post( $tekst ),
		esc_url( home_url( '/?sim_add=' . $wie ) ),
		esc_html( 'Voeg ' . $naam . ' toe' )
	);
}
add_action( 'woocommerce_before_cart', 'sim_winkelwagen_2plus1', 15 );

/* ---------- afrekenen ---------- */

/** Minder velden: geen bedrijfsnaam en geen adresregel 2; het e-mailadres komt bovenaan. */
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	foreach ( array( 'billing', 'shipping' ) as $sim_groep ) {
		unset( $fields[ $sim_groep ][ $sim_groep . '_company' ], $fields[ $sim_groep ][ $sim_groep . '_address_2' ] );
	}
	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['priority'] = 5;
	}
	return $fields;
}, 20 );

/** Geen bestelnotities: voor een flesje autoparfum overbodig. */
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

/** Provincie is in Nederland niet nodig voor de bezorging. */
add_filter( 'woocommerce_get_country_locale', function ( $locale ) {
	$locale['NL']['state'] = array_merge(
		isset( $locale['NL']['state'] ) ? (array) $locale['NL']['state'] : array(),
		array(
			'required' => false,
			'hidden'   => true,
		)
	);
	return $locale;
} );

/** Knoptekst die zegt wat er gebeurt: bestellen met betaalverplichting. */
add_filter( 'woocommerce_order_button_text', fn() => 'Bestellen en betalen' );

/** Kortingscodeveld: zelfde woord als in de welkomstmail en het nieuwsbriefblok. */
add_filter( 'gettext', function ( $vertaling, $tekst, $domein ) {
	if ( 'woocommerce' !== $domein ) {
		return $vertaling;
	}
	static $sim_map = array(
		'Have a coupon?'                                   => 'Heb je een kortingscode?',
		'Click here to enter your code'                     => 'Vul hem hier in',
		'Enter your coupon code'                            => 'Vul je kortingscode in',
		'If you have a coupon code, please apply it below.' => 'Vul je kortingscode hieronder in.',
		'Coupon:'                                           => 'Kortingscode:',
		'Coupon code'                                       => 'Kortingscode',
		'Apply coupon'                                      => 'Code toepassen',
	);
	return $sim_map[ $tekst ] ?? $vertaling;
}, 10, 3 );

/**
 * De privacylink in de afrekentekst was leeg: de ingestelde privacypagina bestaat niet
 * meer. Val terug op /privacybeleid/ zolang Instellingen > Privacy niet goed staat.
 */
add_filter( 'woocommerce_privacy_policy_page_id', function ( $id ) {
	if ( $id && 'publish' === get_post_status( $id ) ) {
		return $id;
	}
	$pagina = get_page_by_path( 'privacybeleid' );
	return ( $pagina && 'publish' === $pagina->post_status ) ? (int) $pagina->ID : $id;
} );

/** Tekst van de nieuwsbrieftoestemming. Bij een wijziging het versienummer ophogen: hij wordt bij de order bewaard. */
function sim_optin_tekst() {
	return 'v1: Ja, mail me af en toe over nieuwe geuren en aanbiedingen. Afmelden kan altijd.';
}

/** Opt-in boven de bestelknop: niet vooraf aangevinkt, niet verplicht. */
add_action( 'woocommerce_review_order_before_submit', function () {
	// WooCommerce ververst dit blok bij elke adreswijziging; neem dan de eigen keuze van de klant over.
	$aan = false;
	if ( isset( $_POST['post_data'] ) && is_string( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		parse_str( wp_unslash( $_POST['post_data'] ), $sim_post ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$aan = ! empty( $sim_post['sim_nieuwsbrief'] );
	}
	?>
	<p class="form-row sim-optin">
		<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox" for="sim_nieuwsbrief">
			<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="sim_nieuwsbrief" id="sim_nieuwsbrief" value="1"<?php checked( $aan ); ?>>
			<span>Ja, mail me af en toe over nieuwe geuren en aanbiedingen. Afmelden kan altijd. Zie ons <a href="<?php echo esc_url( home_url( '/privacybeleid/' ) ); ?>" target="_blank" rel="noopener">privacybeleid</a>.</span>
		</label>
	</p>
	<?php
} );

/** Bewijs van toestemming bij de order: tijdstip en de getoonde tekst. */
add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	if ( empty( $_POST['sim_nieuwsbrief'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce controleerde de checkout-nonce al
		return;
	}
	$order->update_meta_data( '_sim_nieuwsbrief', time() );
	$order->update_meta_data( '_sim_nieuwsbrief_tekst', sim_optin_tekst() );
} );

/** Met toestemming op de nieuwsbrieflijst; nooit een adres van de afmeldlijst. Er gaat geen mail uit. */
add_action( 'woocommerce_checkout_order_processed', function ( $order_id, $data = array(), $order = null ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
	if ( ! $order || ! $order->get_meta( '_sim_nieuwsbrief' ) ) {
		return;
	}
	$email = strtolower( $order->get_billing_email() );
	if ( ! is_email( $email ) || sim_klant_afgemeld( $email ) ) {
		return;
	}
	$lijst = get_option( 'sim_nieuwsbrief_lijst', array() );
	if ( ! isset( $lijst[ $email ] ) ) {
		$lijst[ $email ] = time();
		update_option( 'sim_nieuwsbrief_lijst', $lijst, false );
	}
}, 10, 3 );

/* ---------- bedankpagina ---------- */

/** Volgende stap na de bestelling: wat er nu gebeurt, en waar je Tropical Fresh kunt volgen. */
function sim_bedankt_blok( $order_id ) {
	$order = $order_id ? wc_get_order( $order_id ) : false;
	if ( ! $order || $order->has_status( array( 'failed', 'cancelled', 'refunded' ) ) ) {
		return;
	}
	$betaald = $order->is_paid() || $order->has_status( 'on-hold' );
	?>
	<section class="sim-bedankt" aria-labelledby="sim-bedankt-kop">
		<h2 id="sim-bedankt-kop">Wat gebeurt er nu?</h2>
		<ol class="sim-stappen">
			<li>
				<b><?php echo $betaald ? 'Bevestiging per mail.' : 'Bevestiging zodra je betaling binnen is.'; ?></b>
				We mailen je orderbevestiging naar <?php echo esc_html( $order->get_billing_email() ); ?>. Niets ontvangen? Kijk ook even in je spam.
			</li>
			<li>
				<b>Inpakken en versturen.</b>
				We pakken je bestelling in en versturen hem naar het adres dat je opgaf. De levertijd is doorgaans 2 tot 4 werkdagen.
			</li>
			<li>
				<b>Vragen?</b>
				Mail ons op <a href="mailto:info@tropicalfresh.nl">info@tropicalfresh.nl</a> en noem je bestelnummer <?php echo esc_html( $order->get_order_number() ); ?>.
			</li>
		</ol>
		<div class="sim-volg">
			<p><b>Volg ons.</b> Tropical Fresh zit op TikTok en Instagram.</p>
			<div class="sim-volg-knoppen">
				<a href="https://www.tiktok.com/@tropicalfresh_nl" target="_blank" rel="noopener">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-2.59-2.59c.27 0 .53.04.78.12V9.77a5.76 5.76 0 0 0-.78-.05 5.69 5.69 0 1 0 5.69 5.68V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.3 4.3 0 0 1-3.25-1.48z"/></svg>
					TikTok
				</a>
				<a href="https://www.instagram.com/tropicalfresh.nl/" target="_blank" rel="noopener">
					<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor" stroke="none"/></svg>
					Instagram
				</a>
			</div>
			<p class="sim-volg-yt">En op YouTube: <a href="https://www.youtube.com/channel/UCqnlCEmGxS0Z-6bOJiOKSmA" target="_blank" rel="noopener">kanaal Tropical Fresh</a>.</p>
		</div>
	</section>
	<?php
}
add_action( 'woocommerce_thankyou', 'sim_bedankt_blok', 5 );

/* ---------- reviewverzoek na levering ---------- */

/**
 * Plan 7 dagen na Voltooid een reviewverzoek in. Bewust niet op 'In behandeling':
 * dat betekent betaald, niet geleverd. Zet een order dus bij verzending op Voltooid.
 */
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
	if ( ! wp_next_scheduled( 'sim_review_request', array( $order_id ) ) ) {
		wp_schedule_single_event( time() + 7 * DAY_IN_SECONDS, 'sim_review_request', array( $order_id ) );
	}
} );

add_action( 'sim_review_request', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->get_billing_email() || $order->get_meta( '_sim_review_verstuurd' ) ) {
		return;
	}
	$email = $order->get_billing_email();
	if ( sim_klant_afgemeld( $email ) ) {
		return;
	}
	$regels = array();
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_sim_gratis' ) ) {
			continue; // het gratis 2+1-flesje staat al als betaalde regel in de mail
		}
		$p = $item->get_product();
		if ( ! $p ) {
			continue;
		}
		$pid = $p->get_parent_id() ? $p->get_parent_id() : $p->get_id();
		if ( isset( $regels[ $pid ] ) ) {
			continue;
		}
		// Persoonlijke link: opent het formulier ook voor wie als gast bestelde (zie sim_review_order).
		$regels[ $pid ] = '- ' . $p->get_name() . ': ' . sim_review_link( $order, $pid );
	}
	if ( ! $regels ) {
		return;
	}
	$voornaam  = trim( (string) $order->get_billing_first_name() );
	$verstuurd = wp_mail(
		$email,
		'Hoe bevalt je Tropical Fresh?',
		'Hoi' . ( $voornaam ? ' ' . $voornaam : '' ) . ",\n\n"
			. "Je bestelling is nu ruim een week binnen. Hoe ruikt je auto?\n\n"
			. "Een korte review helpt ons enorm en duurt een minuutje:\n"
			. implode( "\n", $regels ) . "\n\n"
			. "Dank je wel en tot de volgende rit,\nTropical Fresh\ntropicalfresh.nl\n\n"
			. "Wil je geen mails meer van ons ontvangen? Afmelden kan via deze link:\n"
			. sim_klant_afmeldlink( $email ) . "\n\n"
			. sim_klant_bedrijfsregel(),
		array( 'From: Tropical Fresh <info@tropicalfresh.nl>' )
	);
	if ( $verstuurd ) {
		$order->update_meta_data( '_sim_review_verstuurd', time() );
		$order->save();
	}
} );

/*
 * Reviewtoegang voor gastkopers. De winkel staat op "alleen geverifieerde eigenaren"
 * en WooCommerce herkent een koper alleen als hij is ingelogd. Wie als gast bestelde,
 * zag na de reviewmail dus alleen "Enkel ingelogde klanten die dit product gekocht
 * hebben" en geen formulier. De link in de reviewmail draagt daarom een hmac op order
 * en e-mailadres. Is die geldig, is de order voltooid en zit het product in die order,
 * dan mag deze bezoeker voor dit ene product een review schrijven. De instelling zelf
 * blijft staan: zonder geldige sleutel verandert er niets.
 */

/** Sleutel voor de persoonlijke reviewlink. */
function sim_review_sleutel( $order_id, $email ) {
	return substr( hash_hmac( 'sha256', 'review|' . (int) $order_id . '|' . strtolower( (string) $email ), wp_salt( 'auth' ) ), 0, 20 );
}

/** Persoonlijke reviewlink uit de mail; #review_form klapt de reviews open en scrolt naar het formulier. */
function sim_review_link( $order, $product_id ) {
	return add_query_arg(
		array(
			'sim_rv' => (int) $order->get_id(),
			'sim_rk' => sim_review_sleutel( $order->get_id(), $order->get_billing_email() ),
		),
		get_permalink( $product_id )
	) . '#review_form';
}

/** Parameter van de reviewlink: uit het verzonden formulier, anders uit de url. */
function sim_review_param( $naam ) {
	$waarde = isset( $_POST[ $naam ] ) ? $_POST[ $naam ] : ( isset( $_GET[ $naam ] ) ? $_GET[ $naam ] : '' ); // phpcs:ignore WordPress.Security -- gecontroleerd met de hmac in sim_review_order
	return is_scalar( $waarde ) ? sanitize_key( wp_unslash( (string) $waarde ) ) : '';
}

/** Over welk product gaat dit verzoek: het verzonden reviewformulier of de productpagina. */
function sim_review_product_id() {
	if ( isset( $_POST['comment_post_ID'] ) && is_scalar( $_POST['comment_post_ID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return absint( $_POST['comment_post_ID'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
	return ( did_action( 'wp' ) && function_exists( 'is_product' ) && is_product() ) ? (int) get_queried_object_id() : 0;
}

/** De order achter een geldige reviewlink voor dit product, anders null. */
function sim_review_order( $product_id ) {
	static $sim_gezien = array();
	$product_id = (int) $product_id;
	if ( array_key_exists( $product_id, $sim_gezien ) ) {
		return $sim_gezien[ $product_id ];
	}
	$sim_gezien[ $product_id ] = null;
	$order_id = absint( sim_review_param( 'sim_rv' ) );
	$sleutel  = sim_review_param( 'sim_rk' );
	if ( ! $product_id || ! $order_id || '' === $sleutel || ! function_exists( 'wc_get_order' ) ) {
		return null;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->has_status( 'completed' ) || ! $order->get_billing_email() ) {
		return null;
	}
	if ( ! hash_equals( sim_review_sleutel( $order_id, $order->get_billing_email() ), $sleutel ) ) {
		return null;
	}
	foreach ( $order->get_items() as $item ) {
		if ( (int) $item->get_product_id() === $product_id ) {
			$sim_gezien[ $product_id ] = $order;
			break;
		}
	}
	return $sim_gezien[ $product_id ];
}

/** Persoonlijke reviewpagina nooit in de paginacache (naam en e-mailadres staan al ingevuld). */
add_action( 'template_redirect', function () {
	if ( '' === sim_review_param( 'sim_rv' ) ) {
		return;
	}
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'persoonlijke reviewlink' );
}, 1 );

/** Geldige reviewlink: voor dit verzoek en dit product geldt de koper als geverifieerd. */
add_filter( 'option_woocommerce_review_rating_verification_required', function ( $waarde ) {
	if ( 'yes' !== $waarde || '' === sim_review_param( 'sim_rv' ) ) {
		return $waarde;
	}
	return sim_review_order( sim_review_product_id() ) ? 'no' : $waarde;
} );

/** Geef de sleutel mee met het reviewformulier, zodat ook het verzenden wordt toegestaan. */
add_action( 'comment_form', function ( $post_id ) {
	$order = sim_review_order( $post_id );
	if ( ! $order ) {
		return;
	}
	printf(
		'<input type="hidden" name="sim_rv" value="%d"><input type="hidden" name="sim_rk" value="%s">',
		(int) $order->get_id(),
		esc_attr( sim_review_sleutel( $order->get_id(), $order->get_billing_email() ) )
	);
} );

/** Naam en e-mailadres van de bestelling alvast invullen; met dat adres krijgt de review het label geverifieerde koper. */
add_filter( 'wp_get_current_commenter', function ( $commenter ) {
	if ( '' === sim_review_param( 'sim_rv' ) ) {
		return $commenter;
	}
	$order = sim_review_order( sim_review_product_id() );
	if ( ! $order ) {
		return $commenter;
	}
	if ( empty( $commenter['comment_author'] ) ) {
		$commenter['comment_author'] = trim( (string) $order->get_billing_first_name() );
	}
	if ( empty( $commenter['comment_author_email'] ) ) {
		$commenter['comment_author_email'] = $order->get_billing_email();
	}
	return $commenter;
} );

/**
 * Reviewlink uit de mail (#review_form, of het oude #reviews): klap de reviews-accordeon
 * open en scrol naar het formulier. Het openklappen vuurt ook 'toggle', waarop
 * functions.php Turnstile laadt; daarom draait dit na dat script (prioriteit 100).
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
	<script>
	(function () {
		var doelen = ['reviews', 'sim-reviews', 'review_form', 'review_form_wrapper', 'respond', 'commentform', 'comments'];
		function openReviews(scrol) {
			var h = (location.hash || '').slice(1);
			if (doelen.indexOf(h) === -1) return;
			var form = document.getElementById('review_form') || document.getElementById('respond');
			var acc = document.querySelector('details#reviews, details#sim-reviews') || (form && form.closest('details'));
			if (!acc) return;
			if (!acc.open) acc.open = true;
			if (scrol) (form || acc).scrollIntoView({ block: 'start' });
		}
		openReviews(false);
		window.addEventListener('load', function () { openReviews(true); });
		window.addEventListener('hashchange', function () { openReviews(true); });
	})();
	</script>
	<?php
}, 100 );
