<?php
/**
 * Sinterklaas-mailing nov 2026 naar de opt-in-lijst van de vorige eigenaar (besluit eigenaar 11 nov:
 * "email marketing nog een keer doen").
 *
 * Werkt alleen als de wachtrij (optie sim_sint_wachtrij) bestaat. Verstuurt via WP-cron in kleine
 * porties en wacht tot de afzender klopt: SPF van tropicalfresh.nl moet de uitgaande relais van de
 * host bevatten en default._domainkey moet een echte DKIM-sleutel hebben. De eerste mailing (okt) ging
 * zonder die twee en boven de uurlimiet van de host (100 per uur per domein); 1001 mails werden toen
 * weggegooid.
 *
 * Grenzen (Nederlandse tijd): 12 per kwartier (48 per uur, ruimte voor orderbevestigingen), 350 per dag,
 * 08:00-21:00, niets meer na 2 december (de mail gaat over pakjesavond). Elk adres gaat uit de wachtrij
 * voordat de mail vertrekt, dus niemand krijgt hem twee keer. Noodstop: optie sim_sint_stop = 1.
 * Afmelden via de bestaande sim_afmelden-link (functions.php).
 */

defined( 'ABSPATH' ) || exit;

const SIM_SINT_PORTIE  = 12;
const SIM_SINT_PER_DAG = 350;
const SIM_SINT_EINDE   = '2026-12-02 23:59:59';

