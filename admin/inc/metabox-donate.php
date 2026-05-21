<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
function image_points_donate_meta_box() {
	//post type
	$screens = array( 'image_points' );

	foreach ( $screens as $screen ) {
		add_meta_box(
			'image-points-donate-shortcode',
			__( 'Buy me a Coffee to keep me awake :)', 'image-points' ),
			'image_points_donate_shortcode_callback',
			$screen,
			'side',
			'low'
		);
	}
}
add_action( 'add_meta_boxes', 'image_points_donate_meta_box' );
function image_points_donate_shortcode_callback(){
	$donate_url = 'https://paypal.me/nauhyuh99';
	$image_url = plugin_dir_url( __FILE__ ) . '../images/btn_donateCC_LG.gif';
	?>
	<a href="<?php echo esc_url( $donate_url ); ?>" title="<?php esc_attr_e( 'Donate', 'image-points' ); ?>" target="_blank" rel="noopener noreferrer">
		<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php esc_attr_e( 'Donate', 'image-points' ); ?>"/>
	</a>
	<?php
}