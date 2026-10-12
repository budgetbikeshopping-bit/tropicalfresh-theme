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
