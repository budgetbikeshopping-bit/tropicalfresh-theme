<?php
/**
 * Scent in Motion - thema-setup.
 * Bewust dun gehouden: geen wijzigingen aan producten, orders of betaalconfiguratie.
 */

defined( 'ABSPATH' ) || exit;

define( 'SIM_VERSION', '1.5.1' );

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

/**
 * Snelheid: render-blokkerende CSS die op de pagina niets doet uit de wachtrij
 * (PSI mobiel 12 okt: ruim 750 ms aan blokkerende verzoeken). Alleen wat aantoonbaar
 * ongebruikt is; de WooCommerce-basis-CSS blijft staan en LiteSpeed-combinatie blijft uit.
 * - Blokbibliotheek en Stackable: alleen weg op templates die geen post-inhoud tonen;
 *   de blokbibliotheek ook op productpagina's waarvan de beschrijving geen blokken bevat.
 * - Contact Form 7: alleen laden op pagina's met een formulier.
 * - Adres-autocomplete: hoort bij afrekenen, account en winkelwagen.
 * - Merkstijlen: het thema toont nergens merkblokken.
 * - Express-checkout-CSS op de PDP: de bijbehorende scripts gaan daar al uit (zie verderop).
 */
function sim_toont_geen_postinhoud() {
	if ( is_front_page() || is_page( array( 'autoparfum', 'geurwijzer' ) ) ) {
		return true;
	}
	return function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() );
}

/** Staat er een Contact Form 7-formulier op deze pagina? */
function sim_pagina_heeft_cf7() {
	if ( is_page( array( 'contact', 'retourneren' ) ) ) {
		return true;
	}
	$sim_obj = get_queried_object();
	return is_singular() && $sim_obj instanceof WP_Post && false !== strpos( (string) $sim_obj->post_content, 'contact-form-7' );
}

add_action( 'wp_enqueue_scripts', function () {
	// PDP: de beschrijving is klassieke HTML; zodra er blokken in staan blijft de bibliotheek.
	$sim_pdp_zonder_blokken = function_exists( 'is_product' ) && is_product() && ! has_blocks( get_queried_object_id() );
	if ( sim_toont_geen_postinhoud() || $sim_pdp_zonder_blokken ) {
		wp_dequeue_style( 'wp-block-library' );
	}
	if ( sim_toont_geen_postinhoud() ) {
		wp_dequeue_style( 'ugb-style-css' );
		wp_dequeue_style( 'ugb-style-css-responsive' );
	}
	if ( ! sim_pagina_heeft_cf7() ) {
		wp_dequeue_style( 'contact-form-7' );
		wp_dequeue_script( 'contact-form-7' );
		wp_dequeue_script( 'swv' );
	}
	$sim_adres = function_exists( 'is_checkout' ) && ( is_checkout() || is_account_page() || is_cart() );
	if ( ! $sim_adres ) {
		wp_dequeue_style( 'wc-address-autocomplete' );
		wp_dequeue_script( 'wc-address-autocomplete' );
		wp_dequeue_script( 'wc-address-autocomplete-common' );
	}
	wp_dequeue_style( 'brands-styles' );
	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_dequeue_style( 'WCPAY_EXPRESS_CHECKOUT_ECE' );
	}
}, 1000 );

/**
 * Pijlerpagina en geurwijzer tonen prijzen en voorraad, maar vallen niet onder de
 * standaard LiteSpeed-purge bij een productwijziging. Purge ze mee zodra een product
 * of een voorraadstatus verandert, anders blijft er een oude prijs in de cache staan.
 * Zonder LiteSpeed Cache doet do_action hier niets.
 */
function sim_purge_prijspaginas() {
	foreach ( array( 'autoparfum', 'geurwijzer' ) as $sim_pad ) {
		$sim_pagina = get_page_by_path( $sim_pad );
		if ( $sim_pagina ) {
			do_action( 'litespeed_purge_post', $sim_pagina->ID );
		}
	}
}
add_action( 'woocommerce_update_product', 'sim_purge_prijspaginas' );
add_action( 'woocommerce_product_set_stock_status', 'sim_purge_prijspaginas' );

/* ---------- helpers ---------- */

/**
 * Vrijgestelde productfoto per slug; valt terug op de featured image.
 * Alle cutouts zijn 900x900: de vaste afmetingen reserveren de ruimte (geen layout
 * shift), de weergavebreedte komt uit de CSS (img.bottle krijgt height: auto).
 * Zet een template $GLOBALS['sim_eerste_kaart_eager'] op true, dan laadt het
 * eerstvolgende beeld direct (LCP) in plaats van lazy.
 */
