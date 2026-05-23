<?php
/**
 * Frontend shortcode renderer for Image Points.
 *
 * @package Image_Points
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );

/**
 * Render an Image Points shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string|null
 */
function image_points_shortcode_func( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => '',
		),
		$atts,
		'image_points'
	);

	$id_post = intval( $atts['id'] );

	if ( 'publish' !== get_post_status( $id_post ) ) {
		return;
	}

	$data_post = image_points_get_post_data( $id_post );

	$image_points_main_image = ( isset( $data_post['image_points_main_image'] ) ) ? $data_post['image_points_main_image'] : '';
	$data_points             = ( isset( $data_post['data_points'] ) ) ? $data_post['data_points'] : '';

	if ( ! empty( $data_points ) ) {
		$data_points = image_points_get_decoded_data_points( $data_points );
	}

	$pins_image       = ( isset( $data_post['pins_image'] ) ) ? $data_post['pins_image'] : '';
	$pins_image_hover = ( isset( $data_post['pins_image_hover'] ) ) ? $data_post['pins_image_hover'] : '';

	$pins_more_option = isset( $data_post['pins_more_option'] ) && $data_post['pins_more_option'] ? $data_post['pins_more_option'] : array();

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

	$tooltip_trigger = ( isset( $data_post['tooltip_trigger'] ) ) ? $data_post['tooltip_trigger'] : 'click';
	$tooltip_theme   = ( isset( $data_post['tooltip_theme'] ) ) ? $data_post['tooltip_theme'] : 'dark';
	ob_start();
	if ( $image_points_main_image ) :
		?>
	<div class="wrap_svl_center">
	<div class="wrap_svl_center_box">
	<div class="wrap_svl" id="body_drag_<?php echo esc_attr( $id_post ); ?>" data-trigger="<?php echo esc_attr( $tooltip_trigger ); ?>" data-theme="<?php echo esc_attr( $tooltip_theme ); ?>">
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
				<?php
				$allowed_tags = image_points_get_allowed_tags();
				$stt = 1;foreach ( $data_points as $point ) :
					$current_pins_image       = $pins_image;
					$current_pins_image_hover = $pins_image_hover;

					$linkpins                = isset( $point['linkpins'] ) ? esc_url( $point['linkpins'] ) : '';
					$link_target             = isset( $point['link_target'] ) ? esc_attr( $point['link_target'] ) : '_self';
					$pins_image_custom       = isset( $point['pins_image_custom'] ) ? esc_url( $point['pins_image_custom'] ) : '';
					$pins_image_hover_custom = isset( $point['pins_image_hover_custom'] ) ? esc_url( $point['pins_image_hover_custom'] ) : '';
					$placement               = ( isset( $point['placement'] ) && '' !== $point['placement'] ) ? esc_attr( $point['placement'] ) : 'n';
					$pins_id                 = ( isset( $point['pins_id'] ) && '' !== $point['pins_id'] ) ? esc_attr( $point['pins_id'] ) : '';
					$pins_class              = ( isset( $point['pins_class'] ) && '' !== $point['pins_class'] ) ? esc_attr( $point['pins_class'] ) : '';
					$pinsalt                 = ( isset( $point['pinsalt'] ) && '' !== $point['pinsalt'] ) ? esc_attr( $point['pinsalt'] ) : '';

					if ( $pins_image_custom ) {
						$current_pins_image = $pins_image_custom;
					}
					if ( $pins_image_hover_custom ) {
						$current_pins_image_hover = $pins_image_hover_custom;
					}

					$no_tooltip = false;
					ob_start();
					?>
					<?php if ( isset( $point['content'] ) ) : ?>
						<?php
						if ( ! empty( $point['content'] ) ) :
							$point_content = str_replace( '\"', '"', $point['content'] );
							$point_content = wp_kses( $point_content, $allowed_tags );
							?>
				<div class="box_view_html">
					<?php echo wp_kses( wpautop( $point_content ), $allowed_tags ); ?>
				</div>
							<?php
			else :
				$no_tooltip = true;
			endif;
			?>
		<?php endif; ?>
					<?php
					$view_html = trim( ob_get_clean() );
					$point_classes = array(
						'point_style',
						'image_points_tooltip_html',
					);
					$pins_image_classes = array( 'pins_image' );

					if ( $current_pins_image_hover ) {
						$point_classes[] = 'has-hover';
					}

					if ( ! $no_tooltip ) {
						$point_classes[]      = 'image_points_hastooltip';
						$pins_image_classes[] = 'image_points_hastooltip';
					}
					?>
		<div class="drag_element tips <?php echo ( $pins_class ) ? esc_attr( $pins_class ) : ''; ?>" style="top:<?php echo esc_attr( $point['top'] ); ?>%;left:<?php echo esc_attr( $point['left'] ); ?>%;" <?php echo ( $pins_id ) ? 'id="' . esc_attr( $pins_id ) . '"' : ''; ?>>
			<div class="<?php echo esc_attr( implode( ' ', $point_classes ) ); ?>" data-placement="<?php echo esc_attr( $placement ); ?>" data-html='<?php echo esc_attr( $view_html ); ?>'>
					<?php
					if ( $linkpins ) :
						?>
						<a href="<?php echo esc_attr( $linkpins ); ?>" title="" <?php echo ( $link_target ) ? 'target="' . esc_attr( $link_target ) . '"' : ''; ?>><?php endif; ?>
					<?php if ( 'none' !== $pins_more_option['pins_animation'] ) : ?>
						<div class="pins_animation image_points_<?php echo esc_attr( $pins_more_option['pins_animation'] ); ?>" style="top:-<?php echo esc_attr( $pins_more_option['custom_top'] ); ?>px;left:-<?php echo esc_attr( $pins_more_option['custom_left'] ); ?>px;height:<?php echo intval( $pins_more_option['custom_top'] * 2 ); ?>px;width:<?php echo intval( $pins_more_option['custom_left'] * 2 ); ?>px"></div>
					<?php endif; ?>
					<img src="<?php echo esc_attr( $current_pins_image ); ?>" class="<?php echo esc_attr( implode( ' ', $pins_image_classes ) ); ?>" style="top:-<?php echo esc_attr( $pins_more_option['custom_top'] ); ?>px;left:-<?php echo esc_attr( $pins_more_option['custom_left'] ); ?>px" alt="<?php echo esc_attr( $pinsalt ); ?>">
					<?php
					if ( $current_pins_image_hover ) :
						$pins_image_hover_classes = array( 'pins_image_hover' );
						if ( ! $no_tooltip ) {
							$pins_image_hover_classes[] = 'image_points_hastooltip';
						}
						?>
						<img src="<?php echo esc_attr( $current_pins_image_hover ); ?>" class="<?php echo esc_attr( implode( ' ', $pins_image_hover_classes ) ); ?>" style="top:-<?php echo esc_attr( $pins_more_option['custom_hover_top'] ); ?>px;left:-<?php echo esc_attr( $pins_more_option['custom_hover_left'] ); ?>px" alt="<?php echo esc_attr( $pinsalt ); ?>"><?php endif; ?>
					<?php
					if ( $linkpins ) :
						?>
						</a><?php endif; ?>
			</div>
		</div>
					<?php
					++$stt;
endforeach;
				?>
						<?php endif; ?>
	</div>
	</div>
	</div>
		<?php
	endif;
	return ob_get_clean();
}
add_shortcode( 'image_points', 'image_points_shortcode_func' );
