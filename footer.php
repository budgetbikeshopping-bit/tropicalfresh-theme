<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<section class="newsletter">
	<div class="wrap inner">
		<div>
			<span class="mono-label">Nieuwsbrief</span>
			<h2>Nieuwe geuren als eerste ruiken?</h2>
			<p class="fine">Schrijf je in en ontvang 10% korting op je eerste bestelling.</p>
		</div>
		<?php echo sim_email_form( 'sim_newsletter', 'Aanmelden' ); // phpcs:ignore ?>
	</div>
</section>

<footer>
	<div class="wrap">
		<div class="foot-grid">
			<div>
				<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Tropical Fresh home">
					<?php echo sim_logo_svg(); // phpcs:ignore ?>
					<span class="name">TROPICAL<b>&#8202;FRESH</b></span>
				</a>
				<p>Autoparfum uit Leiden. Twee karakters, geen ruis.</p>
				<div class="foot-social" aria-label="Volg Tropical Fresh">
					<a href="https://www.tiktok.com/@tropicalfresh_nl" target="_blank" rel="noopener" aria-label="Tropical Fresh op TikTok">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-2.59-2.59c.27 0 .53.04.78.12V9.77a5.76 5.76 0 0 0-.78-.05 5.69 5.69 0 1 0 5.69 5.68V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.3 4.3 0 0 1-3.25-1.48z"/></svg>
					</a>
					<a href="https://www.instagram.com/tropicalfresh.nl/" target="_blank" rel="noopener" aria-label="Tropical Fresh op Instagram">
						<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor" stroke="none"/></svg>
					</a>
					<a href="https://www.youtube.com/channel/UCqnlCEmGxS0Z-6bOJiOKSmA" target="_blank" rel="noopener" aria-label="Tropical Fresh op YouTube">
						<svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true"><path d="M23 7.5s-.22-1.56-.9-2.24c-.86-.9-1.82-.9-2.26-.96C16.7 4.08 12 4.08 12 4.08h-.01s-4.7 0-7.84.22c-.44.05-1.4.06-2.26.96C1.21 5.94 1 7.5 1 7.5S.78 9.33.78 11.16v1.66C.78 14.66 1 16.5 1 16.5s.21 1.56.89 2.24c.86.9 2 .87 2.5.97 1.81.17 7.61.22 7.61.22s4.71-.01 7.84-.23c.44-.05 1.4-.06 2.26-.96.68-.68.9-2.24.9-2.24s.22-1.84.22-3.67v-1.67C23.22 9.33 23 7.5 23 7.5zM9.68 14.93V8.86l6.08 3.05-6.08 3.02z"/></svg>
					</a>
				</div>
			</div>
			<div>
				<h5>Shop</h5>
				<ul>
					<li><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Alle geuren</a></li>
					<li><a href="<?php echo esc_url( home_url( '/product/tropical-fresh-duo/' ) ); ?>">Tropical Fresh Duo</a></li>
					<li><a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">Geurwijzer</a></li>
				</ul>
			</div>
			<div>
				<h5>Service</h5>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a></li>
					<li><a href="<?php echo esc_url( home_url( '/retourneren/' ) ); ?>">Retourneren</a></li>
					<li><a href="<?php echo esc_url( home_url( '/over-ons/' ) ); ?>">Over ons</a></li>
				</ul>
			</div>
			<div>
				<h5>Juridisch</h5>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/privacybeleid/' ) ); ?>">Privacybeleid</a></li>
					<li><a href="<?php echo esc_url( home_url( '/algemene-voorwaarden/' ) ); ?>">Algemene voorwaarden</a></li>
					<li><a href="<?php echo esc_url( home_url( '/retourbeleid/' ) ); ?>">Retourbeleid</a></li>
				</ul>
			</div>
		</div>
		<div class="foot-base">
			<span>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Tropical Fresh B.V. &middot; Flevoweg 6E, Leiden</span>
			<span>Veilig betalen: iDEAL &middot; Wero &middot; creditcard</span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
