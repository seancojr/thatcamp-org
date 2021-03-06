<?php
/**
 * Content display for posts - default template
 *
 * @package thatcampbase
 * @since thatcampbase 1.0
 */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="post-header">
		<h1 class="post-title"><a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'thatcampbase'), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php the_title(); ?></a></h1>
	</header>
	<div class="post-body">
		<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'thatcampbase') ); ?>
	</div>
	<footer class="post-meta">
		<div class="post-edit">
				<?php edit_post_link( __( 'Edit &rarr;', 'thatcampbase'), ' <span class="edit-link">', '</span> | ' ); ?>
				<a href="<?php the_permalink(); ?>" title="<?php printf( esc_attr__( 'Permalink to %s', 'thatcampbase'), the_title_attribute( 'echo=0' ) ); ?>" rel="bookmark"><?php _e( 'Permalink', 'thatcampbase'); ?></a>
		</div>
	</footer>
</article>