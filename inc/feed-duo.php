<?php
/**
 * Google Shopping-feed: de 2+1 set (de Duo, gegroepeerd product tropical-fresh-duo) als
 * bundel in Merchant Center, zonder iets aan de Duo-pagina of de winkelwagen te veranderen.
 *
 * Google for WooCommerce (GLA) synct standaard alleen simple/variable/variation en geeft een
 * gegroepeerd product geen prijs. Hier mag 'grouped' mee (de Duo is het enige gegroepeerde
 * product) en zetten we prijs, voorraad en bundelvelden via GLA's override-filter, met
 * dezelfde regels als de koopblok-logica in woocommerce/single-product.php:
 * - prijs = Vanilla + Apple (wat de klant betaalt; de derde fles komt gratis via de 2+1-cartlogica);
 * - leverbaar = beide geuren leverbaar en, bij voorraadbeheer op Apple, minstens 2 Apple.
 * De klant koopt de set nog steeds via /?sim_duo=1; de Duo zelf blijft niet koopbaar.
 *
 * @package scentinmotion
 */

defined( 'ABSPATH' ) || exit;

/** Is dit product de Duo (de 2+1 set)? */
function sim_feed_is_duo( $product ) {
	return $product instanceof WC_Product && 'tropical-fresh-duo' === $product->get_slug();
}

/**
 * Prijs en leverbaarheid van de 2+1 set, berekend uit de twee geuren.
 *
 * @return array{prijs: float, leverbaar: bool}
 */
function sim_feed_duo_set() {
	$ids = function_exists( 'sim_promo_ids' ) ? sim_promo_ids() : array( 'vanilla' => 0, 'apple' => 0 );
	$v   = $ids['vanilla'] ? wc_get_product( $ids['vanilla'] ) : null;
	$a   = $ids['apple'] ? wc_get_product( $ids['apple'] ) : null;
	if ( ! $v || ! $a ) {
		return array( 'prijs' => 0.0, 'leverbaar' => false );
	}
	return array(
		'prijs'     => (float) wc_get_price_to_display( $v ) + (float) wc_get_price_to_display( $a ),
		'leverbaar' => function_exists( 'sim_promo_active' ) && sim_promo_active()
			&& $v->is_purchasable() && $a->is_purchasable()
			&& ( ! $a->managing_stock() || (int) $a->get_stock_quantity() >= 2 ),
	);
}

/* GLA: gegroepeerde producten toelaten (alleen de Duo bestaat als gegroepeerd product). */
add_filter(
	'woocommerce_gla_supported_product_types',
	function ( $types ) {
		$types   = (array) $types;
		$types[] = 'grouped';
		return array_values( array_unique( $types ) );
	}
);

/* GLA: velden van de Duo in de feed. Deze filter wint van alle andere mapping. */
add_filter(
	'woocommerce_gla_product_attribute_values',
	function ( $overrides, $product ) {
		if ( ! sim_feed_is_duo( $product ) ) {
			return $overrides;
		}
		$set = sim_feed_duo_set();
		if ( $set['prijs'] <= 0 ) {
			return $overrides;
		}
		$overrides = (array) $overrides;

		// Titel en beschrijving zonder actietaal ("2+1", "gratis"): Google wil die niet in titel of
		// beschrijving. Alleen toegestane claims; de aanbieding staat op de landingspagina.
		$overrides['title']            = 'Autoparfum spray set 3 x 60 ml: Creamy Vanilla en 2x Juicy Green Apple | Tropical Fresh';
		$overrides['description']      = 'Set van drie autoparfum sprays van 60 ml van Tropical Fresh: 1x Creamy Vanilla, warm en zoet, en 2x Juicy Green Apple, fris en fruitig. Tot 2 dagen geur per spraybeurt. Vlekvrij getest op leer, stof en kunststof. 14 dagen retourrecht.';
		$overrides['price']            = array(
			'amountMicros' => (string) (int) round( $set['prijs'] * 1000000 ),
			'currencyCode' => get_woocommerce_currency(),
		);
		$overrides['availability']     = $set['leverbaar'] ? 'IN_STOCK' : 'OUT_OF_STOCK';
		$overrides['isBundle']         = true;
		$overrides['identifierExists'] = false;
		$overrides['brand']            = 'Tropical Fresh';
		$overrides['condition']        = 'NEW';
		return $overrides;
	},
	10,
	2
);

/*
 * GLA leest de Duo alleen opnieuw als de Duo zelf wordt opgeslagen. Verandert de prijs of de
 * voorraad van Vanilla of Apple, dan slaan we de Duo aan het einde van het verzoek één keer op,
 * zodat prijs en leverbaarheid in Merchant Center meebewegen.
 */
function sim_feed_duo_markeer( $product_of_id ) {
	$id  = is_object( $product_of_id ) ? (int) $product_of_id->get_id() : (int) $product_of_id;
	$ids = function_exists( 'sim_promo_ids' ) ? array_map( 'intval', array_values( sim_promo_ids() ) ) : array();
	if ( $id && in_array( $id, $ids, true ) ) {
		$GLOBALS['sim_feed_duo_opnieuw'] = true;
	}
}
add_action( 'woocommerce_update_product', 'sim_feed_duo_markeer', 5 );
add_action( 'woocommerce_product_set_stock_status', 'sim_feed_duo_markeer', 10 );
add_action( 'woocommerce_product_set_stock', 'sim_feed_duo_markeer', 10 );

add_action(
	'shutdown',
	function () {
		if ( empty( $GLOBALS['sim_feed_duo_opnieuw'] ) ) {
			return;
		}
		$GLOBALS['sim_feed_duo_opnieuw'] = false;
		$duo_post                        = get_page_by_path( 'tropical-fresh-duo', OBJECT, 'product' );
		$duo                             = $duo_post ? wc_get_product( $duo_post->ID ) : null;
		if ( $duo ) {
			$duo->save();
		}
	},
	5
);