function sim_product_visual( $product, $size = 'woocommerce_single', $eager = false ) {
	if ( ! $eager && ! empty( $GLOBALS['sim_eerste_kaart_eager'] ) ) {
		$eager                             = true;
		$GLOBALS['sim_eerste_kaart_eager'] = false;
	}
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
			'<img class="bottle" src="%s" alt="%s" width="900" height="900" %s>',
			esc_url( get_template_directory_uri() . '/assets/img/' . $map[ $slug ] ),
			esc_attr( $product->get_name() ),
			$eager ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"'
		);
	}
	if ( $product && $product->get_image_id() ) {
		$sim_attr = array( 'class' => 'bottle' );
		if ( $eager ) {
			$sim_attr['loading']       = false;
			$sim_attr['fetchpriority'] = 'high';
		}
		return wp_get_attachment_image( $product->get_image_id(), $size, false, $sim_attr );
	}
	return '';
}

/** Karakterregel per geur (alleen presentatie; productdata blijft leidend). */
function sim_product_character( $product ) {
	$map = array(
		'creamy-vanilla-autoparfum'    => 'Warm. Zacht. Vertrouwd. De geur van thuiskomen, ook onderweg.',
		'juicy-green-apple-autoparfum' => 'Fris. Helder. Energiek. Frisse lucht op bestelling.',
		'shiny-new-car-autoparfum'     => 'Strak. Modern. Herkenbaar. De geur van een nieuwe auto.',
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
		'08' => array( '08-actie-vanilla-apple-extra-apple-gratis', 1920, 1030, 429, 'Tropical Fresh 2+1: koop Creamy Vanilla en Juicy Green Apple en krijg een extra Juicy Green Apple gratis' ),
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

/**
 * Sociale kanalen van Tropical Fresh: een bron voor de footer, de homepage,
 * de shortcode [sim_social] en het organisatieschema (sameAs).
 * Bewust zonder volgersaantallen en zonder embeds (geen cookies van derden).
 */
function sim_sociale_kanalen() {
	return array(
		'tiktok'    => array(
			'naam'   => 'TikTok',
			'handle' => '@tropicalfresh_nl',
			'url'    => 'https://www.tiktok.com/@tropicalfresh_nl',
			'icoon'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-2.59-2.59c.27 0 .53.04.78.12V9.77a5.76 5.76 0 0 0-.78-.05 5.69 5.69 0 1 0 5.69 5.68V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.3 4.3 0 0 1-3.25-1.48z"/></svg>',
		),
		'instagram' => array(
			'naam'   => 'Instagram',
			'handle' => '@tropicalfresh.nl',
			'url'    => 'https://www.instagram.com/tropicalfresh.nl/',
			'icoon'  => '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor" stroke="none"/></svg>',
		),
		'youtube'   => array(
			'naam'   => 'YouTube',
			'handle' => 'Tropical Fresh',
			'url'    => 'https://www.youtube.com/channel/UCqnlCEmGxS0Z-6bOJiOKSmA',
			'icoon'  => '<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M23 7.5s-.22-1.56-.9-2.24c-.86-.9-1.82-.9-2.26-.96C16.7 4.08 12 4.08 12 4.08h-.01s-4.7 0-7.84.22c-.44.05-1.4.06-2.26.96C1.21 5.94 1 7.5 1 7.5S.78 9.33.78 11.16v1.66C.78 14.66 1 16.5 1 16.5s.21 1.56.89 2.24c.86.9 2 .87 2.5.97 1.81.17 7.61.22 7.61.22s4.71-.01 7.84-.23c.44-.05 1.4-.06 2.26-.96.68-.68.9-2.24.9-2.24s.22-1.84.22-3.67v-1.67C23.22 9.33 23 7.5 23 7.5zM9.68 14.93V8.86l6.08 3.05-6.08 3.02z"/></svg>',
		),
	);
}

/**
 * Lijst met sociale links: per kanaal het icoon plus de zichtbare naam, optioneel
 * met het accountnaampje erachter. $kanalen beperkt de lijst (leeg = alle kanalen).
 */
function sim_sociale_links( $class = 'sociaal', $kanalen = array(), $met_handle = false ) {
	$items = '';
	foreach ( sim_sociale_kanalen() as $sleutel => $kanaal ) {
		if ( $kanalen && ! in_array( $sleutel, $kanalen, true ) ) {
			continue;
		}
		$items .= sprintf(
			'<li><a href="%1$s" target="_blank" rel="noopener">%2$s<span class="s-naam">%3$s</span>%4$s</a></li>',
			esc_url( $kanaal['url'] ),
			$kanaal['icoon'],
			esc_html( $kanaal['naam'] ),
			$met_handle ? '<span class="s-handle">' . esc_html( $kanaal['handle'] ) . '</span>' : ''
		);
	}
	return $items ? '<ul class="' . esc_attr( $class ) . '">' . $items . '</ul>' : '';
}

/** [sim_social]: de kanalen met naam, voor bijvoorbeeld de contactpagina of over ons. */
add_shortcode( 'sim_social', function () {
	return sim_sociale_links( 'sociaal', array(), true );
} );

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
	$sim_vertaal = array( 'Update cart' => 'Winkelwagen bijwerken', 'Login' => 'Inloggen', 'Log in' => 'Inloggen' );
	if ( 'woocommerce' === $domain && isset( $sim_vertaal[ $text ] ) ) {
		return $sim_vertaal[ $text ];
	}
	if ( 'woocommerce' === $domain && '[Remove]' === $text ) {
		return '[Verwijderen]';
	}
	return $translation;
}, 10, 3 );

