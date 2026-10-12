<?php
/**
 * Meting en schema, ingeladen vanuit functions.php.
 *
 * - Google Ads-aankoopconversie op de bedankpagina (alleen betaalde of on-hold orders).
 * - GA4-trechter: view_item, view_cart en begin_checkout, en bruikbare
 *   add_to_cart-data voor de eigen knoppen en voor toevoegingen met een redirect.
 * - Productschema: productnaam zonder "| Tropical Fresh", verzend- en retourinfo.
 * - Duo: de setprijs (som van de betaalde flesjes) ook in Open Graph en de Slack/X-preview.
 * - Productcategorie-archieven noindex en het schema-kruimelpad naar /shop/.
 *
 * Tags op de site: de Google-tag GT-55JZBGWC (Site Kit) stuurt events zonder
 * send_to naar GA4 (G-PJTT8V18QP) en Google Ads (AW-18501908223). Site Kit meet
 * zelf alleen add_to_cart en purchase. Hier komt daarom bewust GEEN eigen
 * purchase-event bij (dat telt dubbel). De Ads-conversie gaat met send_to en een
 * eigen label uitsluitend naar de Ads-conversieactie "Aankoop".
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SIM_GRATIS_VERZENDING_VANAF' ) ) {
	// Drempel voor gratis verzending; gelijk houden met de WooCommerce-verzendzone en Merchant Center.
	define( 'SIM_GRATIS_VERZENDING_VANAF', 17.90 );
}
if ( ! defined( 'SIM_VERZENDKOSTEN' ) ) {
	// Verzendkosten onder de drempel; gelijk houden met de WooCommerce-verzendzone en Merchant Center.
	define( 'SIM_VERZENDKOSTEN', 4.95 );
}

/* ---------- helpers ---------- */

/**
 * Setprijs van de Duo: de som van de leverbare kindproducten, dus wat de klant
 * echt betaalt (de derde fles komt gratis via de 2+1-cartlogica). Zelfde som als
 * de prijsweergave in functions.php. Geeft 0 terug voor elk ander product, zodat
 * aanroepers alleen op > 0 hoeven te testen. Zonder argument: de Duo zelf.
 */
function sim_duo_setprijs( $product = null ) {
	if ( null === $product ) {
		$sim_duo_post = get_page_by_path( 'tropical-fresh-duo', OBJECT, 'product' );
		$product      = $sim_duo_post ? wc_get_product( $sim_duo_post->ID ) : null;
	} elseif ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'grouped' ) || 'tropical-fresh-duo' !== $product->get_slug() ) {
		return 0.0;
	}
	$som = 0.0;
	foreach ( $product->get_children() as $sim_kind_id ) {
		$kind = wc_get_product( $sim_kind_id );
		if ( $kind && $kind->is_purchasable() && $kind->is_in_stock() ) {
			$som += (float) wc_get_price_to_display( $kind );
		}
	}
	return $som;
}

/** De order op de bedankpagina, alleen met een geldige orderkey; anders null. */
function sim_meting_bedankorder() {
	if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
		return null;
	}
	$order_id = absint( get_query_var( 'order-received' ) );
	$order    = $order_id ? wc_get_order( $order_id ) : false;
	$key      = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $order || ! $key || ! hash_equals( $order->get_order_key(), $key ) ) {
		return null;
	}
	return $order;
}

/* ---------- Google Ads-aankoopconversie ---------- */

/**
 * Google Ads-aankoopconversie op de bedankpagina: echt orderbedrag en het
 * ordernummer als transactie-ID. Vuurt alleen bij een betaalde order
 * (processing of completed, volgens WooCommerce zelf) of een on-hold order;
 * pending en failed zijn nog geen aankoop.
 *
 * Er is bewust geen eenmalige blokkade meer. Die zette de vlag op de server al
 * voordat de tag in de browser vuurde, zodat een gemiste eerste keer nooit meer
 * binnenkwam. Google Ads negeert een herhaalde conversie met hetzelfde
 * transactie-ID binnen dezelfde conversieactie, dus herladen telt niet dubbel.
 * De metavlag _sim_aw_conversie is alleen nog een logregel (eerste keer gevuurd).
 *
 * Verbeterde conversies: gtag hasht het e-mailadres in de browser voordat het
 * naar Google gaat. De gtag-functie komt van de Site Kit-tag; de shim vangt het
 * geval dat die later laadt.
 */
