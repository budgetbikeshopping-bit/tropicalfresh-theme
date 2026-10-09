<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="light page-wrap">
	<div class="wrap narrow">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class(); ?>>
					<h1><?php the_title(); ?></h1>
					<div class="page-content"><?php the_content(); ?></div>
				</article>
				<?php
			endwhile;
		else :
			?>
			<h1>Niets gevonden</h1>
			<p class="lead">Deze pagina bestaat niet (meer). <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Terug naar home</a>.</p>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
