<?php
defined( 'ABSPATH' ) || exit;
get_header();

$sim_wide = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );
?>
<section class="light page-wrap">
	<div class="wrap <?php echo $sim_wide ? 'wide' : 'narrow'; ?>">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article <?php post_class(); ?>>
				<h1 class="page-title"><?php the_title(); ?></h1>
				<div class="page-content"><?php the_content(); ?></div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</section>
<?php get_footer(); ?>