/**
 * Meting en schema: Google Ads-aankoopconversie, GA4-trechter, productschema
 * (verzend- en retourinfo), Duo-prijs in de deelpreviews en noindex op de
 * categoriearchieven. Zie inc/meting.php.
 */
require_once get_template_directory() . '/inc/meting.php';

/** Vertrouwensregel direct onder de bestelknop, op het beslismoment zelf. */
add_action( 'woocommerce_review_order_after_submit', function () {
	echo '<p class="order-trust">Veilig betalen met iDEAL, Wero of kaart. 14 dagen retourrecht. Doorgaans binnen 2 tot 4 werkdagen geleverd.</p>';
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
			// Tropical Fresh is een handelsnaam van Budget Bike XL B.V. (KvK 68069758).
			$data[ $sim_key ]['legalName'] = 'Budget Bike XL B.V.';
			$data[ $sim_key ]['vatID']     = 'NL857288799B01';
			$data[ $sim_key ]['sameAs'] = array_values( wp_list_pluck( sim_sociale_kanalen(), 'url' ) );
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
	$som     = $product ? sim_duo_setprijs( $product ) : 0.0; // 0 voor alles behalve de Duo (inc/meting.php)
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

/* ---------- klantcontact ---------- */

/**
 * Nieuwsbrief, voorraadmelding, afrekenen, bedankpagina, 2+1-upsell in de
 * winkelwagen en het reviewverzoek staan samen in inc/klantcontact.php.
 */
require_once get_template_directory() . '/inc/klantcontact.php';

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
	return empty( $item['sim_gratis'] ) ? $name : $name . ' <span class="sim-gratis-tag">Gratis bij de 2+1</span>';
}, 10, 2 );
add_filter( 'woocommerce_cart_item_quantity', function ( $qty_html, $key, $item ) {
	return empty( $item['sim_gratis'] ) ? $qty_html : '<span class="sim-qty-vast">' . (int) $item['quantity'] . '</span>';
}, 10, 3 );
add_filter( 'woocommerce_cart_item_remove_link', function ( $link, $key ) {
	$item = WC()->cart ? WC()->cart->get_cart_item( $key ) : null;
	return ( $item && ! empty( $item['sim_gratis'] ) ) ? '' : $link;
}, 10, 2 );
add_filter( 'woocommerce_order_item_name', function ( $name, $item ) {
	return ( is_object( $item ) && method_exists( $item, 'get_meta' ) && $item->get_meta( '_sim_gratis' ) ) ? $name . ' (gratis bij de 2+1)' : $name;
}, 10, 2 );
add_action( 'woocommerce_checkout_create_order_line_item', function ( $line_item, $cart_item_key, $values ) {
	if ( ! empty( $values['sim_gratis'] ) ) {
		$line_item->add_meta_data( '_sim_gratis', 1, true );
	}
}, 10, 3 );

/* De 2+1-melding in de winkelwagen (met directe toevoegknop) staat in inc/klantcontact.php. */

/** Snelkoppeling: beide geuren in een klik in de winkelwagen (2+1 wordt dan automatisch toegepast). */
add_action( 'template_redirect', function () {
	if ( ! isset( $_GET['sim_duo'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	// Nooit cachen: anders serveert LiteSpeed de redirect zonder dat er iets in de winkelwagen komt.
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'sim_duo voegt de 2+1 set toe aan de winkelwagen' );
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

/* Het reviewverzoek na levering staat in inc/klantcontact.php. */

/**
 * Toevoegknoppen bouwen hun URL op de huidige request-URL. Op een gecachte pagina
 * kregen alle bezoekers zo de utm- of klik-ID's van de eerste bezoeker mee. Die
 * parameters halen we eruit; add-to-cart zelf blijft staan.
 */
add_filter( 'woocommerce_product_add_to_cart_url', function ( $url ) {
	return remove_query_arg( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id', 'gclid', 'gbraid', 'wbraid', 'fbclid', 'msclkid', 'ttclid' ), $url );
} );

/* WordPress-emoji's zijn niet nodig: scheelt een script en een stijlblok op elke pagina. */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