add_filter( 'cron_schedules', function ( $s ) {
	$s['sim_kwartier'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Elk kwartier' );
	return $s;
} );

add_action( 'init', function () {
	// Eerst de (autoload) cron-array, pas daarna de grote wachtrij-optie.
	if ( ! wp_next_scheduled( 'sim_sint_portie' ) && false !== get_option( 'sim_sint_wachtrij', false ) ) {
		wp_schedule_event( time() + 60, 'sim_kwartier', 'sim_sint_portie' );
	}
} );

function sim_sint_nu() {
	return new DateTimeImmutable( 'now', new DateTimeZone( 'Europe/Amsterdam' ) );
}

/** TXT-records als tekst, of null als de opvraging zelf mislukte (storing is geen 'ok'). */
function sim_sint_txt( $naam ) {
	$r = @dns_get_record( $naam, DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! is_array( $r ) ) {
		return null;
	}
	return implode( ' ', array_map( function ( $x ) {
		return isset( $x['entries'] ) ? implode( '', (array) $x['entries'] ) : (string) ( $x['txt'] ?? '' );
	}, $r ) );
}

/** Klopt de afzender? SPF met de relais van de host en een echte DKIM-sleutel. */
function sim_sint_afzender_ok() {
	$staat = get_transient( 'sim_sint_dns' );
	if ( false === $staat ) {
		$spf  = sim_sint_txt( 'tropicalfresh.nl' );
		$dkim = sim_sint_txt( 'default._domainkey.tropicalfresh.nl' );
		if ( null === $spf || null === $dkim ) {
			set_transient( 'sim_sint_dns', 'nee', 5 * MINUTE_IN_SECONDS );
			return false;
		}
		$spf_ok  = false !== stripos( $spf, 'v=spf1' ) && false !== strpos( $spf, 'spf.mysecurecloudhost.com' );
		$dkim_ok = false !== stripos( $dkim, 'v=DKIM1' ) && preg_match( '/\bp=[A-Za-z0-9+\/=]{100,}/', $dkim );
		$staat   = ( $spf_ok && $dkim_ok ) ? 'ok' : 'nee';
		set_transient( 'sim_sint_dns', $staat, HOUR_IN_SECONDS );
	}
	return 'ok' === $staat;
}

function sim_sint_log( $regel ) {
	$log   = (array) get_option( 'sim_sint_log', array() );
	$log[] = sim_sint_nu()->format( 'Y-m-d H:i' ) . ' ' . $regel;
	update_option( 'sim_sint_log', array_slice( $log, -200 ), false );
}

/** Tekstversie naast de HTML (beter voor spamfilters). */
function sim_sint_tekst( $afmeld ) {
	return "Iets kleins voor in de schoen?\n\n"
		. "Pakjesavond komt eraan. Met de 2+1 set van Tropical Fresh heb je drie flesjes autoparfum: eentje voor jezelf en twee om weg te geven.\n"
		. "In de set: Creamy Vanilla en twee keer Juicy Green Apple, sprays van 60 ml. Samen 19,90 euro, gratis verzonden in Nederland.\n\n"
		. "Bekijk de set: https://tropicalfresh.nl/product/tropical-fresh-duo/?utm_source=email&utm_medium=email&utm_campaign=sint-nov26&utm_content=tekst\n\n"
		. "Je ontvangt deze mail omdat je je bij Tropical Fresh hebt aangemeld voor e-mail.\n"
		. "Afmelden: {$afmeld}\n"
		. "Tropical Fresh, Hogewoerd 71, 2311 HG Leiden, KvK 27371332\n";
}

add_action( 'sim_sint_portie', function () {
	if ( false === get_option( 'sim_sint_wachtrij', false ) ) {
		wp_clear_scheduled_hook( 'sim_sint_portie' );
		return;
	}
	$nu = sim_sint_nu();
	if ( $nu > new DateTimeImmutable( SIM_SINT_EINDE, new DateTimeZone( 'Europe/Amsterdam' ) ) ) {
		sim_sint_log( 'einddatum voorbij: wachtrij met ' . count( (array) get_option( 'sim_sint_wachtrij', array() ) ) . ' adressen verwijderd' );
		delete_option( 'sim_sint_wachtrij' );
		wp_clear_scheduled_hook( 'sim_sint_portie' );
		return;
	}
	if ( get_option( 'sim_sint_stop' ) ) {
		return;
	}
	$uur = (int) $nu->format( 'G' );
	if ( $uur < 8 || $uur >= 21 ) {
		return;
	}
	if ( ! sim_sint_afzender_ok() ) {
		set_transient( 'sim_sint_status', 'wacht op SPF en DKIM', HOUR_IN_SECONDS );
		return;
	}
	$dag = 'sim_sint_dag_' . $nu->format( 'Ymd' );
	if ( (int) get_option( $dag, 0 ) >= SIM_SINT_PER_DAG ) {
		return;
	}
	// Slot: add_option is een atomaire INSERT. Een slot ouder dan 10 minuten is van een vastgelopen run.
	$slot = (int) get_option( 'sim_sint_slot', 0 );
	if ( $slot && time() - $slot > 10 * MINUTE_IN_SECONDS ) {
		delete_option( 'sim_sint_slot' );
	}
	if ( ! add_option( 'sim_sint_slot', time(), '', 'no' ) ) {
		return;
	}

	$sjabloon = (string) file_get_contents( get_template_directory() . '/assets/mail/sint-nov26.html' );
	$afgemeld = array_map( 'strtolower', (array) get_option( 'sim_afmeldingen', array() ) );
	$tekst    = '';
	// Envelope-afzender op het eigen domein (SPF en DKIM lijnen dan uit op tropicalfresh.nl) en een tekstversie.
	$mailer_instellen = function ( $mailer ) use ( &$tekst ) {
		$mailer->Sender  = 'info@tropicalfresh.nl';
		$mailer->AltBody = $tekst;
	};
	add_action( 'phpmailer_init', $mailer_instellen );
	$n      = 0;
	$fouten = 0;
	while ( $n < SIM_SINT_PORTIE && (int) get_option( $dag, 0 ) < SIM_SINT_PER_DAG ) {
		$wachtrij = (array) get_option( 'sim_sint_wachtrij', array() );
		if ( ! $wachtrij ) {
			break;
		}
		$aan = strtolower( trim( (string) array_shift( $wachtrij ) ) );
		update_option( 'sim_sint_wachtrij', $wachtrij, false ); // eerst uit de wachtrij, dan pas versturen
		if ( ! is_email( $aan ) || in_array( $aan, $afgemeld, true ) ) {
			continue;
		}
		$link    = home_url( '/?sim_afmelden=' . rawurlencode( base64_encode( $aan ) ) . '&k=' . sim_afmeld_hash( $aan ) );
		$html    = str_replace( '{{AFMELD}}', esc_url( $link ), $sjabloon );
		$tekst   = sim_sint_tekst( $link );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: Tropical Fresh <info@tropicalfresh.nl>',
			'Reply-To: Tropical Fresh <tropicalfreshshop@gmail.com>',
			'List-Unsubscribe: <' . $link . '>',
			'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
		);
		if ( wp_mail( $aan, 'Iets kleins voor in de schoen', $html, $headers ) ) {
			update_option( 'sim_sint_verzonden', (int) get_option( 'sim_sint_verzonden', 0 ) + 1, false );
			update_option( $dag, (int) get_option( $dag, 0 ) + 1, false );
		} else {
			++$fouten;
			$mislukt   = (array) get_option( 'sim_sint_mislukt', array() );
			$mislukt[] = $aan;
			update_option( 'sim_sint_mislukt', $mislukt, false );
		}
		++$n;
		usleep( 1500000 );
	}
	remove_action( 'phpmailer_init', $mailer_instellen );
	$rest = count( (array) get_option( 'sim_sint_wachtrij', array() ) );
	sim_sint_log( "portie {$n} verstuurd, fouten {$fouten}, resterend {$rest}" );
	if ( $fouten >= 3 ) {
		update_option( 'sim_sint_stop', 1 ); // liever stoppen dan doorsturen in een storing
		sim_sint_log( 'noodstop: 3 of meer fouten in een portie' );
	}
	if ( 0 === $rest ) {
		sim_sint_log( 'klaar: wachtrij leeg' );
		delete_option( 'sim_sint_wachtrij' );
		wp_clear_scheduled_hook( 'sim_sint_portie' );
	}
	delete_option( 'sim_sint_slot' );
} );
