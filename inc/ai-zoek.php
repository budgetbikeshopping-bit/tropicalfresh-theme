<?php
/**
 * Vindbaarheid in AI-zoekmachines (ChatGPT, Perplexity, Copilot, Claude, Google AI Overviews).
 *
 * - /llms.txt: eigen versie met actuele prijzen, het 2+1-aanbod, verzending en retour,
 *   in plaats van de kale Rank Math-lijst (titel "Tropical Fresh: Tropical Fresh",
 *   HTML-entiteiten in de tekst, geen enkel productfeit).
 * - FAQPage-schema uit de zichtbare veelgestelde vragen van elke pagina: de accordeons
 *   op de homepage en de h3-vragen onder een h2 "Veelgestelde vragen" in de pijler,
 *   de geurwijzer en alle artikelen. Alleen wat de bezoeker ook echt ziet.
 * - Artikelauteur: Rank Math noemde een naamloze persoon met /author/ (404) als auteur;
 *   dat wordt de organisatie Tropical Fresh.
 * - Homepage-schema: dateModified volgt de laatste wijziging van homepage of producten
 *   (stond op 27-10-2025) en de hoofdafbeelding is de echte 2+1 set in plaats van de
 *   uitverkochte Shiny New Car.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- llms.txt ---------- */

/** Prijs als "€ 9,95" voor platte tekst. */
function sim_ai_prijs( $bedrag ) {
	return '€ ' . number_format( (float) $bedrag, 2, ',', '.' );
}

