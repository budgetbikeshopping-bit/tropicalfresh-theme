<?php
/**
 * Scent in Motion - thema-setup.
 * Bewust dun gehouden: geen wijzigingen aan producten, orders of betaalconfiguratie.
 */

defined( 'ABSPATH' ) || exit;

define( 'SIM_VERSION', '1.4.5' );

/* ---------- setup ---------- */

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
	register_nav_menus( array( 'primary' => 'Hoofdmenu' ) );
} );

/* ---------- assets ---------- */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'sim-fonts',
		'https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&family=Instrument+Sans:wght@400..600&family=IBM+Plex+Mono:wght@400;500;600&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'sim-main', get_template_directory_uri() . '/assets/css/main.css', array(), SIM_VERSION );
	wp_enqueue_style( 'sim-woo', get_template_directory_uri() . '/assets/css/woo.css', array( 'sim-main' ), SIM_VERSION );
	wp_enqueue_script( 'sim-lux', get_template_directory_uri() . '/assets/js/lux.js', array(), SIM_VERSION, true );
} );

/** Google Fonts niet-blokkerend laden (tekst rendert direct in fallback-font, swap daarna). */
add_filter( 'style_loader_tag', function ( $tag, $handle ) {
	if ( 'sim-fonts' === $handle && false !== strpos( $tag, "media='all'" ) ) {
		$async = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $tag );
		return $async . '<noscript>' . $tag . '</noscript>';
	}
	return $tag;
}, 10, 2 );

/** LCP-afbeelding op de homepage voorladen. */
add_action( 'wp_head', function () {
	if ( is_front_page() ) {
		printf(
			'<link rel="preload" as="image" href="%s" type="image/webp" fetchpriority="high">' . "\n",
			esc_url( get_template_directory_uri() . '/assets/img/cutout_vanilla.webp' )
		);
	}
}, 2 );

/* ---------- helpers ---------- */

/** Vrijgestelde productfoto per slug; valt terug op de featured image. */
function sim_product_visual( $product, $size = 'woocommerce_single', $eager = false ) {
	$map = array(
		'creamy-vanilla-autoparfum'    => 'cutout_vanilla.webp',
		'juicy-green-apple-autoparfum' => 'cutout_apple.webp',
		'shiny-new-car-autoparfum'     => 'cutout_newcar.webp',
		'autoparfum-valuepack'         => 'cutout_trio.webp',
		'tropical-fresh-duo'           => 'cutout_duo.webp',
	);
	$slug = $product ? $product->get_slug() : '';
	if ( isset( $map[ $slug ] ) ) {
		return sprintf(
			'<img class="bottle" src="%s" alt="%s" %s>',
			esc_url( get_template_directory_uri() . '/assets/img/' . $map[ $slug ] ),
			esc_attr( $product->get_name() ),
			$eager ? 'fetchpriority="high"' : 'loading="lazy"'
		);
	}
	if ( $product && $product->get_image_id() ) {
		return wp_get_attachment_image( $product->get_image_id(), $size, false, array( 'class' => 'bottle' ) );
	}
	return '';
}

/** Karakterregel per geur (alleen presentatie; productdata blijft leidend). */
function sim_product_character( $product ) {
	$map = array(
		'creamy-vanilla-autoparfum'    => 'Warm. Zacht. Vertrouwd. De geur van thuiskomen, ook onderweg.',
		'juicy-green-apple-autoparfum' => 'Fris. Helder. Energiek. Frisse lucht op bestelling.',
		'shiny-new-car-autoparfum'     => 'Clean. Modern. Herkenbaar. De iconische nieuwe-autogeur.',
		'autoparfum-valuepack'         => 'Alle drie de geuren in één set.',
		'tropical-fresh-duo'           => 'Vanilla en Apple samen, met een extra Apple gratis.',
	);
	$slug = $product ? $product->get_slug() : '';
	return $map[ $slug ] ?? wp_trim_words( wp_strip_all_tags( $product ? $product->get_short_description() : '' ), 14 );
}

/** Korte productnaam voor koppen: "Creamy Vanilla Autoparfum – ..." wordt "Creamy Vanilla". */
function sim_short_name( $product ) {
	$name = explode( ' Autoparfum', $product->get_name() )[0];
	return trim( preg_replace( '/[\s\x{2013}\x{2014}-]+$/u', '', $name ) );
}

