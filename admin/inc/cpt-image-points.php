<?php
/**
 * Custom post type registration and admin columns for Image Points.
 *
 * @package Image_Points
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Register Image Points custom post type.
 */
function image_points_cpt_func() {

	$labels = array(
		'name'                  => _x( 'Image Points', 'Post Type General Name', 'image-points' ),
		'singular_name'         => _x( 'Image Points', 'Post Type Singular Name', 'image-points' ),
		'menu_name'             => __( 'Image Points', 'image-points' ),
		'name_admin_bar'        => __( 'Image Points', 'image-points' ),
		'archives'              => __( 'Item Archives', 'image-points' ),
		'parent_item_colon'     => __( 'Parent Item:', 'image-points' ),
		'all_items'             => __( 'All Items', 'image-points' ),
		'add_new_item'          => __( 'Add New Item', 'image-points' ),
		'add_new'               => __( 'Add New', 'image-points' ),
		'new_item'              => __( 'New Item', 'image-points' ),
		'edit_item'             => __( 'Edit Item', 'image-points' ),
		'update_item'           => __( 'Update Item', 'image-points' ),
		'view_item'             => __( 'View Item', 'image-points' ),
		'search_items'          => __( 'Search Item', 'image-points' ),
		'not_found'             => __( 'Not found', 'image-points' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'image-points' ),
		'featured_image'        => __( 'Featured Image', 'image-points' ),
		'set_featured_image'    => __( 'Set featured image', 'image-points' ),
		'remove_featured_image' => __( 'Remove featured image', 'image-points' ),
		'use_featured_image'    => __( 'Use as featured image', 'image-points' ),
		'insert_into_item'      => __( 'Insert into item', 'image-points' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'image-points' ),
		'items_list'            => __( 'Items list', 'image-points' ),
		'items_list_navigation' => __( 'Items list navigation', 'image-points' ),
		'filter_items_list'     => __( 'Filter items list', 'image-points' ),
	);
	$args   = array(
		'label'               => __( 'Image Points', 'image-points' ),
		'labels'              => $labels,
		'supports'            => array( 'title' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-location-alt',
		'show_in_admin_bar'   => false,
		'show_in_nav_menus'   => false,
		'can_export'          => false,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
	);
	register_post_type( 'image_points', $args );
}
add_action( 'init', 'image_points_cpt_func', 0 );

/**
 * Hide irrelevant post action controls for Image Points entries.
 */
function image_points_admin_css() {
	global $post_type;
	$post_types = array(
		'image_points',
	);
	if ( in_array( $post_type, $post_types, true ) ) {
		echo '<style type="text/css">#post-preview, #view-post-btn,#message.notice-success a{display: none;}</style>';
	}
}
add_action( 'admin_head-post-new.php', 'image_points_admin_css' );
add_action( 'admin_head-post.php', 'image_points_admin_css' );

add_filter( 'page_row_actions', 'image_points_row_actions', 10, 2 );
add_filter( 'post_row_actions', 'image_points_row_actions', 10, 2 );
/**
 * Remove unsupported row actions.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post    Current post.
 * @return array
 */
function image_points_row_actions( $actions, $post ) {
	if ( 'image_points' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
		unset( $actions['view'] );
	}
	return $actions;
}

/**
 * Configure admin list table columns.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function image_points_cpt_admin_columns( $columns ) {
	$columns = array(
		'cb'        => '<input type="checkbox" />',
		'title'     => __( 'Title', 'image-points' ),
		'shortcode' => __( 'Shortcode', 'image-points' ),
		'date'      => __( 'Date', 'image-points' ),
	);
	return $columns;
}
add_filter( 'manage_edit-image_points_columns', 'image_points_cpt_admin_columns' );

/**
 * Render custom column content.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function image_points_manage_image_points_columns( $column, $post_id ) {
	unset( $post_id );

	global $post;
	switch ( $column ) {
		case 'shortcode':
			echo esc_html( '[image_points id="' . intval( $post->ID ) . '"]' );
			break;
		default:
			break;
	}
}
add_action( 'manage_image_points_posts_custom_column', 'image_points_manage_image_points_columns', 10, 2 );