/** HTML naar één regel platte tekst (ook &#039; en harde spaties). */
function sim_ai_tekst( $html ) {
	$tekst = html_entity_decode( wp_strip_all_tags( (string) $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$tekst = str_replace( "\xc2\xa0", ' ', $tekst );
	return trim( preg_replace( '/\s+/u', ' ', $tekst ) );
}

/** De volledige llms.txt, opgebouwd uit de live winkeldata. */
function sim_ai_llms_txt() {
	$drempel = 17.90; // gratis verzending in NL vanaf; gelijk aan front-page.php en de verzendinstelling
	$regels  = array();

	$regels[] = '# Tropical Fresh';
	$regels[] = '';
	$regels[] = '> Tropical Fresh is een Nederlandse webshop voor autoparfum uit Leiden. We verkopen sprays van 60 ml die je zelf doseert in het interieur van je auto, in plaats van een geurhanger aan de spiegel. Elke spraybeurt geeft tot twee dagen geur en de sprays zijn vlekvrij getest op leer, stof en kunststof.';
	$regels[] = '';
	$regels[] = 'Laatst bijgewerkt: ' . wp_date( 'j F Y' ) . '. Prijzen hieronder zijn de actuele webshopprijzen inclusief btw.';
	$regels[] = '';

	$regels[] = '## Producten';
	$regels[] = '';
	$producten = function_exists( 'wc_get_products' ) ? wc_get_products(
		array(
			'status'       => 'publish',
			'visibility'   => 'visible',
			'stock_status' => 'instock',
			'limit'        => 20,
			'orderby'      => 'menu_order',
			'order'        => 'ASC',
		)
	) : array();
	foreach ( $producten as $product ) {
		$set   = function_exists( 'sim_duo_setprijs' ) ? sim_duo_setprijs( $product ) : 0.0;
		$prijs = $set > 0 ? $set : (float) wc_get_price_to_display( $product );
		if ( $prijs <= 0 ) {
			continue;
		}
		$kort  = sim_ai_tekst( $product->get_short_description() );
		$regel = '- [' . sim_ai_tekst( $product->get_name() ) . '](' . get_permalink( $product->get_id() ) . '): ' . sim_ai_prijs( $prijs );
		if ( $set > 0 ) {
			$regel .= ' voor 3 flesjes van 60 ml (je betaalt 2, de derde Juicy Green Apple is gratis), met gratis verzending in Nederland.';
		} else {
			$regel .= ' per spray van 60 ml.';
		}
		$regels[] = $regel . ( $kort ? ' ' . $kort : '' );
	}
	$regels[] = '';
	$regels[] = 'Geuren: Creamy Vanilla is warm, zacht en zoet. Juicy Green Apple is groene appel met een lichte zoete toon, fris en helder.';
	$regels[] = '';

	$regels[] = '## Gebruik';
	$regels[] = '';
	$regels[] = '- Spray twee tot drie keer in het interieur, bijvoorbeeld op de matten of onder de stoel. Niet op schermen of glas.';
	$regels[] = '- Een spraybeurt geeft tot twee dagen geur, afhankelijk van ventilatie, temperatuur en interieur.';
	$regels[] = '- Vlekvrij getest op leer, stof en kunststof.';
	$regels[] = '';

	$regels[] = '## Bestellen, verzending en retour';
	$regels[] = '';
	$regels[] = '- Verzending in Nederland is gratis vanaf ' . sim_ai_prijs( $drempel ) . ', daaronder ' . sim_ai_prijs( 4.95 ) . '. Naar België ' . sim_ai_prijs( 7.95 ) . '.';
	$regels[] = '- Doorgaans binnen 2 tot 4 werkdagen geleverd.';
	$regels[] = '- Betalen met iDEAL | Wero of creditcard.';
	$regels[] = '- 14 dagen retourrecht na ontvangst: ' . home_url( '/retourneren/' );
	$regels[] = '- Nieuwsbrief: 10% korting op de eerste bestelling van losse flesjes (niet op de 2+1 set).';
	$regels[] = '- Contact: info@tropicalfresh.nl, ' . home_url( '/contact/' );
	$regels[] = '';

	$regels[] = '## Gidsen over autoparfum en een frisse auto';
	$regels[] = '';
	$pijler = get_page_by_path( 'autoparfum' );
	if ( $pijler ) {
		$regels[] = '- [Autoparfum voor een frisse auto](' . get_permalink( $pijler ) . '): wat autoparfum is, spray of geurhanger, gebruik en geurkeuze.';
		$kinderen = get_pages(
			array(
				'child_of'    => $pijler->ID,
				'post_status' => 'publish',
				'sort_column' => 'menu_order,post_title',
			)
		);
		foreach ( $kinderen as $kind ) {
			$omschrijving = sim_ai_tekst( get_post_meta( $kind->ID, 'rank_math_description', true ) );
			if ( '' === $omschrijving || false !== strpos( $omschrijving, '%' ) ) {
				$omschrijving = wp_trim_words( sim_ai_tekst( $kind->post_content ), 30, '...' );
			}
			$regels[] = '- [' . sim_ai_tekst( get_the_title( $kind ) ) . '](' . get_permalink( $kind ) . '): ' . $omschrijving;
		}
	}
	$geurwijzer = get_page_by_path( 'geurwijzer' );
	if ( $geurwijzer ) {
		$regels[] = '- [Geurwijzer](' . get_permalink( $geurwijzer ) . '): welke autogeur past bij jou, warm en zoet of fris en fruitig.';
	}
	$regels[] = '';

	$regels[] = '## Over Tropical Fresh';
	$regels[] = '';
	$regels[] = '- [Over ons](' . home_url( '/over-ons/' ) . '): klein autoparfum-merk uit Leiden met een kleine collectie geuren.';
	$regels[] = '- Handelsnaam Tropical Fresh, KvK 27371332, Leiden, Nederland.';
	$regels[] = '- [Algemene voorwaarden](' . home_url( '/algemene-voorwaarden/' ) . '), [retourbeleid](' . home_url( '/retourbeleid/' ) . '), [privacybeleid](' . home_url( '/privacybeleid/' ) . ').';
	$regels[] = '';

	return implode( "\n", $regels );
}

/**
 * Serveert /llms.txt vóór Rank Math (die doet het op template_redirect prioriteit 10).
 * Werkt ook als de Rank Math-module ooit uit gaat: dan is het pad zelf de trigger.
 */
add_action( 'template_redirect', function () {
	$pad = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( 1 !== (int) get_query_var( 'llms_txt' ) && '/llms.txt' !== $pad ) {
		return;
	}
	// Een dag in de LiteSpeed-cache: prijzen en gidsen veranderen zelden binnen een dag.
	do_action( 'litespeed_control_set_ttl', DAY_IN_SECONDS );
	status_header( 200 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex, follow', true );
	echo sim_ai_llms_txt(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- platte tekst
	exit;
}, 1 );

/* ---------- FAQPage-schema uit de zichtbare vragen ---------- */

/** Vraag-antwoordparen uit de pagina-HTML, in de volgorde waarin ze op de pagina staan. */
function sim_ai_faq_vragen( $html ) {
	$paren = array();

	// Accordeons in een FAQ-sectie (homepage: section.faq > details.acc).
	if ( preg_match_all( '~<section[^>]*class="[^"]*\bfaq\b[^"]*"[^>]*>(.*?)</section>~is', $html, $secties ) ) {
		foreach ( $secties[1] as $sectie ) {
			preg_match_all( '~<summary[^>]*>(.*?)</summary>\s*<div[^>]*class="[^"]*\bbody\b[^"]*"[^>]*>(.*?)</div>\s*</details>~is', $sectie, $acc, PREG_SET_ORDER );
			foreach ( $acc as $x ) {
				$paren[] = array( sim_ai_tekst( $x[1] ), sim_ai_tekst( $x[2] ) );
			}
		}
	}

	// Een h2 "Veelgestelde vragen..." met h3-vragen en de tekst eronder (pijler, geurwijzer, artikelen).
	if ( preg_match( '~<h2[^>]*>[^<]*Veelgestelde vragen[^<]*</h2>(.*?)(?=<h2|</article>|</main>|<section)~is', $html, $blok ) ) {
		$delen = preg_split( '~<h3[^>]*>~i', $blok[1] );
		array_shift( $delen );
		foreach ( $delen as $deel ) {
			$stukken = explode( '</h3>', $deel, 2 );
			if ( 2 === count( $stukken ) ) {
				$paren[] = array( sim_ai_tekst( $stukken[0] ), sim_ai_tekst( $stukken[1] ) );
			}
		}
	}

	return array_values( array_filter( $paren, fn( $p ) => '' !== $p[0] && '' !== $p[1] ) );
}

/** Output-buffer: zet het FAQPage-blok in de head als de pagina minstens twee vragen toont. */
function sim_ai_faq_buffer( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, '</head>' ) || false !== strpos( $html, '"FAQPage"' ) ) {
		return $html;
	}
	$paren = sim_ai_faq_vragen( $html );
	if ( count( $paren ) < 2 ) {
		return $html;
	}
	$schema = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => array(),
	);
	foreach ( $paren as $paar ) {
		$schema['mainEntity'][] = array(
			'@type'          => 'Question',
			'name'           => $paar[0],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $paar[1],
			),
		);
	}
	$tag = '<script type="application/ld+json" class="sim-faq-schema">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . "</script>\n";
	return preg_replace( '~</head>~i', $tag . '</head>', $html, 1 );
}