/**
 * Campagne-/galerijbeeld uit de pack (assets/img/pack), met srcset en vaste afmetingen.
 * Alt-teksten volgen het SEO-plan van de pack; alle beelden zijn 60 ml-campagne.
 */
function sim_pack_img( $key, $sizes = '(max-width: 767px) 92vw, 46vw', $eager = false ) {
	$map = array(
		'01' => array( '01-creamy-vanilla-luxe-auto-60ml', 1920, 883, 368, 'Tropical Fresh Creamy Vanilla 60 ml autoparfum in een luxe auto-interieur' ),
		'02' => array( '02-creamy-vanilla-spray-in-auto-60ml', 1128, 1368, 970, 'Creamy Vanilla autoparfum spray 60 ml in gebruik in de auto' ),
		'03' => array( '03-creamy-vanilla-middenconsole-60ml', 1048, 1384, 1056, 'Tropical Fresh Creamy Vanilla autogeur 60 ml bij de middenconsole' ),
		'04' => array( '04-creamy-vanilla-studio-60ml', 1600, 1360, 680, 'Creamy Vanilla 60 ml autoparfum productfoto' ),
		'05' => array( '05-creamy-vanilla-sfeer-60ml', 936, 1360, 1162, 'Vanillebloem en vanillestokjes, het karakter van Creamy Vanilla' ),
		'06' => array( '06-juicy-green-apple-luxe-auto-60ml', 1920, 691, 288, 'Tropical Fresh Juicy Green Apple 60 ml autoparfum in een luxe auto' ),
		'07' => array( '07-juicy-green-apple-fris-60ml', 1328, 1376, 829, 'Juicy Green Apple frisse autogeur 60 ml met groene appels' ),
		'08' => array( '08-actie-vanilla-apple-extra-apple-gratis', 1920, 1030, 429, 'Tropical Fresh 2+1 actie: koop Creamy Vanilla en Juicy Green Apple en krijg een extra Juicy Green Apple gratis' ),
		'09' => array( '09-creamy-vanilla-juicy-green-apple-duo', 1080, 1360, 1007, 'Creamy Vanilla en Juicy Green Apple van Tropical Fresh, beide 60 ml' ),
	);
	if ( ! isset( $map[ $key ] ) ) {
		return '';
	}
	list( $base, $w, $h, $h800, $alt ) = $map[ $key ];
	$dir = get_template_directory_uri() . '/assets/img/pack/';
	return sprintf(
		'<img src="%1$s%2$s.webp" srcset="%1$s%2$s-800.webp 800w, %1$s%2$s.webp %3$dw" sizes="%4$s" width="%3$d" height="%5$d" alt="%6$s" %7$s decoding="async">',
		esc_url( $dir ),
		esc_attr( $base ),
		(int) $w,
		esc_attr( $sizes ),
		(int) $h,
		esc_attr( $alt ),
		$eager ? 'fetchpriority="high"' : 'loading="lazy"'
	);
}

function sim_logo_svg() {
	return '<svg viewBox="0 0 64 64" fill="none" aria-hidden="true"><g fill="currentColor"><rect x="16" y="10" width="8" height="44"/><rect x="8" y="10" width="34" height="7"/><rect x="45" y="10" width="6" height="7"/><rect x="54" y="10" width="3" height="7"/><rect x="24" y="30" width="18" height="6"/><rect x="45" y="30" width="5" height="6"/><rect x="53" y="30" width="2" height="6"/></g></svg>';
}

/* ---------- WooCommerce presentatie ---------- */

/** Winkelwagenteller live bijwerken bij AJAX toevoegen. */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$fragments['span.sim-cart-count'] = '<span class="count sim-cart-count">' . esc_html( $count ) . '</span>';
	return $fragments;
} );

/** Aantal producten per rij in de shop. */
add_filter( 'loop_shop_columns', fn() => 3 );

/** Sale-badge en standaard-title/prijs uit de kaart; onze kaart regelt de presentatie. */
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );

/**
 * PDP-summary opschonen: het template levert titel, prijs en tekst zelf.
 * We houden alleen de add-to-cart-flow (30) en de structured data (60) van WooCommerce.
 */
add_action( 'init', function () {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );
} );

/** Related-koptekst (wordt in ons template zelf gezet, maar voor de zekerheid). */
add_filter( 'woocommerce_product_related_products_heading', fn() => 'Wissel eens af.' );

/** Productafbeeldingen in de ordermails (klein, naast de productregel). */
add_filter( 'woocommerce_email_order_items_args', function ( $args ) {
	$args['show_image'] = true;
	$args['image_size'] = array( 48, 48 );
	return $args;
} );

/** 404-titel in het Nederlands (kwam Engels uit de standaard). */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( is_404() ) {
		$parts['title'] = 'Pagina niet gevonden';
	}
	return $parts;
} );

/** Telefoon is optioneel bij afrekenen: e-mail volstaat voor ordercommunicatie. */
add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['required'] = false;
	}
	return $fields;
} );

/** De voorwaarden-checkbox kwam half Engels uit WooCommerce; volledig NL. */
add_filter( 'gettext', function ( $translation, $text, $domain ) {
	if ( 'woocommerce' === $domain && 'I have read and agree to the website %s' === $text ) {
		return 'Ik heb de %s gelezen en ga ermee akkoord';
	}
	if ( 'woocommerce' === $domain && '[Remove]' === $text ) {
		return '[Verwijderen]';
	}
	return $translation;
}, 10, 3 );

/**
 * Google Ads-aankoopconversie op de bedankpagina: echt orderbedrag, ordernummer
 * als transactie-ID, en een metavlag zodat hij maar EEN keer per bestelling
 * vuurt (herladen telt niet dubbel). Verbeterde conversies: gtag hasht het
 * e-mailadres client-side voordat het naar Google gaat. De gtag-functie komt
 * van de Site Kit-tag; de shim vangt het geval dat die later laadt.
 */
add_action( 'wp_head', function () {
	if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
		return;
	}
	$order_id = absint( get_query_var( 'order-received' ) );
	$order    = $order_id ? wc_get_order( $order_id ) : false;
	$key      = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : '';
	if ( ! $order || ! $key || ! hash_equals( $order->get_order_key(), $key ) ) {
		return;
	}
	if ( $order->get_meta( '_sim_aw_conversie' ) ) {
		return;
	}
	$order->update_meta_data( '_sim_aw_conversie', (string) time() );
	$order->save();
	?>
	<script>
	window.gtag = window.gtag || function () { ( window.dataLayer = window.dataLayer || [] ).push( arguments ); };
	gtag( 'set', 'user_data', { 'email': <?php echo wp_json_encode( $order->get_billing_email() ); ?> } );
	gtag( 'event', 'conversion', {
		'send_to': 'AW-18501908223/r50mCNzY5ZUdEP_tsvZE',
		'value': <?php echo wp_json_encode( (float) $order->get_total() ); ?>,
		'currency': <?php echo wp_json_encode( $order->get_currency() ); ?>,
		'transaction_id': <?php echo wp_json_encode( (string) $order->get_order_number() ); ?>
	} );
	</script>
	<?php
}, 30 );

/** Vertrouwensregel direct onder de bestelknop, op het beslismoment zelf. */
add_action( 'woocommerce_review_order_after_submit', function () {
	echo '<p class="order-trust">Veilig betalen via iDEAL of kaart. 14 dagen retourrecht en snelle levering.</p>';
} );

/** Merk in het productschema als echt Brand-veld. */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', function ( $entity ) {
	$entity['brand'] = array(
		'@type' => 'Brand',
		'name'  => 'Tropical Fresh',
	);
	return $entity;
}, 15 );

/**
 * Organisatie-schema: het echte vestigingsadres en contactadres erbij, en de
 * onbewezen zeven-dagen-openingstijden eruit (webshop, geen fysieke openingstijden).
 */
