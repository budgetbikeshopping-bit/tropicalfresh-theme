<?php
/**
 * Sinterklaas-mailing nov 2026 naar de opt-in-lijst van de vorige eigenaar (besluit eigenaar 11 nov:
 * "email marketing nog een keer doen").
 *
 * Werkt alleen als de wachtrij (optie sim_sint_wachtrij) bestaat. Verstuurt via WP-cron in kleine
 * porties en wacht tot de afzender klopt: SPF van tropicalfresh.nl moet de uitgaande relais van de
 * host bevatten en de DKIM-sleutel default._domainkey moet bestaan. De eerste mailing (okt) ging zonder
 * die twee en boven de uurlimiet van de host (100 per uur per domein); 1001 mails werden toen weggegooid.
 *
 * Grenzen: 12 per kwartier (48 per uur, ruimte voor orderbevestigingen), 350 per dag, 08:00-21:00.
 * Noodstop: optie sim_sint_stop = 1. Afmelden via de bestaande sim_afmelden-link (functions.php).
 */

defined( 'ABSPATH' ) || exit;

const SIM_SINT_PORTIE  = 12;
const SIM_SINT_PER_DAG = 350;

add_filter( 'cron_schedules', function ( $s ) {
	$s['sim_kwartier'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Elk kwartier' );
	return $s;
} );

add_action( 'init', function () {
	if ( false !== get_option( 'sim_sint_wachtrij', false ) && ! wp_next_scheduled( 'sim_sint_portie' ) ) {
		wp_schedule_event( time() + 60, 'sim_kwartier', 'sim_sint_portie' );
	}
} );

/** Klopt de afzender? SPF met de relais van de host en een DKIM-sleutel. Uitkomst 1 uur bewaard. */
function sim_sint_afzender_ok() {
	$staat = get_transient( 'sim_sint_dns' );
	if ( false === $staat ) {
		$spf = '';
		foreach ( (array) @dns_get_record( 'tropicalfresh.nl', DNS_TXT ) as $r ) {
			$spf .= ' ' . ( $r['txt'] ?? '' );
		}
		$dkim  = (array) @dns_get_record( 'default._domainkey.tropicalfresh.nl', DNS_TXT );
		$staat = ( false !== strpos( $spf, 'spf.mysecurecloudhost.com' ) && ! empty( $dkim ) ) ? 'ok' : 'nee';
		set_transient( 'sim_sint_dns', $staat, HOUR_IN_SECONDS );
	}
	return 'ok' === $staat;
}

function sim_sint_log( $regel ) {
	$log   = (array) get_option( 'sim_sint_log', array() );
	$log[] = current_time( 'mysql' ) . ' ' . $regel;
	update_option( 'sim_sint_log', array_slice( $log, -200 ), false );
}

add_action( 'sim_sint_portie', function () {
	$wachtrij = get_option( 'sim_sint_wachtrij', false );
	if ( false === $wachtrij ) {
		wp_clear_scheduled_hook( 'sim_sint_portie' );
		return;
	}
	if ( get_option( 'sim_sint_stop' ) || get_transient( 'sim_sint_slot' ) ) {
		return;
	}
	$uur = (int) wp_date( 'G' );
	if ( $uur < 8 || $uur >= 21 ) {
		return;
	}
	if ( ! sim_sint_afzender_ok() ) {
		set_transient( 'sim_sint_status', 'wacht op SPF en DKIM', HOUR_IN_SECONDS );
		return;
	}
	$dag     = 'sim_sint_dag_' . wp_date( 'Ymd' );
	$vandaag = (int) get_option( $dag, 0 );
	if ( $vandaag >= SIM_SINT_PER_DAG ) {
		return;
	}
	if ( ! $wachtrij ) {
		sim_sint_log( 'klaar: wachtrij leeg' );
		delete_option( 'sim_sint_wachtrij' );
		wp_clear_scheduled_hook( 'sim_sint_portie' );
		return;
	}
	set_transient( 'sim_sint_slot', 1, 10 * MINUTE_IN_SECONDS );

	$sjabloon  = (string) file_get_contents( get_template_directory() . '/assets/mail/sint-nov26.html' );
	$afgemeld  = array_map( 'strtolower', (array) get_option( 'sim_afmeldingen', array() ) );
	$verzonden = (int) get_option( 'sim_sint_verzonden', 0 );
	$fouten    = 0;
	// Envelope-afzender op het eigen domein, zodat SPF en DKIM op tropicalfresh.nl uitlijnen (DMARC).
	$afzender = function ( $mailer ) {
		$mailer->Sender = 'info@tropicalfresh.nl';
	};
	add_action( 'phpmailer_init', $afzender );
	$n = 0;
	while ( $wachtrij && $n < SIM_SINT_PORTIE && $vandaag < SIM_SINT_PER_DAG ) {
		$aan = strtolower( trim( (string) array_shift( $wachtrij ) ) );
		if ( ! is_email( $aan ) || in_array( $aan, $afgemeld, true ) ) {
			continue;
		}
		$link    = home_url( '/?sim_afmelden=' . rawurlencode( base64_encode( $aan ) ) . '&k=' . sim_afmeld_hash( $aan ) );
		$html    = str_replace( '{{AFMELD}}', esc_url( $link ), $sjabloon );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Tropical Fresh <info@tropicalfresh.nl>',
			'Reply-To: Tropical Fresh <tropicalfreshshop@gmail.com>',
			'List-Unsubscribe: <' . $link . '>',
			'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
		);
		if ( wp_mail( $aan, 'Iets kleins voor in de schoen', $html, $headers ) ) {
			++$verzonden;
			++$vandaag;
		} else {
			++$fouten;
		}
		++$n;
		usleep( 1500000 );
	}
	remove_action( 'phpmailer_init', $afzender );
	update_option( 'sim_sint_wachtrij', $wachtrij, false );
	update_option( 'sim_sint_verzonden', $verzonden, false );
	update_option( $dag, $vandaag, false );
	sim_sint_log( "portie {$n} verstuurd, fouten {$fouten}, resterend " . count( $wachtrij ) );
	if ( $fouten >= 3 ) {
		update_option( 'sim_sint_stop', 1 ); // liever stoppen dan doorsturen in een storing
		sim_sint_log( 'noodstop: 3 of meer fouten in een portie' );
	}
	delete_transient( 'sim_sint_slot' );
} );
