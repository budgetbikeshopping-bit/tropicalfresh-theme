<?php
/**
 * Sneller afrekenen (12 nov 2026, verzoek eigenaar: "bestel traject duurt lang, ik zou het graag
 * gemakkelijker zien zodat het sneller uitcheckt").
 *
 * Gemeten op mobiel (workflow tf-kassa-sneller, 12 nov): de server is niet de rem (winkelwagen en
 * afrekenen 0,2 tot 0,6 s), het aantal handelingen wel. Een los flesje ging via een herladen
 * productpagina en de winkelwagen naar afrekenen: 4 paginawissels, 13 tikken, 10 swipes. De set ging
 * al direct naar afrekenen. Op afrekenen: 8 velden onder elkaar, iOS zoomt in bij elk veld (15px), de
 * bestelknop 2 swipes onder het laatste veld, een adreslijst die over postcode en plaats valt, en twee
 * blokkerende scripts die daar niets doen (Aanmelden met Google, Turnstile).
 *
 * Bewust ongemoeid: prijzen, setprijs 19,90, gratis verzending in NL, de 2+1-logica, het verplichte
 * voorwaardenvinkje en de nieuwsbrief als expliciete opt-in. Alle meting blijft: Meta Purchase
 * (browser + server), Google Ads-conversie en GA4 purchase op de bedankpagina, order attribution.
 * Terugdraaien: de require van dit bestand in functions.php weghalen (plus woo.css-blok 'kassa 12 nov').
 */

defined( 'ABSPATH' ) || exit;

/* ---------- 1. los flesje direct naar afrekenen, net als de set ---------- */