add_action( 'template_redirect', function () {
	if ( is_admin() || wp_doing_ajax() || is_feed() || is_404() ) {
		return;
	}
	if ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) {
		return;
	}
	if ( ! is_front_page() && ! is_page() && ! is_single() ) {
		return;
	}
	ob_start( 'sim_ai_faq_buffer' );
}, 2 );

/* ---------- Rank Math-graaf: auteur en homepage ---------- */

add_filter( 'rank_math/json_ld', function ( $data ) {
	$organisatie = array( '@id' => home_url( '/#organization' ) );
	$naamloos    = '~/author/?$~';

	foreach ( $data as $sleutel => $entiteit ) {
		if ( ! is_array( $entiteit ) ) {
			continue;
		}
		$types = isset( $entiteit['@type'] ) ? (array) $entiteit['@type'] : array();
		if ( in_array( 'Person', $types, true ) && isset( $entiteit['@id'] ) && preg_match( $naamloos, $entiteit['@id'] ) ) {
			unset( $data[ $sleutel ] );
			continue;
		}
		if ( isset( $entiteit['author']['@id'] ) && preg_match( $naamloos, $entiteit['author']['@id'] ) ) {
			$data[ $sleutel ]['author'] = $organisatie;
		}
	}

	if ( ! is_front_page() ) {
		return $data;
	}

	// Homepage: laatste echte wijziging (homepagesjabloon of een van de producten) en de 2+1 set als beeld.
	$momenten = array( (int) @filemtime( get_template_directory() . '/front-page.php' ) );
	if ( function_exists( 'wc_get_products' ) ) {
		foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 10, 'return' => 'ids' ) ) as $sim_id ) {
			$momenten[] = (int) get_post_modified_time( 'U', true, $sim_id );
		}
	}
	$gewijzigd = gmdate( 'Y-m-d\TH:i:sP', max( $momenten ) );
	$beeld_url = get_template_directory_uri() . '/assets/img/hero_set_2plus1.webp';

	foreach ( $data as $sleutel => $entiteit ) {
		if ( ! is_array( $entiteit ) ) {
			continue;
		}
		$types = isset( $entiteit['@type'] ) ? (array) $entiteit['@type'] : array();
		if ( in_array( 'WebPage', $types, true ) ) {
			$data[ $sleutel ]['dateModified']       = $gewijzigd;
			$data[ $sleutel ]['primaryImageOfPage'] = array( '@id' => $beeld_url );
		}
		if ( in_array( 'ImageObject', $types, true ) && isset( $entiteit['url'] ) && false !== strpos( $entiteit['url'], 'shiny-new-car' ) ) {
			$data[ $sleutel ] = array(
				'@type'      => 'ImageObject',
				'@id'        => $beeld_url,
				'url'        => $beeld_url,
				'width'      => '900',
				'height'     => '900',
				'caption'    => 'Tropical Fresh 2+1 set: Creamy Vanilla en twee keer Juicy Green Apple, sprays van 60 ml',
				'inLanguage' => 'nl-NL',
			);
		}
	}
	return $data;
}, 30 );
