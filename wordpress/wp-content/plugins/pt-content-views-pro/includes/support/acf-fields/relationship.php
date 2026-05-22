<?php
/*
 * Basic loop
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$posts = $value;
?>

<ul>
	<?php foreach ( $posts as $post ): // variable must be called $post (IMPORTANT) ?>
		<li>
			<a href="<?php echo get_permalink( $post ) ?>"><?php echo apply_filters( PT_CV_PREFIX_ . 'acf_relationship_item', get_the_title( $post ), $post ); ?></a>
		</li>
	<?php endforeach; ?>
</ul>
<?php
wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>