/** Is dit een gewone (niet-AJAX) toevoeging van een los flesje, bv. het koopformulier op de productpagina? */
function sim_kassa_direct_request() {
	if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return false;
	}
	if ( ! isset( $_REQUEST['add-to-cart'] ) || ! is_numeric( wp_unslash( $_REQUEST['add-to-cart'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return false;
	}
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( absint( wp_unslash( $_REQUEST['add-to-cart'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return $product instanceof WC_Product && $product->is_type( 'simple' );
}

/**
 * Na een geslaagde toevoeging stuurt WooCommerce (WC_Form_Handler) door naar deze URL: post/redirect/get,
 * dus verversen voegt niets dubbel toe. AJAX-knoppen (home, shop) komen hier niet langs.
 */
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url, $product = null ) {
	if ( wp_doing_ajax() || ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
		return $url;
	}
	return wc_get_checkout_url();
}, 20, 2 );

/** De knop zegt wat er gebeurt: je gaat bestellen, niet 'toevoegen aan winkelwagen' (het aantal staat ernaast). */
add_filter( 'woocommerce_product_single_add_to_cart_text', function ( $tekst, $product = null ) {
	return ( $product instanceof WC_Product && $product->is_type( 'simple' ) ) ? 'Bestellen' : $tekst;
}, 20, 2 );

/** Korte bevestiging bovenaan afrekenen, zonder knop naar de winkelwagen. */
add_filter( 'wc_add_to_cart_message_html', function ( $message, $products = array() ) {
	if ( ! sim_kassa_direct_request() ) {
		return $message;
	}
	$namen = array();
	foreach ( (array) $products as $sim_id => $sim_aantal ) {
		$sim_p = wc_get_product( $sim_id );
		if ( $sim_p ) {
			$naam    = function_exists( 'sim_short_name' ) ? sim_short_name( $sim_p ) : $sim_p->get_name();
			$namen[] = ( (int) $sim_aantal > 1 ? (int) $sim_aantal . '× ' : '' ) . $naam;
		}
	}
	return $namen ? esc_html( implode( ', ', $namen ) . ' zit in je bestelling.' ) : $message;
}, 20, 2 );

/*
 * Meta-pixel AddToCart (browser) bewaren bij een doorverwijzing. De Meta-plugin zet het browser-event
 * alleen klaar voor de volgende pagina als 'doorsturen naar winkelwagen na toevoegen' aan staat; anders
 * ging het event verloren bij elke doorverwijzing (ook al bij de set). De server-event (CAPI) kwam wel aan.
 * Alleen in de toevoeg-request zelf doet de optie alsof hij aan staat; de plugin bewaart het event dan
 * met hetzelfde event_id (ontdubbeling met CAPI) en wij tonen het op afrekenen of de winkelwagen.
 * Verandert de interne plugin-API, dan valt dit stil terug op de oude situatie (alleen CAPI).
 */
function sim_kassa_fb_uitstel() {
	static $klaar = false;
	if ( $klaar || ! class_exists( 'WC_Facebookcommerce_Utils' ) || ! is_callable( array( 'WC_Facebookcommerce_Utils', 'save_deferred_events' ) ) ) {
		return;
	}
	$klaar = true;
	add_filter( 'pre_option_woocommerce_cart_redirect_after_add', function () {
		return 'yes';
	} );
	add_action( 'shutdown', array( 'WC_Facebookcommerce_Utils', 'save_deferred_events' ), 5 );
}
add_action( 'wp_loaded', function () {
	if ( sim_kassa_direct_request() ) {
		sim_kassa_fb_uitstel(); // voor WC_Form_Handler::add_to_cart_action (wp_loaded, prioriteit 20)
	}
}, 5 );
add_action( 'template_redirect', function () {
	if ( isset( $_GET['sim_duo'] ) || ! empty( $_GET['sim_add'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		sim_kassa_fb_uitstel(); // voor de sim_duo- en sim_add-doorverwijzingen (template_redirect, prioriteit 10)
	}
}, 1 );
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) || is_order_received_page() ) {
		return;
	}
	// Staat de optie echt aan, dan doet de plugin dit zelf al.
	if ( 'yes' !== get_option( 'woocommerce_cart_redirect_after_add', 'no' ) && class_exists( 'WC_Facebookcommerce_Utils' ) && is_callable( array( 'WC_Facebookcommerce_Utils', 'print_deferred_events' ) ) ) {
		WC_Facebookcommerce_Utils::print_deferred_events();
	}
}, 20 );

/* ---------- 2. home en shop: na 'In winkelwagen' meteen 'Afrekenen' ---------- */

/** De AJAX-knoppen tonen na toevoegen een link; die gaat nu naar afrekenen in plaats van de winkelwagen. */
add_filter( 'woocommerce_get_script_data', function ( $params, $handle ) {
	if ( 'wc-add-to-cart' === $handle && is_array( $params ) && function_exists( 'is_cart' ) && ! is_cart() ) {
		$params['cart_url']       = wc_get_checkout_url();
		$params['i18n_view_cart'] = 'Afrekenen';
	}
	return $params;
}, 10, 2 );

/* ---------- 3. op afrekenen: 2+1-aanbod, wijzigen, kortere teksten ---------- */

/** Wie een los flesje direct naar afrekenen stuurt, ziet hier het 2+1-aanbod (stond eerst alleen in de winkelwagen). */
add_action( 'woocommerce_before_checkout_form', function () {
	if ( function_exists( 'sim_winkelwagen_2plus1' ) ) {
		echo '<div class="sim-promo-blok is-afrekenen">';
		sim_winkelwagen_2plus1( true );
		echo '</div>';
	}
}, 12 );

/** De winkelwagen wordt overgeslagen: aantal wijzigen kan via een link bij 'Je bestelling'. */
add_action( 'woocommerce_checkout_before_order_review_heading', function () {
	printf( '<p class="sim-wijzig"><a href="%s">Aantal wijzigen</a></p>', esc_url( wc_get_cart_url() ) );
} );

/** Kopje 'Factuurgegevens' klinkt zakelijk voor een flesje autoparfum. */
add_filter( 'gettext', function ( $vertaling, $tekst, $domein ) {
	if ( 'woocommerce' === $domein && ( 'Billing details' === $tekst || 'Billing &amp; Shipping' === $tekst ) ) {
		return 'Je gegevens';
	}
	return $vertaling;
}, 20, 3 );

/** Privacytekst boven de knop: van vier regels naar een korte zin met link. */
add_filter( 'woocommerce_get_privacy_policy_text', function ( $tekst, $type = '' ) {
	return 'checkout' === $type ? 'Hoe we met je gegevens omgaan, lees je in ons [privacy_policy].' : $tekst;
}, 10, 2 );

/*
 * De volledige algemene voorwaarden stonden verborgen in het formulier en gingen mee bij elke update
 * (ca. 8,6 kB). Het verplichte vinkje en de link blijven; de link opent de voorwaardenpagina.
 */
add_action( 'wp_loaded', function () {
	remove_action( 'woocommerce_checkout_terms_and_conditions', 'wc_terms_and_conditions_page_content', 30 );
} );

/* ---------- 4. minder in de weg ---------- */

/*
 * Adres-autocomplete uit: de lijst viel over postcode en plaats; wie daar tikte, koos een ander adres
 * (gemeten 12 nov: 'Teststraat 1' gaf vijf adressen elders in het land). Het automatisch invullen door
 * de browser of iPhone blijft werken, de velden hebben de juiste autocomplete-attributen.
 */
add_filter( 'pre_option_woocommerce_address_autocomplete_enabled', function () {
	return 'no';
} );

/*
 * Apple Pay en Google Pay vroegen verplicht een telefoonnummer omdat de instelling op 'verplicht' stond,
 * terwijl het gewone formulier het al optioneel had (functions.php). Nu overal optioneel.
 */
add_filter( 'pre_option_woocommerce_checkout_phone_field', function () {
	return 'optional';
} );

/*
 * 'Aanmelden met Google' (Site Kit) laadde op elke pagina een blokkerend script van ca. 103 kB, ook op
 * winkelwagen en afrekenen, terwijl de shop alleen gastbestellingen kent. Alleen op de accountpagina
 * (en wp-login, dat via login_init loopt) blijft het staan.
 */
add_filter( 'googlesitekit_sign-in-with-google_tag_blocked', function ( $geblokkeerd ) {
	if ( $geblokkeerd || ! doing_action( 'template_redirect' ) ) {
		return $geblokkeerd;
	}
	return ! ( function_exists( 'is_account_page' ) && is_account_page() );
} );

/** Turnstile en de CF7-meting horen alleen bij pagina's met een contactformulier. */
add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'sim_pagina_heeft_cf7' ) && ! sim_pagina_heeft_cf7() ) {
		wp_dequeue_script( 'cloudflare-turnstile' );
		wp_dequeue_script( 'googlesitekit-events-provider-contact-form-7' );
	}
}, 1001 );

/* ---------- 5. winkelwagen korter voor wie toch via de tas komt ---------- */

/** De 2+1-melding biedt Juicy Green Apple al aan; de tweede kaart 'misschien ook interessant' was dubbel. */
add_action( 'wp_loaded', function () {
	remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
} );

/** 'Bereken verzendkosten' in de winkelwagen: in NL is verzending altijd gratis, België zie je op afrekenen. */
add_filter( 'woocommerce_shipping_show_shipping_calculator', function ( $tonen ) {
	return ( function_exists( 'is_cart' ) && is_cart() ) ? false : $tonen;
} );

/** Afrekenknop direct onder de meldingen bovenaan de winkelwagen, niet pas na anderhalf scherm scrollen. */
add_action( 'woocommerce_before_cart', function () {
	if ( WC()->cart && ! WC()->cart->is_empty() ) {
		printf( '<p class="sim-snel-afrekenen"><a class="button checkout-button" href="%s">Afrekenen</a></p>', esc_url( wc_get_checkout_url() ) );
	}
}, 20 );