add_action( 'wp_head', function () {
	$order = sim_meting_bedankorder();
	if ( ! $order || ( ! $order->is_paid() && ! $order->has_status( 'on-hold' ) ) ) {
		return;
	}
	if ( ! $order->get_meta( '_sim_aw_conversie' ) ) {
		$order->update_meta_data( '_sim_aw_conversie', (string) time() );
		$order->save();
	}
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

/* ---------- GA4-trechter ---------- */

/**
 * GA4-item in hetzelfde formaat als Site Kit: het WooCommerce-product-ID als
 * item_id (zonder het gla_-voorvoegsel van Merchant Center, zodat de
 * itemrapporten aansluiten op de add_to_cart en purchase van Site Kit), de
 * productnaam en de productcategorieen.
 */
function sim_ga_item( $product, $aantal = 1, $prijs = null ) {
	$sim_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
	$item   = array(
		'item_id'   => $sim_id,
		'item_name' => $product->get_title(),
		'price'     => round( (float) ( null === $prijs ? wc_get_price_to_display( $product ) : $prijs ), 2 ),
		'quantity'  => max( 1, (int) $aantal ),
	);
	$sim_n = 1;
	foreach ( wc_get_product_terms( $sim_id, 'product_cat', array( 'number' => 5 ) ) as $sim_cat ) {
		$item[ 1 === $sim_n ? 'item_category' : 'item_category' . $sim_n ] = $sim_cat->name;
		$sim_n++;
	}
	return $item;
}

/** Print een GA4-event met dezelfde gtag-shim als de Ads-conversie. */
function sim_ga_event( $naam, $data ) {
	printf(
		"<script>window.gtag = window.gtag || function () { ( window.dataLayer = window.dataLayer || [] ).push( arguments ); };\ngtag( 'event', %s, %s );</script>\n",
		wp_json_encode( (string) $naam ),
		wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP )
	);
}

/**
 * Items, waarde en kortingscode van de huidige winkelwagen. Prijzen zoals de
 * winkelwagen ze toont (incl. btw), na korting; de gratis 2+1-fles telt mee
 * met prijs 0. Verzendkosten zitten niet in de waarde.
 */
function sim_ga_winkelwagen_data() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return null;
	}
	$incl   = WC()->cart->display_prices_including_tax();
	$items  = array();
	$waarde = 0.0;
	foreach ( WC()->cart->get_cart() as $regel ) {
		$product = isset( $regel['data'] ) ? $regel['data'] : null;
		$aantal  = isset( $regel['quantity'] ) ? (int) $regel['quantity'] : 0;
		if ( ! $product instanceof WC_Product || $aantal < 1 ) {
			continue;
		}
		$korting = 0.0;
		if ( ! empty( $regel['sim_gratis'] ) ) {
			$prijs = 0.0;
		} elseif ( isset( $regel['line_total'], $regel['line_subtotal'] ) ) {
			$totaal  = (float) $regel['line_total'] + ( $incl ? (float) ( $regel['line_tax'] ?? 0 ) : 0.0 );
			$sub     = (float) $regel['line_subtotal'] + ( $incl ? (float) ( $regel['line_subtotal_tax'] ?? 0 ) : 0.0 );
			$prijs   = $totaal / $aantal;
			$korting = max( 0.0, ( $sub - $totaal ) / $aantal );
		} else {
			$prijs = (float) wc_get_price_to_display( $product );
		}
		$item = sim_ga_item( $product, $aantal, $prijs );
		if ( $korting >= 0.005 ) {
			$item['discount'] = round( $korting, 2 );
		}
		$items[] = $item;
		$waarde += $prijs * $aantal; // onafgerond, zodat de waarde gelijk is aan het echte regeltotaal
	}
	if ( ! $items ) {
		return null;
	}
	$data = array(
		'currency' => get_woocommerce_currency(),
		'value'    => round( $waarde, 2 ),
		'items'    => $items,
	);
	$codes = WC()->cart->get_applied_coupons();
	if ( $codes ) {
		$data['coupon'] = implode( ',', $codes );
	}
	return $data;
}