add_filter( 'rank_math/json_ld', function ( $data ) {
	foreach ( $data as $sim_key => $sim_entity ) {
		$sim_type = isset( $sim_entity['@type'] ) ? (array) $sim_entity['@type'] : array();
		if ( array_intersect( array( 'Store', 'Organization' ), $sim_type ) ) {
			$data[ $sim_key ]['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Flevoweg 6E',
				'postalCode'      => '2318 BZ',
				'addressLocality' => 'Leiden',
				'addressCountry'  => 'NL',
			);
			$data[ $sim_key ]['email'] = 'info@tropicalfresh.nl';
			$data[ $sim_key ]['sameAs'] = array(
				'https://www.tiktok.com/@tropicalfresh_nl',
				'https://www.instagram.com/tropicalfresh.nl/',
				'https://www.youtube.com/channel/UCqnlCEmGxS0Z-6bOJiOKSmA',
			);
			unset( $data[ $sim_key ]['openingHours'], $data[ $sim_key ]['openingHoursSpecification'] );
		}
	}
	return $data;
}, 20 );

/**
 * PDP-performance: op de productpagina zelf wordt niet betaald, maar WooPayments
 * laadt daar wel Stripe.js dat op zijn beurt hCaptcha bijlaadt (ruim 3 s main-thread
 * op mobiel, PSI 46). Die scripts horen bij cart/checkout en blijven daar volledig
 * staan; op de PDP halen we ze uit de wachtrij. Turnstile beschermt alleen het
 * reviewformulier en laden we pas zodra de reviews-accordeon opengaat.
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	global $wp_scripts;
	foreach ( (array) $wp_scripts->queue as $sim_handle ) {
		if ( preg_match( '/wcpay|woocommerce.?payments|stripe|turnstile/i', $sim_handle ) ) {
			wp_dequeue_script( $sim_handle );
		}
	}
}, 999 );

/** Vangnet: Turnstile-tag die niet via de wachtrij komt op de PDP ombouwen naar lazy-load. */
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return $tag;
	}
	if ( false !== strpos( (string) $src, 'challenges.cloudflare.com/turnstile' ) ) {
		return '';
	}
	return $tag;
}, 20, 3 );

/** Turnstile pas laden wanneer de reviews-accordeon voor het eerst opengaat. */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	?>
	<script>
	(function () {
		var acc = document.getElementById('reviews');
		if (!acc) return;
		var geladen = false;
		acc.addEventListener('toggle', function () {
			if (geladen || !acc.open) return;
			geladen = true;
			var s = document.createElement('script');
			s.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
			s.async = true;
			document.head.appendChild(s);
		});
	})();
	</script>
	<?php
}, 99 );

/**
 * Duo: Rank Math's productschema toont anders de laagste kindprijs (8,95) terwijl
 * de pagina de somprijs 17,90 laat zien. Zet de schema-prijs gelijk aan wat de
 * klant echt betaalt (dynamisch berekend, zelfde som als de prijsweergave).
 */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', function ( $entity ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
	if ( ! $product || 'grouped' !== $product->get_type() || 'tropical-fresh-duo' !== $product->get_slug() ) {
		return $entity;
	}
	$som = 0.0;
	foreach ( $product->get_children() as $sim_child_id ) {
		$c = wc_get_product( $sim_child_id );
		if ( $c && $c->is_purchasable() && $c->is_in_stock() ) {
			$som += (float) wc_get_price_to_display( $c );
		}
	}
	if ( $som <= 0 || empty( $entity['offers'] ) ) {
		return $entity;
	}
	$prijs = number_format( $som, 2, '.', '' );
	$zet   = function ( &$offer ) use ( $prijs ) {
		if ( isset( $offer['price'] ) ) {
			$offer['price'] = $prijs;
		}
		if ( isset( $offer['priceSpecification']['price'] ) ) {
			$offer['priceSpecification']['price'] = $prijs;
		}
		if ( isset( $offer['lowPrice'] ) ) {
			$offer['lowPrice'] = $prijs;
		}
		if ( isset( $offer['highPrice'] ) ) {
			$offer['highPrice'] = $prijs;
		}
	};
	if ( isset( $entity['offers']['price'] ) || isset( $entity['offers']['lowPrice'] ) ) {
		$zet( $entity['offers'] );
	} elseif ( is_array( $entity['offers'] ) ) {
		foreach ( $entity['offers'] as &$sim_offer ) {
			if ( is_array( $sim_offer ) ) {
				$zet( $sim_offer );
			}
		}
		unset( $sim_offer );
	}
	return $entity;
}, 20 );

