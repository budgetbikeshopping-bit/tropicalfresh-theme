<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<?php echo sim_nieuwsbrief_blok( 'footer' ); // phpcs:ignore -- opmaak en copy staan in inc/klantcontact.php ?>

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
			<span class="foot-bedrijf">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Tropical Fresh &middot; Hogewoerd 71, 2311 HG Leiden &middot; KvK 27371332 &middot; btw NL002422235B29 &middot; <a href="mailto:info@tropicalfresh.nl">info@tropicalfresh.nl</a></span>
			<span>Veilig betalen: iDEAL &middot; Wero &middot; creditcard</span>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