/** Status van deze request: betaalde toevoegingen en of wp_footer al draaide. */
function sim_ga_request_staat() {
	static $staat = null;
	if ( null === $staat ) {
		$staat = (object) array(
			'toegevoegd' => array(),
			'footer'     => false,
		);
	}
	return $staat;
}

/**
 * add_to_cart voor toevoegingen die met een redirect eindigen, zoals de
 * Duo-knop (/?sim_duo=1) of een toevoeging waarna WooCommerce doorstuurt naar
 * de winkelwagen. Site Kit schrijft zijn add_to_cart pas in wp_footer van
 * dezelfde request uit en mist die dus. We onthouden de betaalde toevoegingen
 * van deze request (de gratis 2+1-regel niet); draait wp_footer niet, dan gaan
 * ze via de sessie mee en vuren ze op de volgende winkelwagen- of afrekenpagina.
 * Die worden nooit gecachet, dus een event komt nooit bij een andere bezoeker
 * terecht. AJAX- en Store API-toevoegingen meet Site Kit zelf in de browser.
 */
add_action( 'woocommerce_add_to_cart', function ( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
	if ( ! empty( $cart_item_data['sim_gratis'] ) || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$product = wc_get_product( $variation_id ? $variation_id : $product_id );
	if ( $product ) {
		sim_ga_request_staat()->toegevoegd[] = sim_ga_item( $product, $quantity );
	}
}, 30, 6 );

add_action( 'shutdown', function () {
	$staat = sim_ga_request_staat();
	if ( $staat->footer || ! $staat->toegevoegd || ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	$wachtend = (array) WC()->session->get( 'sim_ga_add_to_cart', array() );
	// De sessie slaat zichzelf later op (shutdown, prioriteit 20).
	WC()->session->set( 'sim_ga_add_to_cart', array_slice( array_merge( $wachtend, $staat->toegevoegd ), -20 ) );
}, 5 );

/**
 * Site Kit vult zijn productlijst (nodig voor add_to_cart bij de AJAX-knoppen)
 * alleen via de filter woocommerce_loop_add_to_cart_link. De eigen knoppen op de
 * homepage en in de productkaarten gaan buiten die filter om, waardoor Site Kit
 * een leeg product met waarde NaN stuurt. We melden de leverbare losse producten
 * daarom hier bij die filter aan, voordat Site Kit zijn data in wp_footer
 * (prioriteit 10) uitschrijft. Zo werkt het ook voor knoppen die later in de
 * templates bijkomen. De lijst is voor elke bezoeker gelijk, dus cache-veilig.
 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'wc_get_products' ) || is_checkout() ) {
		return;
	}
	$producten = wc_get_products(
		array(
			'status'       => 'publish',
			'type'         => 'simple',
			'stock_status' => 'instock',
			'limit'        => 20,
		)
	);
	foreach ( $producten as $product ) {
		if ( $product->is_purchasable() ) {
			apply_filters( 'woocommerce_loop_add_to_cart_link', '', $product, array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}
	}
}, 5 );

/**
 * De trechterstappen die Site Kit niet meet: view_item op de productpagina,
 * view_cart op de winkelwagen en begin_checkout op de afrekenpagina (niet op de
 * bedankpagina en niet op order-pay). Plus de uitgestelde add_to_cart van een
 * toevoeging met redirect. De productpagina wordt gecachet; daar staat alleen
 * productdata in, niets van de bezoeker.
 */
add_action( 'wp_footer', function () {
	$staat         = sim_ga_request_staat();
	$staat->footer = true; // toevoegingen van deze request schrijft Site Kit zelf uit
	if ( ! function_exists( 'WC' ) || ! function_exists( 'is_product' ) ) {
		return;
	}
	$valuta = get_woocommerce_currency();

	if ( is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$som   = sim_duo_setprijs( $product );
			$prijs = $som > 0 ? $som : (float) wc_get_price_to_display( $product );
			sim_ga_event(
				'view_item',
				array(
					'currency' => $valuta,
					'value'    => round( $prijs, 2 ),
					'items'    => array( sim_ga_item( $product, 1, $prijs ) ),
				)
			);
		}
		return;
	}

	$is_cart     = is_cart();
	$is_checkout = is_checkout() && ! is_order_received_page() && ! is_wc_endpoint_url( 'order-pay' );
	if ( ! $is_cart && ! $is_checkout ) {
		return;
	}

	$wachtend = WC()->session ? WC()->session->get( 'sim_ga_add_to_cart' ) : null;
	if ( $wachtend && is_array( $wachtend ) ) {
		WC()->session->set( 'sim_ga_add_to_cart', null );
		$waarde = 0.0;
		foreach ( $wachtend as $item ) {
			$waarde += (float) $item['price'] * (int) $item['quantity'];
		}
		sim_ga_event(
			'add_to_cart',
			array(
				'currency' => $valuta,
				'value'    => round( $waarde, 2 ),
				'items'    => array_values( $wachtend ),
			)
		);
	}

	$data = sim_ga_winkelwagen_data();
	if ( $data ) {
		sim_ga_event( $is_cart ? 'view_cart' : 'begin_checkout', $data );
	}
}, 1 );

/* ---------- productschema ---------- */

/**
 * Productnaam in het schema: de WooCommerce-productnaam in plaats van de
 * SEO-titel met "| Tropical Fresh" (bij de Duo stond de merknaam er twee keer
 * in). get_name() en niet get_the_title(): die laatste haalt de titel door
 * wptexturize, waardoor er HTML-entiteiten in het schema kunnen komen. De naam
 * is dan ook gelijk aan wat Google Listings & Ads naar de feed stuurt.
 */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', function ( $entity ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
	if ( $product && is_array( $entity ) ) {
		$entity['name'] = $product->get_name();
	}
	return $entity;
}, 16 );

