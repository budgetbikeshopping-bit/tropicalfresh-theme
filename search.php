<?php
defined( 'ABSPATH' ) || exit;
get_header();

$sim_producten = array();
$sim_overig    = array();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		if ( 'product' === get_post_type() ) {
			$sim_producten[] = get_post();
		} else {
			$sim_overig[] = get_post();
		}
	}
	rewind_posts();
}
?>
<section class="light collection page-wrap">
	<div class="wrap">
		<div class="section-head">
			<span class="mono-label">Zoeken</span>
			<h1>Resultaten voor &ldquo;<?php echo esc_html( get_search_query() ); ?>&rdquo;</h1>
			<?php if ( ! have_posts() ) : ?>
				<p class="lead">Niets gevonden. Probeer een andere term, of laat de <a href="<?php echo esc_url( home_url( '/geurwijzer/' ) ); ?>">geurwijzer</a> voor je kiezen.</p>
			<?php endif; ?>
		</div>

		<?php if ( $sim_producten ) : ?>
			<div class="cards" style="margin-bottom: 44px;">
				<?php
				foreach ( $sim_producten as $sim_p ) {
					setup_postdata( $GLOBALS['post'] =& $sim_p ); // phpcs:ignore
					wc_get_template_part( 'content', 'product' );
				}
				wp_reset_postdata();
				?>
			</div>
		<?php endif; ?>

		<?php if ( $sim_overig ) : ?>
			<div class="cat-tekst" style="margin-top: 0; border-top: 0; padding-top: 0;">
				<h2>Pagina's</h2>
				<ul>
					<?php foreach ( $sim_overig as $sim_o ) : ?>
						<li><a href="<?php echo esc_url( get_permalink( $sim_o ) ); ?>"><?php echo esc_html( get_the_title( $sim_o ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
