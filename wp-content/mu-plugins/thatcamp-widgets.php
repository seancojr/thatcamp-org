<?php

// @todo not use create_function()
function thatcamp_blogs_register_widgets() {
	global $wpdb;

	include( __DIR__ . '/includes/posts-widget.php' );

	if ( bp_is_active( 'activity' ) && (int) $wpdb->blogid == bp_get_root_blog_id() )
		add_action( 'widgets_init', create_function( '', 'unregister_widget( "BP_Blogs_Recent_Posts_Widget" ); return register_widget("THATCamp_Blogs_Recent_Posts_Widget");' ) );
}
add_action( 'bp_register_widgets', 'thatcamp_blogs_register_widgets', 20 );