/**
 * Retourbeleid voor het schema, volgens /retourbeleid/: 14 dagen bedenktijd na
 * ontvangst, terugsturen per post, de retourkosten zijn voor de klant.
 */
function sim_schema_retourbeleid() {
	return array(
		'@type'                => 'MerchantReturnPolicy',
		'applicableCountry'    => 'NL',
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
		'merchantReturnDays'   => 14,
		'returnMethod'         => 'https://schema.org/ReturnByMail',
		'returnFees'           => 'https://schema.org/ReturnFeesCustomerResponsibility',
		'merchantReturnLink'   => home_url( '/retourbeleid/' ),
	);
}

/**
 * Verzend- en retourinfo bij een Offer. Verzending naar Nederland: gratis
 * vanaf de drempel, daaronder de vaste verzendkosten (de Duo komt dus op 0).
 * Geen levertijd: de verdeling over verwerking en transport is niet bevestigd.
 */
function sim_schema_offer_aanvullen( $offer ) {
	if ( ! is_array( $offer ) || ! isset( $offer['@type'] ) || 'Offer' !== $offer['@type'] || ! isset( $offer['price'] ) ) {
		return $offer;
	}
	$valuta     = ! empty( $offer['priceCurrency'] ) ? $offer['priceCurrency'] : get_woocommerce_currency();
	$gratis     = round( (float) $offer['price'] * 100 ) >= round( (float) SIM_GRATIS_VERZENDING_VANAF * 100 );
	$verzending = $gratis ? 0.0 : (float) SIM_VERZENDKOSTEN;

	$offer['shippingDetails']         = array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => array(
			'@type'    => 'MonetaryAmount',
			'value'    => number_format( $verzending, 2, '.', '' ),
			'currency' => $valuta,
		),
		'shippingDestination' => array(
			'@type'          => 'DefinedRegion',
			'addressCountry' => 'NL',
		),
	);
	$offer['hasMerchantReturnPolicy'] = sim_schema_retourbeleid();
	return $offer;
}

/** Na de Duo-prijsfix (prioriteit 20), zodat de Duo met 17,90 op gratis verzending uitkomt. */
add_filter( 'rank_math/snippet/rich_snippet_product_entity', function ( $entity ) {
	if ( ! is_array( $entity ) || empty( $entity['offers'] ) || ! is_array( $entity['offers'] ) ) {
		return $entity;
	}
	if ( isset( $entity['offers']['@type'] ) ) {
		$entity['offers'] = sim_schema_offer_aanvullen( $entity['offers'] );
	} else {
		foreach ( $entity['offers'] as $sim_i => $sim_offer ) {
			$entity['offers'][ $sim_i ] = sim_schema_offer_aanvullen( $sim_offer );
		}
	}
	return $entity;
}, 25 );