/**
 * Duo (grouped product): toon de som van de twee betaalde flesjes in plaats van
 * WooCommerce's laagste-kindprijs. Dynamisch berekend, nooit hardcoded; de derde
 * fles (extra Juicy Green Apple) komt gratis via de 2+1-cartlogica.
 */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( 'grouped' !== $product->get_type() || 'tropical-fresh-duo' !== $product->get_slug() ) {
		return $html;
	}
	$som     = 0.0;
	$som_reg = 0.0;
	foreach ( $product->get_children() as $sim_child_id ) {
		$c = wc_get_product( $sim_child_id );
		if ( ! $c || ! $c->is_purchasable() || ! $c->is_in_stock() ) {
			continue;
		}
		$som     += (float) wc_get_price_to_display( $c );
		$som_reg += (float) wc_get_price_to_display( $c, array( 'price' => $c->get_regular_price() ) );
	}
	if ( $som <= 0 ) {
		return $html;
	}
	if ( $som_reg > $som ) {
		return '<del aria-hidden="true">' . wc_price( $som_reg ) . '</del> <ins>' . wc_price( $som ) . '</ins>';
	}
	return wc_price( $som );
}, 10, 2 );

/* ---------- formulieren (nieuwsbrief + voorraadmelding) ---------- */

function sim_handle_form( $type ) {
	if ( ! isset( $_POST['sim_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['sim_nonce'] ), 'sim_form' ) ) {
		wp_safe_redirect( wp_get_referer() ?: home_url( '/' ) );
		exit;
	}
	$email = isset( $_POST['sim_email'] ) ? sanitize_email( wp_unslash( $_POST['sim_email'] ) ) : '';
	$extra = isset( $_POST['sim_product'] ) ? sanitize_text_field( wp_unslash( $_POST['sim_product'] ) ) : '';
	// honeypot
	if ( ! empty( $_POST['sim_website'] ) || ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'sim', 'invalid', wp_get_referer() ?: home_url( '/' ) ) );
		exit;
	}
	// Duurzame opslag: inschrijvingen leefden eerst alleen als notificatiemail.
	if ( 'nieuwsbrief' === $type ) {
		$sim_lijst = get_option( 'sim_nieuwsbrief_lijst', array() );
		$sim_lijst[ strtolower( $email ) ] = time();
		update_option( 'sim_nieuwsbrief_lijst', $sim_lijst, false );
	} else {
		$sim_bis   = get_option( 'sim_bis_lijst', array() );
		$sim_bis[] = array( 'email' => strtolower( $email ), 'product' => $extra, 'tijd' => time() );
		update_option( 'sim_bis_lijst', $sim_bis, false );
	}
	$subject = 'nieuwsbrief' === $type ? 'Nieuwsbrief-aanmelding via de site' : 'Voorraadmelding-aanvraag: ' . $extra;
	wp_mail(
		get_option( 'admin_email' ),
		'[Tropical Fresh] ' . $subject,
		"E-mail: {$email}\nType: {$type}\nProduct: {$extra}\nPagina: " . ( wp_get_referer() ?: '-' )
	);
	// Welkomstmail met de beloofde 10%-code naar de inschrijver zelf.
	if ( 'nieuwsbrief' === $type ) {
		wp_mail(
			$email,
			'Je 10% welkomstkorting bij Tropical Fresh',
			"Hoi,\n\nWelkom bij Tropical Fresh. Dit is je persoonlijke welkomstkorting van 10% op je eerste bestelling:\n\nWELKOM10\n\nVul de code in bij het afrekenen op https://tropicalfresh.nl. De code werkt op het hele assortiment en is eenmalig per klant geldig.\n\nTot snel onderweg,\nTropical Fresh\nFlevoweg 6E, Leiden",
			array( 'From: Tropical Fresh <info@tropicalfresh.nl>' )
		);
	}
	wp_safe_redirect( add_query_arg( 'sim', 'ok', wp_get_referer() ?: home_url( '/' ) ) );
	exit;
}
/**
 * Afmelden voor mailings: /?sim_afmelden=<base64 e-mail>&k=<hmac>. De hmac voorkomt
 * dat iemand anders je kan afmelden. Afmeldingen gaan op een suppressielijst die
 * elke mailing-run uitsluit.
 */
