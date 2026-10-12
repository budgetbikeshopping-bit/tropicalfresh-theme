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
				<p>Autoparfum in 60 ml sprays. Twee karakters, geen ruis.</p>
				<h5 class="foot-volg">Volg ons</h5>
				<?php echo sim_sociale_links( 'foot-social' ); // phpcs:ignore ?>
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
			<span class="foot-bedrijf">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Tropical Fresh is een handelsnaam van Budget Bike XL B.V. &middot; Flevoweg 6E, 2318 BZ Leiden &middot; KvK 68069758 &middot; btw NL857288799B01 &middot; <a href="mailto:info@tropicalfresh.nl">info@tropicalfresh.nl</a></span>
			<span>Veilig betalen: iDEAL &middot; Wero &middot; creditcard</span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