/** Hetzelfde retourbeleid ook op organisatieniveau (na de organisatiefilter op prioriteit 20). */
add_filter( 'rank_math/json_ld', function ( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	foreach ( $data as $sim_key => $sim_entity ) {
		$sim_type = isset( $sim_entity['@type'] ) ? (array) $sim_entity['@type'] : array();
		if ( array_intersect( array( 'Store', 'Organization' ), $sim_type ) ) {
			$data[ $sim_key ]['hasMerchantReturnPolicy'] = sim_schema_retourbeleid();
		}
	}
	return $data;
}, 21 );

/* ---------- Duo: setprijs in de deelpreviews ---------- */

/**
 * Rank Math zet in Open Graph de laagste kindprijs (8,95) van de Duo; de pagina
 * en het schema vragen 17,90. product:price:amount krijgt de setprijs.
 */
add_filter( 'rank_math/opengraph/facebook/product_price_amount', function ( $prijs ) {
	if ( ! function_exists( 'wc_get_product' ) || ! is_singular( 'product' ) ) {
		return $prijs;
	}
	$som = sim_duo_setprijs( wc_get_product( get_queried_object_id() ) );
	return $som > 0 ? number_format( $som, 2, '.', '' ) : $prijs;
} );

/** Ook de prijsregel in de Slack/X-preview (twitter:label1 "Prijs"). */
add_filter( 'rank_math/opengraph/slack_enhanced_data', function ( $data ) {
	if ( ! is_array( $data ) || ! function_exists( 'wc_get_product' ) || ! is_singular( 'product' ) ) {
		return $data;
	}
	$som     = sim_duo_setprijs( wc_get_product( get_queried_object_id() ) );
	$sleutel = __( 'Price', 'rank-math' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- de sleutel die Rank Math zelf gebruikt ("Prijs").
	if ( $som > 0 && isset( $data[ $sleutel ] ) ) {
		$data[ $sleutel ] = wp_strip_all_tags( wc_price( $som ) );
	}
	return $data;
} );

/* ---------- categoriearchief en kruimelpad ---------- */

/**
 * Productcategorie- en tagarchieven (zoals /product-category/autoparfum/) zijn
 * een kopie van /shop/ met dezelfde productkaarten: noindex, links wel volgen.
 */
add_filter( 'rank_math/frontend/robots', function ( $robots ) {
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() && is_array( $robots ) ) {
		$robots['index']  = 'noindex';
		$robots['follow'] = 'follow';
	}
	return $robots;
}, 20 );

/**
 * Het schema-kruimelpad op productpagina's wees op positie 2 naar het
 * categoriearchief, terwijl het zichtbare kruimelpad naar /shop/ linkt.
 * Categorieschakels gaan nu naar /shop/; staat /shop/ er al in, dan vervalt de
 * categorieschakel, zodat er geen dubbele stap ontstaat.
 */
add_filter( 'rank_math/frontend/breadcrumb/items', function ( $crumbs ) {
	if ( ! is_array( $crumbs ) || ! function_exists( 'is_product' ) || ! is_product() ) {
		return $crumbs;
	}
	$shop       = wc_get_page_permalink( 'shop' );
	$pl         = wc_get_permalink_structure();
	$basis      = '/' . trim( ! empty( $pl['category_rewrite_slug'] ) ? $pl['category_rewrite_slug'] : 'product-category', '/' ) . '/';
	$heeft_shop = false;
	foreach ( $crumbs as $sim_crumb ) {
		if ( ! empty( $sim_crumb[1] ) && untrailingslashit( $sim_crumb[1] ) === untrailingslashit( $shop ) ) {
			$heeft_shop = true;
		}
	}
	foreach ( $crumbs as $sim_i => $sim_crumb ) {
		$pad = ! empty( $sim_crumb[1] ) ? (string) wp_parse_url( $sim_crumb[1], PHP_URL_PATH ) : '';
		if ( '' === $pad || false === strpos( $pad, $basis ) ) {
			continue;
		}
		if ( $heeft_shop ) {
			unset( $crumbs[ $sim_i ] );
		} else {
			$crumbs[ $sim_i ][1] = $shop;
			$heeft_shop          = true;
		}
	}
	return array_values( $crumbs );
} );

/* GLA-gtag staat uit via de mu-plugin tf-gla-geen-gtag.php (het filter moet vóór plugins_loaded bestaan). */
