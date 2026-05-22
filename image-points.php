<?php
/**
 * Main plugin bootstrap for Image Points.
 *
 * @package Image_Points
 */

/*
Plugin Name: Image Points
Plugin URI: https://t11n.dev/
Description: Image Points helps you add interactive points to your images.
Author: T11N
Version: 1.0.0
Author URI: https://t11n.dev/
Text Domain: image-points
Domain Path: /languages
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0

Image Points

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

define( 'IMAGE_POINTS_VER', '1.0.0' );
define( 'IMAGE_POINTS_DEV_MOD', true );
define( 'IMAGE_POINTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'IMAGE_POINTS_URL', plugin_dir_url( __FILE__ ) );
if ( ! defined( 'IMAGE_POINTS_BASENAME' ) ) {
	define( 'IMAGE_POINTS_BASENAME', plugin_basename( __FILE__ ) );
}

define(
	'IMAGE_POINTS_POINT_DEFAULT',
	wp_json_encode(
		array(
			'countPoint'  => '',
			'content'     => '',
			'left'        => '',
			'top'         => '',
			'linkpins'    => '',
			'link_target' => '',
			'placement'   => '',
			'pins_id'     => '',
			'pins_class'  => '',
			'pinsalt'     => '',
		)
	)
);
define(
	'IMAGE_POINTS_PINS_DEFAULT',
	wp_json_encode(
		array(
			'countPoint' => '',
			'imgPoint'   => '',
			'top'        => '',
			'left'       => '',
		)
	)
);

require_once IMAGE_POINTS_PATH . 'admin/inc/cpt-image-points.php';
require_once IMAGE_POINTS_PATH . 'admin/inc/add-shortcode-image-points.php';
require_once IMAGE_POINTS_PATH . 'admin/inc/metabox-donate.php';
require_once IMAGE_POINTS_PATH . 'admin/inc/settings.php';

/**
 * Load bundled translation files before WordPress language packs.
 *
 * @param string $mofile Translation file path.
 * @param string $domain Text domain.
 * @return string
 */