function sim_afmeld_hash( $email ) {
	return substr( hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'auth' ) ), 0, 16 );
}
add_action( 'template_redirect', function () {
	if ( empty( $_GET['sim_afmelden'] ) || empty( $_GET['k'] ) ) {
		return;
	}
	$email = strtolower( sanitize_email( base64_decode( wp_unslash( $_GET['sim_afmelden'] ) ) ) );
	$k     = sanitize_key( wp_unslash( $_GET['k'] ) );
	if ( ! is_email( $email ) || ! hash_equals( sim_afmeld_hash( $email ), $k ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	$af = get_option( 'sim_afmeldingen', array() );
	if ( ! in_array( $email, $af, true ) ) {
		$af[] = $email;
		update_option( 'sim_afmeldingen', $af, false );
	}
	wp_die(
		'<h1 style="font-family:sans-serif">Je bent afgemeld.</h1><p style="font-family:sans-serif">Je ontvangt geen mailings meer van Tropical Fresh. Orderbevestigingen van eigen bestellingen blijven wel komen.</p><p style="font-family:sans-serif"><a href="' . esc_url( home_url( '/' ) ) . '">Terug naar tropicalfresh.nl</a></p>',
		'Afgemeld',
		array( 'response' => 200 )
	);
} );

add_action( 'admin_post_sim_newsletter', fn() => sim_handle_form( 'nieuwsbrief' ) );
add_action( 'admin_post_nopriv_sim_newsletter', fn() => sim_handle_form( 'nieuwsbrief' ) );
add_action( 'admin_post_sim_stock', fn() => sim_handle_form( 'voorraad' ) );
add_action( 'admin_post_nopriv_sim_stock', fn() => sim_handle_form( 'voorraad' ) );

/* ---------- 2+1 actie: Vanilla + Apple = extra Apple gratis ---------- */

function sim_promo_ids() {
	static $ids = null;
	if ( null === $ids ) {
		$v   = get_page_by_path( 'creamy-vanilla-autoparfum', OBJECT, 'product' );
		$a   = get_page_by_path( 'juicy-green-apple-autoparfum', OBJECT, 'product' );
		$ids = array(
			'vanilla' => $v ? (int) $v->ID : 0,
			'apple'   => $a ? (int) $a->ID : 0,
		);
	}
	return $ids;
}

/** URL van de Duo-setpagina; daar kiest de klant bewust voor de 2+1-set. */
function sim_duo_url() {
	$p = get_page_by_path( 'tropical-fresh-duo', OBJECT, 'product' );
	return $p ? get_permalink( $p->ID ) : home_url( '/?sim_duo=1' );
}

/** Actie actief? Alleen zolang beide geuren leverbaar zijn. */
function sim_promo_active() {
	$ids = sim_promo_ids();
	if ( ! $ids['vanilla'] || ! $ids['apple'] ) {
		return false;
	}
	$v = wc_get_product( $ids['vanilla'] );
	$a = wc_get_product( $ids['apple'] );
	return $v && $a && $v->is_in_stock() && $a->is_in_stock();
}

/** Houd de gratis regel in sync met de gekwalificeerde sets (herhaalbaar per set). */
function sim_promo_reconcile() {
	static $busy = false;
	if ( $busy || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$busy = true;
	$ids = sim_promo_ids();
	$v_qty = 0;
	$a_paid = 0;
	$free_key = null;
	$free_qty = 0;
	foreach ( WC()->cart->get_cart() as $key => $item ) {
		if ( ! empty( $item['sim_gratis'] ) ) {
			$free_key = $key;
			$free_qty = (int) $item['quantity'];
			continue;
		}
		if ( (int) $item['product_id'] === $ids['vanilla'] ) {
			$v_qty += (int) $item['quantity'];
		}
		if ( (int) $item['product_id'] === $ids['apple'] ) {
			$a_paid += (int) $item['quantity'];
		}
	}
	$target = sim_promo_active() ? min( $v_qty, $a_paid ) : 0;
	// nooit meer weggeven dan er voorraad is (betaalde flesjes gaan voor)
	if ( $target > 0 ) {
		$apple = wc_get_product( $ids['apple'] );
		if ( $apple && $apple->managing_stock() ) {
			$target = min( $target, max( 0, (int) $apple->get_stock_quantity() - $a_paid ) );
		}
	}
	if ( $free_key && 0 === $target ) {
		WC()->cart->remove_cart_item( $free_key );
	} elseif ( $free_key && $free_qty !== $target ) {
		WC()->cart->set_quantity( $free_key, $target, true );
	} elseif ( ! $free_key && $target > 0 ) {
		WC()->cart->add_to_cart( $ids['apple'], $target, 0, array(), array( 'sim_gratis' => true ) );
	}
	$busy = false;
}
add_action( 'woocommerce_add_to_cart', 'sim_promo_reconcile', 20 );
add_action( 'woocommerce_cart_item_removed', 'sim_promo_reconcile', 20 );
add_action( 'woocommerce_cart_item_restored', 'sim_promo_reconcile', 20 );
add_action( 'woocommerce_after_cart_item_quantity_update', 'sim_promo_reconcile', 20 );
add_action( 'woocommerce_check_cart_items', 'sim_promo_reconcile', 5 );

/** Gratis regels kosten niets; geen andere korting eroverheen. */
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	foreach ( $cart->get_cart() as $item ) {
		if ( ! empty( $item['sim_gratis'] ) ) {
			$item['data']->set_price( 0 );
		}
	}
}, 20 );

/** Presentatie van de gratis regel. */
add_filter( 'woocommerce_cart_item_name', function ( $name, $item ) {
	return empty( $item['sim_gratis'] ) ? $name : $name . ' <span class="sim-gratis-tag">Gratis actieproduct</span>';
}, 10, 2 );
add_filter( 'woocommerce_cart_item_quantity', function ( $qty_html, $key, $item ) {
	return empty( $item['sim_gratis'] ) ? $qty_html : '<span class="sim-qty-vast">' . (int) $item['quantity'] . '</span>';
}, 10, 3 );
add_filter( 'woocommerce_cart_item_remove_link', function ( $link, $key ) {
	$item = WC()->cart ? WC()->cart->get_cart_item( $key ) : null;
	return ( $item && ! empty( $item['sim_gratis'] ) ) ? '' : $link;
}, 10, 2 );
add_filter( 'woocommerce_order_item_name', function ( $name, $item ) {
	return ( is_object( $item ) && method_exists( $item, 'get_meta' ) && $item->get_meta( '_sim_gratis' ) ) ? $name . ' (gratis actieproduct)' : $name;
}, 10, 2 );
add_action( 'woocommerce_checkout_create_order_line_item', function ( $line_item, $cart_item_key, $values ) {
	if ( ! empty( $values['sim_gratis'] ) ) {
		$line_item->add_meta_data( '_sim_gratis', 1, true );
	}
}, 10, 3 );

/** Actiemelding in de winkelwagen, premium toon. */
add_action( 'woocommerce_before_cart', function () {
	if ( ! sim_promo_active() ) {
		return;
	}
	$ids = sim_promo_ids();
	$v = 0;
	$a = 0;
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
		$txt = 1 === $gratis ? 'Je gratis Juicy Green Apple is toegevoegd.' : sprintf( 'Je %d gratis Juicy Green Apples zijn toegevoegd.', $gratis );
		echo '<div class="promo-note is-actief">' . esc_html( $txt ) . '</div>';
	} elseif ( $v > 0 && 0 === $a ) {
		printf( '<div class="promo-note">Voeg <a href="%s">Juicy Green Apple</a> toe en ontvang er nog een gratis bij.</div>', esc_url( get_permalink( $ids['apple'] ) ) );
	} elseif ( $a > 0 && 0 === $v ) {
		printf( '<div class="promo-note">Voeg <a href="%s">Creamy Vanilla</a> toe en ontvang een extra Juicy Green Apple gratis.</div>', esc_url( get_permalink( $ids['vanilla'] ) ) );
	} else {
		printf( '<div class="promo-note">Combineer <a href="%s">Creamy Vanilla</a> met <a href="%s">Juicy Green Apple</a> en ontvang een extra Apple gratis.</div>', esc_url( get_permalink( $ids['vanilla'] ) ), esc_url( get_permalink( $ids['apple'] ) ) );
	}
}, 15 );

