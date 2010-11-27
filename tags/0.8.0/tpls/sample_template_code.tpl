<?php
/**
 * Sample template for displaying all single [+post_type+]-type posts.
 * Save this file as as single-[+post_type+].php
 *
 * This sample code was based off of the Starkers theme: http://starkerstheme.com/
 *
 */

get_header(); ?>

<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
	
	<h1><?php the_title(); ?></h1>
	
		<?php the_content(); ?>

		<h2>Custom Fields</h2>	
		
[+custom_fields+]
		<?php comments_template( '', true ); ?>

<?php endwhile; // end of the loop. ?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>