function image_points_load_my_own_textdomain( $mofile, $domain ) {
	if ( 'image-points' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
		$locale = apply_filters( 'plugin_locale', determine_locale(), $domain ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
	}
	return $mofile;
}
add_filter( 'load_textdomain_mofile', 'image_points_load_my_own_textdomain', 10, 2 );

/**
 * Register Image Points metaboxes.
 */
function image_points_meta_box() {
	$screens = array( 'image_points' );

	foreach ( $screens as $screen ) {
		add_meta_box(
			'image-points-metabox',
			__( 'Image Points', 'image-points' ),
			'image_points_meta_box_callback',
			$screen,
			'normal',
			'high'
		);
		add_meta_box(
			'image-points-shortcode',
			__( 'Image Points Shortcode', 'image-points' ),
			'image_points_shortcode_callback',
			$screen,
			'side',
			'high'
		);
	}
}
add_action( 'add_meta_boxes', 'image_points_meta_box' );

/**
 * Prefer the TinyMCE editor in point content fields.
 *
 * @return string
 */
function image_points_default_editor() {
	return 'tinymce';
}

/**
 * Render the main editor metabox.
 *
 * @param WP_Post $post Current post.
 */
function image_points_meta_box_callback( $post ) {
	add_filter( 'wp_default_editor', 'image_points_default_editor' );
	wp_nonce_field( 'image_points_save_meta_box_data', 'image_points_meta_box_nonce' );

	$data_post = get_post_meta( $post->ID, 'image_points_content', true );

	if ( ! is_serialized( $data_post ) && ! is_array( $data_post ) && is_string( $data_post ) ) {
		$data_post = json_decode( $data_post, true );
	}

	if ( ! $data_post ) {
		$post_content = $post->post_content;
		if ( is_serialized( $post_content ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize,WordPress.PHP.NoSilencedErrors.Discouraged -- Legacy content fallback with allowed_classes disabled.
			$data_post = @unserialize( trim( $post_content ), array( 'allowed_classes' => false ) );
		} else {
			$data_post = $post_content;
		}
	}

	$image_points_main_image = ( isset( $data_post['image_points_main_image'] ) ) ? $data_post['image_points_main_image'] : '';
	$data_points             = isset( $data_post['data_points'] ) && $data_post['data_points'] ? $data_post['data_points'] : array();

	if ( ! empty( $data_points ) ) {

		$decoded_array = array();

		foreach ( $data_points as $key => $array_value ) {
			foreach ( $array_value as $key2 => $encoded_value ) {
				if ( $encoded_value && is_string( $encoded_value ) && image_points_is_base64( $encoded_value ) ) {
					// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Plugin stores sanitized point values as base64 strings.
					$decoded_array[ $key ][ $key2 ] = base64_decode( $encoded_value );
				} else {
					$decoded_array[ $key ][ $key2 ] = $encoded_value;
				}
			}
		}

		$data_points = image_points_sanitize_data_points( $decoded_array );

	}

	$pins_image       = ( isset( $data_post['pins_image'] ) ) ? $data_post['pins_image'] : '';
	$pins_image_hover = ( isset( $data_post['pins_image_hover'] ) ) ? $data_post['pins_image_hover'] : '';
	$pins_more_option = ( isset( $data_post['pins_more_option'] ) ) ? $data_post['pins_more_option'] : array();
	$pins_more_option = wp_parse_args(
		$pins_more_option,
		array(
			'position'          => 'center_center',
			'custom_top'        => 0,
			'custom_left'       => 0,
			'custom_hover_top'  => 0,
			'custom_hover_left' => 0,
			'pins_animation'    => 'none',
		)
	);
	?>	
	<table class="svl-table">
		<tbody>
			<tr>
				<td class="svl-label"><?php esc_html_e( 'Pins Image', 'image-points' ); ?></td>
				<td class="svl-input">
					<div class="svl-upload-image <?php echo ( $pins_image ) ? 'has-image' : ''; ?>">
						<div class="view-has-value">
							<input type="hidden" name="pins_image" class="pins_image" value="<?php echo esc_attr( $pins_image ); ?>" />
							<img src="<?php echo esc_attr( $pins_image ); ?>" class="image_view pins_img"/>
							<a href="#" class="svl-delete-image">x</a>
						</div>
						<div class="hidden-has-value"><input type="button" class="button-upload button" value="<?php esc_html_e( 'Select pins', 'image-points' ); ?>" /></div>
					</div>
				</td>
			</tr>
			<tr>
				<td class="svl-label"><?php esc_html_e( 'Pins Hover Image', 'image-points' ); ?></td>
				<td class="svl-input">
					<div class="svl-upload-image <?php echo ( $pins_image_hover ) ? 'has-image' : ''; ?>">
						<div class="view-has-value">
							<input type="hidden" name="pins_image_hover" class="pins_image_hover" value="<?php echo esc_attr( $pins_image_hover ); ?>" />
							<img src="<?php echo esc_attr( $pins_image_hover ); ?>" class="image_view pins_img_hover"/>
							<a href="#" class="svl-delete-image">x</a>
						</div>
						<div class="hidden-has-value"><input type="button" class="button-upload button" value="<?php esc_html_e( 'Select pins hover', 'image-points' ); ?>" /></div>
					</div>
				</td>				
			</tr>
			<tr>
				<td class="svl-label"><?php esc_html_e( 'Pins Center Position', 'image-points' ); ?></td>
				<td class="svl-input">
					<div class="pins-position-wrap">
						<p>
								<label><input type="radio" name="choose_type" value="center_center" <?php checked( 'center_center', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Center center', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="top_left" <?php checked( 'top_left', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Top Left', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="top_center" <?php checked( 'top_center', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Top Center', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="top_right" <?php checked( 'top_right', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Top Right', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="right_center" <?php checked( 'right_center', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Right Center', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="bottom_right" <?php checked( 'bottom_right', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Bottom Right', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="bottom_center" <?php checked( 'bottom_center', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Bottom Center', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="bottom_left" <?php checked( 'bottom_left', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Bottom Left', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="left_center" <?php checked( 'left_center', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Left Center', 'image-points' ); ?></label>
								<label><input type="radio" name="choose_type" value="custom_center" <?php checked( 'custom_center', $pins_more_option['position'] ); ?>><?php esc_html_e( 'Custom', 'image-points' ); ?></label>
							<label><?php esc_html_e( 'Top: -', 'image-points' ); ?> <input type="number" name="custom_top" value="<?php echo floatval( $pins_more_option['custom_top'] ); ?>" min="0" step="any"> px</label>
							<label><?php esc_html_e( 'Left: -', 'image-points' ); ?> <input type="number" name="custom_left" value="<?php echo floatval( $pins_more_option['custom_left'] ); ?>" min="0" step="any"> px</label>
							<input type="hidden" name="custom_hover_top" value="<?php echo floatval( $pins_more_option['custom_hover_top'] ); ?>" min="0" step="any">
							<input type="hidden" name="custom_hover_left" value="<?php echo floatval( $pins_more_option['custom_hover_left'] ); ?>" min="0" step="any">
						</p>
					</div>
				</td>				
			</tr>
			<tr>
				<td class="svl-label"><?php esc_html_e( 'Pins Animation', 'image-points' ); ?></td>
				<td class="svl-input">
					<div class="pins-position-wrap">
						<p>
								<label><input type="radio" name="pins_animation" value="none" <?php checked( 'none', $pins_more_option['pins_animation'] ); ?>><?php esc_html_e( 'None', 'image-points' ); ?></label>
								<label><input type="radio" name="pins_animation" value="pulse" <?php checked( 'pulse', $pins_more_option['pins_animation'] ); ?>><?php esc_html_e( 'Pulse', 'image-points' ); ?></label>
						</p>
					</div>
				</td>				
			</tr>
		</tbody>
	</table>
	<div class="svl-image-wrap <?php echo ( $image_points_main_image ) ? 'has-image' : ''; ?>">
	<div class="svl-control">
		<input type="button" id="meta-image-button" class="button" value="<?php esc_attr_e( 'Upload Image', 'image-points' ); ?>" />
		<input type="hidden" name="image_points_main_image" class="image_points_main_image" id="image_points_main_image" value="<?php echo esc_attr( $image_points_main_image ); ?>" />
		<input type="button" name="add_point" class="add_point button view-has-value" value="<?php esc_attr_e( 'Add Point', 'image-points' ); ?>"/>
		<span class="spinner"></span>
	</div>
	<div class="wrap_svl view-has-value" id="body_drag">
		<div class="images_wrap">
			<?php
			if ( $image_points_main_image ) :
				$image_info = image_points_get_image_info_from_url( $image_points_main_image );
				$alt        = isset( $image_info['alt'] ) ? sanitize_text_field( $image_info['alt'] ) : '';
				?>
			<img src="<?php echo esc_attr( $image_points_main_image ); ?>" alt="<?php echo esc_attr( $alt ); ?>">
			<?php endif; ?>
		</div>	
		<?php if ( is_array( $data_points ) ) : ?>
			<?php $stt = 1; foreach ( $data_points as $point ) : ?>
				<?php
				$data_input = array(
					'countPoint'              => $stt,
					'imgPoint'                => $pins_image,
					'top'                     => $point['top'],
					'left'                    => $point['left'],
					'linkpins'                => isset( $point['linkpins'] ) ? esc_url( $point['linkpins'] ) : '',
					'link_target'             => isset( $point['link_target'] ) ? esc_attr( $point['link_target'] ) : '_self',
					'pins_image_custom'       => isset( $point['pins_image_custom'] ) ? $point['pins_image_custom'] : '',
					'pins_image_hover_custom' => isset( $point['pins_image_hover_custom'] ) ? $point['pins_image_hover_custom'] : '',
					'placement'               => isset( $point['placement'] ) ? $point['placement'] : '',
					'pins_id'                 => isset( $point['pins_id'] ) ? $point['pins_id'] : '',
					'pins_class'              => isset( $point['pins_class'] ) ? $point['pins_class'] : '',
					'pinsalt'                 => isset( $point['pinsalt'] ) ? $point['pinsalt'] : '',
				);
				echo image_points_get_pins_default( $data_input ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped	
				?>
				<?php
				++$stt;
endforeach;
			?>
		<?php endif; ?> 	
	</div>
	<div class="all_points">
	<?php if ( is_array( $data_points ) ) : ?>
			<?php $stt = 1;foreach ( $data_points as $point ) : ?>
				<?php
				$data_input = array(
					'countPoint'              => $stt,
					'content'                 => $point['content'],
					'left'                    => $point['left'],
					'top'                     => $point['top'],
					'linkpins'                => isset( $point['linkpins'] ) ? esc_url( $point['linkpins'] ) : '',
					'link_target'             => isset( $point['link_target'] ) ? esc_attr( $point['link_target'] ) : '_self',
					'pins_image_custom'       => isset( $point['pins_image_custom'] ) ? $point['pins_image_custom'] : '',
					'pins_image_hover_custom' => isset( $point['pins_image_hover_custom'] ) ? $point['pins_image_hover_custom'] : '',
					'placement'               => isset( $point['placement'] ) ? $point['placement'] : '',
					'pins_id'                 => isset( $point['pins_id'] ) ? $point['pins_id'] : '',
					'pins_class'              => isset( $point['pins_class'] ) ? $point['pins_class'] : '',
					'pinsalt'                 => isset( $point['pinsalt'] ) ? $point['pinsalt'] : '',
				);
				echo image_points_get_input_point_default( $data_input );//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
				<?php
				++$stt;
endforeach;
			?>
	<?php else : ?>
		<div style="display: none;"><?php wp_editor( '', '_image_points_default_content' ); ?></div>
	<?php endif; ?>	 	 
	</div>
	<?php
}

/**
 * Check whether a string looks like base64 content.
 *
 * @param string $input_string Input string.
 * @return bool
 */
function image_points_is_base64( $input_string ) {
	// Check if string length is a multiple of 4.
	if ( strlen( $input_string ) % 4 !== 0 ) {
		return false;
	}

	return preg_match( '/^[A-Za-z0-9+\/]+={0,2}$/', $input_string );
}

/**
 * Get allowed HTML tags for point content.
 *
 * @return array
 */
function image_points_get_allowed_tags() {
	$allowed_tags           = wp_kses_allowed_html( 'post' );
	$allowed_tags['iframe'] = array(
		'src'             => array(),
		'width'           => array(),
		'height'          => array(),
		'frameborder'     => array(),
		'scrolling'       => array(),
		'allowfullscreen' => array(),
	);
	return apply_filters( 'image_points_allowed_tags', $allowed_tags );
}

/**
 * Sanitize saved point data.
 *
 * @param array $data_points Point data.
 * @return array
 */
function image_points_sanitize_data_points( $data_points ) {
	if ( empty( $data_points ) || ! is_array( $data_points ) ) {
		return array();
	}

	$allowed_tags     = image_points_get_allowed_tags();
	$sanitized_points = array();

	foreach ( $data_points as $key => $point ) {
		if ( ! is_array( $point ) ) {
			continue;
		}

		$sanitized_point = array();

		foreach ( $point as $field_key => $field_value ) {
			if ( 'content' === $field_key ) {
				$sanitized_point[ $field_key ] = wp_kses( $field_value, $allowed_tags );
			} elseif ( 'linkpins' === $field_key ) {
				$sanitized_point[ $field_key ] = esc_url_raw( $field_value );
			} elseif ( 'pins_image_custom' === $field_key || 'pins_image_hover_custom' === $field_key ) {
				$sanitized_point[ $field_key ] = esc_url_raw( $field_value );
			} elseif ( 'link_target' === $field_key ) {
				$sanitized_point[ $field_key ] = sanitize_text_field( $field_value );
			} elseif ( 'placement' === $field_key ) {
				$sanitized_point[ $field_key ] = sanitize_text_field( $field_value );
			} elseif ( 'pins_id' === $field_key || 'pins_class' === $field_key || 'pinsalt' === $field_key ) {
				$sanitized_point[ $field_key ] = sanitize_text_field( $field_value );
			} elseif ( 'top' === $field_key || 'left' === $field_key ) {
				$sanitized_point[ $field_key ] = is_numeric( $field_value ) ? floatval( $field_value ) : sanitize_text_field( $field_value );
			} else {
				$sanitized_point[ $field_key ] = sanitize_text_field( $field_value );
			}
		}

		$sanitized_points[ $key ] = $sanitized_point;
	}

	return $sanitized_points;
}

/**
 * Render shortcode metabox content.
 *
 * @param WP_Post $post Current post.
 */
function image_points_shortcode_callback( $post ) {
	if ( 'publish' === get_post_status( $post->ID ) ) :
		?>
		<span><?php esc_html_e( 'Copy shortcode to view', 'image-points' ); ?></span>
		<input readonly="readonly" class="shortcodemap" value='[image_points id="<?php echo intval( $post->ID ); ?>"]'/>
	<?php else : ?>
		<span><?php esc_html_e( 'Publish to view shortcode', 'image-points' ); ?></span>
		<?php
	endif;
}
/**
 * Save Image Points metabox data.
 *
 * @param int $post_id Post ID.
 */
function image_points_save_meta_box_data( $post_id ) {

	if ( ! isset( $_POST['image_points_meta_box_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( wp_unslash( $_POST['image_points_meta_box_nonce'] ), 'image_points_save_meta_box_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( isset( $_POST['post_type'] ) && 'image_points' === $_POST['post_type'] ) {
		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}
	} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
	}
	if ( ! isset( $_POST['image_points_main_image'] ) ) {
		return;
	}

	$my_data = '';
	if ( isset( $_POST['image_points_main_image'] ) ) {
		$image_points_main_image_raw = sanitize_text_field( wp_unslash( $_POST['image_points_main_image'] ) );
		if ( $image_points_main_image_raw ) {
			$my_data = esc_url_raw( $image_points_main_image_raw );
		}
	}

	$data_points = array();

	/*sanitize in image_points_convert_array_data*/
	$pointdata = isset( $_POST['pointdata'] ) ? wp_unslash( $_POST['pointdata'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	$choose_type = isset( $_POST['choose_type'] ) ? sanitize_text_field( wp_unslash( $_POST['choose_type'] ) ) : '';

	$custom_top  = isset( $_POST['custom_top'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_top'] ) ) : '';
	$custom_left = isset( $_POST['custom_left'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_left'] ) ) : '';

	$custom_hover_top  = isset( $_POST['custom_hover_top'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_hover_top'] ) ) : '';
	$custom_hover_left = isset( $_POST['custom_hover_left'] ) ? sanitize_text_field( wp_unslash( $_POST['custom_hover_left'] ) ) : '';

	$pins_animation = isset( $_POST['pins_animation'] ) ? sanitize_text_field( wp_unslash( $_POST['pins_animation'] ) ) : '';

	$pins_more_option = array(
		'position'          => $choose_type,
		'custom_top'        => $custom_top,
		'custom_left'       => $custom_left,
		'custom_hover_top'  => $custom_hover_top,
		'custom_hover_left' => $custom_hover_left,
		'pins_animation'    => $pins_animation,
	);
	if ( is_array( $pointdata ) ) {
		$data_points = image_points_convert_array_data( $pointdata );
	}
	$data_post = array(
		'image_points_main_image' => $my_data,
		'pins_image'              => isset( $_POST['pins_image'] ) ? sanitize_text_field( wp_unslash( $_POST['pins_image'] ) ) : '',
		'pins_image_hover'        => isset( $_POST['pins_image_hover'] ) ? sanitize_text_field( wp_unslash( $_POST['pins_image_hover'] ) ) : '',
		'pins_more_option'        => $pins_more_option,
		'data_points'             => $data_points,
	);
	update_post_meta( $post_id, 'image_points_content', wp_json_encode( $data_post ) );
}
add_action( 'save_post', 'image_points_save_meta_box_data' );

/**
 * Collect editor styles for dynamically created TinyMCE fields.
 *
 * @return string|false
 */
function image_points_editor_styles() {

	global $wp_version;

	$baseurl = includes_url( 'js/tinymce' );

	$suffix    = SCRIPT_DEBUG ? '' : '.min';
	$version   = 'ver=' . $wp_version;
	$dashicons = includes_url( "css/dashicons$suffix.css?$version" );

	// WordPress default stylesheet and dashicons.
	$mce_css = array(
		$dashicons,
		$baseurl . '/skins/wordpress/wp-content.css?' . $version,
	);

	$editor_styles = get_editor_stylesheets();
	if ( ! empty( $editor_styles ) ) {
		foreach ( $editor_styles as $style ) {
			$mce_css[] = $style;
		}
	}

	$mce_css = trim( apply_filters( 'image_points_mce_css', implode( ',', $mce_css ) ), ' ,' );

	if ( ! empty( $mce_css ) ) {
		return $mce_css;
	} else {
		return false;
	}
}

/**
 * Enqueue admin scripts.
 */
function image_points_admin_script() {
	global $typenow;
	if ( 'image_points' === $typenow ) {
		wp_enqueue_media();

		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-droppable' );

		wp_register_script( 'image-points-tinymce', home_url( '/wp-includes/js/tinymce/wp-tinymce.js' ), array(), IMAGE_POINTS_VER, true );

		wp_register_script( 'image_points', plugin_dir_url( __FILE__ ) . 'admin/js/image_points.js', array( 'jquery', 'quicktags', 'image-points-tinymce', 'editor' ), IMAGE_POINTS_VER, true );
		wp_localize_script(
			'image_points',
			'meta_image',
			array(
				'title'        => __( 'Select image', 'image-points' ),
				'button'       => __( 'Select', 'image-points' ),
				'site_url'     => home_url(),
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'editor_style' => image_points_editor_styles(),
			)
		);
		wp_enqueue_script( 'image_points' );
	}
}
add_action( 'admin_enqueue_scripts', 'image_points_admin_script' );

/**
 * Enqueue admin styles.
 */
function image_points_admin_styles() {
	global $typenow;
	if ( 'image_points' === $typenow ) {
		wp_enqueue_style( 'bootstrap', plugin_dir_url( __FILE__ ) . 'admin/css/bootstrap.css', array(), IMAGE_POINTS_VER, 'all' );
		wp_enqueue_style( 'image_points', plugin_dir_url( __FILE__ ) . 'admin/css/image_points_style.css', array(), IMAGE_POINTS_VER, 'all' );
	}
}
add_action( 'admin_print_styles', 'image_points_admin_styles' );

/**
 * Enqueue frontend scripts and styles.
 */
function image_points_frontend_scripts() {
	if ( IMAGE_POINTS_DEV_MOD ) {
		wp_enqueue_style( 'image-points', plugin_dir_url( __FILE__ ) . 'frontend/css/image_points.css', array(), IMAGE_POINTS_VER, 'all' );
		wp_enqueue_script( 'image-points', plugin_dir_url( __FILE__ ) . 'frontend/js/image_points.js', array( 'jquery' ), IMAGE_POINTS_VER, true );
	} else {
		wp_enqueue_style( 'image_points', plugin_dir_url( __FILE__ ) . 'frontend/css/image_points.min.css', array(), IMAGE_POINTS_VER, 'all' );
		wp_enqueue_script( 'image_points-js', plugin_dir_url( __FILE__ ) . 'frontend/js/jquery.image_points.min.js', array( 'jquery' ), IMAGE_POINTS_VER, true );
	}
}
add_action( 'wp_enqueue_scripts', 'image_points_frontend_scripts' );

/**
 * Build default point editor markup.
 *
 * @param array $data Point data.
 * @return string
 */
function image_points_get_input_point_default( $data = array() ) {
	if ( ! is_array( $data ) ) {
		$data = array();
	}
	$data = wp_parse_args( $data, json_decode( IMAGE_POINTS_POINT_DEFAULT, true ) );

	$count_point             = isset( $data['countPoint'] ) ? $data['countPoint'] : '';
	$point_content           = isset( $data['content'] ) ? $data['content'] : '';
	$point_left              = isset( $data['left'] ) ? $data['left'] : '';
	$point_top               = isset( $data['top'] ) ? $data['top'] : '';
	$point_link              = isset( $data['linkpins'] ) ? $data['linkpins'] : '';
	$link_target             = isset( $data['link_target'] ) ? $data['link_target'] : '_self';
	$pins_image_custom       = isset( $data['pins_image_custom'] ) ? $data['pins_image_custom'] : '';
	$pins_image_hover_custom = isset( $data['pins_image_hover_custom'] ) ? $data['pins_image_hover_custom'] : '';
	$placement               = isset( $data['placement'] ) ? $data['placement'] : '';
	$pins_id                 = isset( $data['pins_id'] ) ? $data['pins_id'] : '';
	$pins_class              = isset( $data['pins_class'] ) ? $data['pins_class'] : '';
	$pinsalt                 = isset( $data['pinsalt'] ) ? $data['pinsalt'] : '';

	$point_content = str_replace( '\"', '"', $point_content );

	ob_start();
	?>
		<div class="image-points-popup list_points" tabindex="-1" role="dialog" id="info_draggable<?php echo intval( $count_point ); ?>" data-popup="info_draggable<?php echo intval( $count_point ); ?>" data-points="<?php echo intval( $count_point ); ?>">
		<div class="image-points-popup-inner">
			<div class="image-points-popup-modal-content">
				<div class="image-points-popup-modal-header">
					<h3 class="modal-title"><?php esc_html_e( 'Content', 'image-points' ); ?></h3>
					</div>
					<div class="image-points-popup-modal-body">
					<?php
					add_filter( 'wp_default_editor', 'image_points_default_editor' );
					$settings = array(
						'textarea_name' => 'pointdata[content][]',
						'tabindex'      => 4,
						'tinymce'       => array(
							'min_height' => 200,
							'toolbar1'   => 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo,wp_more',
						),
					);
					wp_editor( $point_content, 'point_content' . $count_point, $settings );
					?>
					<div class="image_points_row">
						<div class="image_points_col_3">
							<label><?php esc_html_e( 'Link to pins', 'image-points' ); ?><br>
							<input type="text" name="pointdata[linkpins][]" value="<?php echo esc_attr( $point_link ); ?>" placeholder="<?php esc_attr_e( 'Link to pins', 'image-points' ); ?>"/>
							</label><br>
							<label><?php esc_html_e( 'Link target', 'image-points' ); ?><br>
							<select name="pointdata[link_target][]">
								<option value="_self" <?php selected( '_self', $link_target ); ?>><?php esc_html_e( 'Open in current window', 'image-points' ); ?></option>
								<option value="_blank" <?php selected( '_blank', $link_target ); ?>><?php esc_html_e( 'Open in new window', 'image-points' ); ?></option>
							</select>
							</label>

						</div>	
						<div class="image_points_col_3">

							<label><?php esc_html_e( 'Pin Image Custom', 'image-points' ); ?></label>
							<div class="svl-upload-image <?php echo ( $pins_image_custom ) ? 'has-image' : ''; ?>">
								<div class="view-has-value">
									<input type="hidden" name="pointdata[pins_image_custom][]" class="pins_image" value="<?php echo esc_attr( $pins_image_custom ); ?>" />
									<img src="<?php echo esc_attr( $pins_image_custom ); ?>" class="image_view pins_img"/>
									<a href="#" class="svl-delete-image">x</a>
								</div>
								<div class="hidden-has-value"><input type="button" class="button-upload button" value="<?php esc_attr_e( 'Select pins', 'image-points' ); ?>" /></div>
							</div>

							<label><?php esc_html_e( 'Pins hover image custom', 'image-points' ); ?></label>
							<div class="svl-upload-image <?php echo ( $pins_image_hover_custom ) ? 'has-image' : ''; ?>">
								<div class="view-has-value">
									<input type="hidden" name="pointdata[pins_image_hover_custom][]" class="pins_image_hover" value="<?php echo esc_attr( $pins_image_hover_custom ); ?>" />
									<img src="<?php echo esc_attr( $pins_image_hover_custom ); ?>" class="image_view pins_img_hover"/>
									<a href="#" class="svl-delete-image">x</a>
								</div>
								<div class="hidden-has-value"><input type="button" class="button-upload button" value="<?php esc_attr_e( 'Select pins hover', 'image-points' ); ?>" /></div>
							</div>

						</div>
						<div class="image_points_col_3">
							<label><?php esc_html_e( 'Pin Alt Text', 'image-points' ); ?><br>
							<input type="text" name="pointdata[pinsalt][]" value="<?php echo esc_attr( $pinsalt ); ?>" placeholder="<?php esc_attr_e( 'Enter ALT text', 'image-points' ); ?>"/>
							</label>
						</div>
					</div>
					<div class="image_points_row">
						<div class="image_points_col_3">
							<label><?php esc_html_e( 'Placement', 'image-points' ); ?><br></label>
							<select name="pointdata[placement][]">
								<?php
								$all_placement = array(
									'n'  => __( 'North', 'image-points' ),
									'e'  => __( 'East', 'image-points' ),
									's'  => __( 'South', 'image-points' ),
									'w'  => __( 'West', 'image-points' ),
									'nw' => __( 'North West', 'image-points' ),
									'ne' => __( 'North East', 'image-points' ),
									'sw' => __( 'South West', 'image-points' ),
									'se' => __( 'South East', 'image-points' ),
								);
								foreach ( $all_placement as $k => $v ) {
									?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $placement ); ?>><?php echo esc_html( $v ); ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="image_points_col_3">
							<label><?php esc_html_e( 'Pin ID', 'image-points' ); ?><br>
							<input type="text" name="pointdata[pins_id][]" value="<?php echo esc_attr( $pins_id ); ?>" placeholder="<?php esc_attr_e( 'Enter ID', 'image-points' ); ?>"/>
							</label>
						</div>
						<div class="image_points_col_3">
							<label><?php esc_html_e( 'Pin Class', 'image-points' ); ?><br>
							<input type="text" name="pointdata[pins_class][]" value="<?php echo esc_attr( $pins_class ); ?>" placeholder="<?php esc_attr_e( 'e.g.: class_1 class_2 class_3', 'image-points' ); ?>"/>
							</label>
						</div>
					</div>
					<p>
						<input type="hidden" name="pointdata[top][]" min="0" max="100" step="any" value="<?php echo esc_attr( $point_top ); ?>" />
					</p>
					<p>
						<input type="hidden" name="pointdata[left][]" min="0" max="100" step="any" value="<?php echo esc_attr( $point_left ); ?>" />
					</p>
					</div>
					<div class="image-points-popup-modal-footer">
					<button type="button" class="button button-danger button-large button_delete"><?php esc_html_e( 'Delete', 'image-points' ); ?></button>
					<button type="button" class="button button-primary button-large" data-popup-close="info_draggable<?php echo esc_attr( $count_point ); ?>"><?php esc_html_e( 'Done & Close', 'image-points' ); ?></button>
					</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->		
	<?php
	return ob_get_clean();
}

/**
 * Build default pin marker markup.
 *
 * @param array $datapin Pin data.
 * @return string
 */
function image_points_get_pins_default( $datapin = array() ) {
	if ( ! is_array( $datapin ) ) {
		$datapin = array();
	}
	$datapin           = wp_parse_args( $datapin, json_decode( IMAGE_POINTS_PINS_DEFAULT, true ) );
	$count_point       = $datapin['countPoint'];
	$img_pin           = $datapin['imgPoint'];
	$top_pin           = $datapin['top'];
	$left_pin          = $datapin['left'];
	$pins_image_custom = isset( $datapin['pins_image_custom'] ) && $datapin['pins_image_custom'] ? $datapin['pins_image_custom'] : '';
	if ( $pins_image_custom ) {
		$img_pin = $pins_image_custom;
	}
	ob_start();
	?>
	<div id="draggable<?php echo esc_attr( $count_point ); ?>" data-points="<?php echo esc_attr( $count_point ); ?>" class="drag_element" 
	<?php
	if ( $top_pin && $left_pin ) :
		?>
		style="top:<?php echo esc_attr( $top_pin ); ?>%; left:<?php echo esc_attr( $left_pin ); ?>%;"<?php endif; ?>>
		<div class="point_style">		
			<a href="#" class="pins_click_to_edit" data-popup-open="info_draggable<?php echo esc_attr( $count_point ); ?>" data-target="#info_draggable<?php echo esc_attr( $count_point ); ?>">
				<img src="<?php echo esc_attr( $img_pin ); ?>">
			</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_action( 'wp_ajax_image_points_clone_point', 'image_points_clone_point_func' );
/**
 * Ajax handler for cloning a point editor row.
 */
function image_points_clone_point_func() {
	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['nonce'] ), 'image_points_save_meta_box_data' ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		exit();
	}
	if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
	}
	$count_point = isset( $_POST['countpoint'] ) ? intval( wp_unslash( $_POST['countpoint'] ) ) : 0;
	$img_pin     = isset( $_POST['img_pins'] ) ? esc_url_raw( wp_unslash( $_POST['img_pins'] ) ) : '';
	$count_point = ( isset( $count_point ) && ! empty( $count_point ) ) ? $count_point : wp_rand();
	$datapin     = array(
		'countPoint' => $count_point,
		'imgPoint'   => $img_pin,
	);
	$data_input  = array(
		'countPoint' => $count_point,
	);
	wp_send_json_success(
		array(
			'point_pins' => image_points_get_pins_default( $datapin ),
			'point_data' => image_points_get_input_point_default( $data_input ),
		)
	);
	die();
}

/**
 * Convert submitted point form data into encoded rows.
 *
 * @param array $input_array Submitted point fields.
 * @return array
 */
function image_points_convert_array_data( $input_array = array() ) {
	$a_output  = array();
	$first_key = null;
	foreach ( $input_array as $key => $value ) {
		$first_key = $key;
		break;
	}
	$n_count_key = count( $input_array[ $first_key ] );
	for ( $i = 0; $i < $n_count_key;$i++ ) {
		$element = array();
		foreach ( $input_array as $key => $value ) {

			$allowed_tags = image_points_get_allowed_tags();

			if ( 'content' === $key ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoded to preserve structured point content in post meta.
				$element[ $key ] = base64_encode( wp_kses( $value[ $i ], $allowed_tags ) );
			} elseif ( 'linkpins' === $key || 'pins_image_custom' === $key || 'pins_image_hover_custom' === $key ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoded to preserve structured point content in post meta.
				$element[ $key ] = base64_encode( esc_url_raw( $value[ $i ] ) );
			} elseif ( 'top' === $key || 'left' === $key ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoded to preserve structured point content in post meta.
				$element[ $key ] = base64_encode( is_numeric( $value[ $i ] ) ? floatval( $value[ $i ] ) : sanitize_text_field( $value[ $i ] ) );
			} else {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encoded to preserve structured point content in post meta.
				$element[ $key ] = base64_encode( sanitize_text_field( $value[ $i ] ) );
			}
		}
		array_push( $a_output, $element );
	}

	return $a_output;
}

if ( ! function_exists( 'image_points_get_image_info_from_url' ) ) {
	/**
	 * Get attachment metadata from an image URL.
	 *
	 * @param string $image_url Image URL.
	 * @return array|false
	 */
	function image_points_get_image_info_from_url( $image_url ) {
		global $wpdb;

		$cache_key     = 'image_points_media_info_' . md5( $image_url );
		$attachment_id = wp_cache_get( $cache_key );

		if ( false === $attachment_id ) {
			$upload_dir    = wp_upload_dir();
			$relative_path = str_replace( $upload_dir['baseurl'] . '/', '', $image_url );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching - No WordPress function exists to get attachment by file path, caching added above
			$attachment_id = $wpdb->get_var(
				$wpdb->prepare(
					"
                SELECT post_id FROM {$wpdb->postmeta}
                WHERE meta_key = '_wp_attached_file'
                AND meta_value = %s
                LIMIT 1
            ",
					$relative_path
				)
			);

			wp_cache_set( $cache_key, $attachment_id, '', 3600 );
		}

		if ( ! $attachment_id ) {
			return false;
		}

		$alt     = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$title   = get_the_title( $attachment_id );
		$caption = wp_get_attachment_caption( $attachment_id );
		$desc    = get_post_field( 'post_content', $attachment_id );

		return array(
			'ID'      => $attachment_id,
			'alt'     => $alt,
			'title'   => $title,
			'caption' => $caption,
			'desc'    => $desc,
		);
	}
}