/** Snelkoppeling: beide geuren in een klik in de winkelwagen (2+1 wordt dan automatisch toegepast). */
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['sim_duo'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$ids = sim_promo_ids();
	if ( $ids['vanilla'] && $ids['apple'] ) {
		WC()->cart->add_to_cart( $ids['vanilla'], 1 );
		WC()->cart->add_to_cart( $ids['apple'], 1 );
	}
	wp_safe_redirect( wc_get_cart_url() );
	exit;
} );

/* ---------- gratis-verzending voortgang in de winkelwagen ---------- */

add_action( 'woocommerce_before_cart', function () {
	$drempel = 17.90;
	$sub = (float) WC()->cart->get_displayed_subtotal(); // drempel telt VOOR korting, net als de verzendinstelling
	if ( $sub <= 0 ) {
		return;
	}
	if ( $sub >= $drempel ) {
		echo '<div class="ship-progress is-vrij"><span class="sp-label">Gratis verzending geregeld.</span><span class="sp-bar"><span class="sp-fill" style="width:100%"></span></span></div>';
	} else {
		$rest = $drempel - $sub;
		$pct  = max( 8, min( 96, round( $sub / $drempel * 100 ) ) );
		printf(
			'<div class="ship-progress"><span class="sp-label">Nog %s tot gratis verzending</span><span class="sp-bar"><span class="sp-fill" style="width:%d%%"></span></span></div>',
			wp_kses_post( wc_price( $rest ) ),
			(int) $pct
		);
	}
} );

/* ---------- reviewverzoek na levering ---------- */

/** Plan 7 dagen na afronding van een bestelling een reviewverzoek in. */
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
	if ( ! wp_next_scheduled( 'sim_review_request', array( $order_id ) ) ) {
		wp_schedule_single_event( time() + 7 * DAY_IN_SECONDS, 'sim_review_request', array( $order_id ) );
	}
} );

add_action( 'sim_review_request', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! $order->get_billing_email() ) {
		return;
	}
	$regels = array();
	foreach ( $order->get_items() as $item ) {
		$p = $item->get_product();
		if ( $p ) {
			$regels[] = '- ' . $p->get_name() . ': ' . $p->get_permalink() . '#reviews';
		}
	}
	if ( ! $regels ) {
		return;
	}
	wp_mail(
		$order->get_billing_email(),
		'Hoe bevalt je Tropical Fresh?',
		"Hoi " . $order->get_billing_first_name() . ",\n\nJe bestelling is nu ruim een week binnen. Hoe ruikt je auto?\n\nEen korte review helpt ons enorm en duurt een minuutje:\n" . implode( "\n", $regels ) . "\n\nDank je wel en tot de volgende rit,\nTropical Fresh\ntropicalfresh.nl",
		array( 'From: Tropical Fresh <info@tropicalfresh.nl>' )
	);
} );

/** Herbruikbaar e-mailformulier. */
function sim_email_form( $action, $button, $product_name = '' ) {
	$ok = isset( $_GET['sim'] ) && 'ok' === $_GET['sim'];
	ob_start(); ?>
	<form class="bis" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
		<input type="hidden" name="sim_nonce" value="<?php echo esc_attr( wp_create_nonce( 'sim_form' ) ); ?>">
		<?php if ( $product_name ) : ?><input type="hidden" name="sim_product" value="<?php echo esc_attr( $product_name ); ?>"><?php endif; ?>
		<input type="text" name="sim_website" value="" style="display:none" tabindex="-1" autocomplete="off" aria-hidden="true">
		<input type="email" name="sim_email" placeholder="je@email.nl" aria-label="E-mailadres" required>
		<button class="btn btn-primary" type="submit"><?php echo esc_html( $button ); ?></button>
		<?php if ( $ok ) : ?><span class="mono-label" role="status">Gelukt, je staat op de lijst.</span><?php endif; ?>
	</form>
	<?php
	return ob_get_clean();
}
