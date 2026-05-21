<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

add_action( 'admin_init', 'image_points_register_mysettings' );
function image_points_register_mysettings() {
    register_setting( 'image-points-options-group','image_points_options', array(
        'sanitize_callback' => 'image_points_sanitize_options'
    ) );
}

function image_points_sanitize_options( $input ) {
    $sanitized = array();
    
    if ( isset( $input['popup_type'] ) ) {
        $popup_type = absint( $input['popup_type'] );
        if ( in_array( $popup_type, array( 1, 2 ), true ) ) {
            $sanitized['popup_type'] = $popup_type;
        } else {
            $sanitized['popup_type'] = 1;
        }
    } else {
        $sanitized['popup_type'] = 1;
    }
    
    return $sanitized;
}

add_action( 'admin_menu', 'image_points_admin_menu' );
function image_points_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=image_points',
        __( 'Image Points settings', 'image-points' ),
        __( 'Settings', 'image-points' ),
        'manage_options',
        'image-points',
        'image_points_callback'
    );
}

function image_points_callback(){
    $popup_type = image_points_get_options('popup_type');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Image Points settings', 'image-points');?></h1>
        <form method="post" action="options.php" novalidate="novalidate">
            <?php settings_fields( 'image-points-options-group' );?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Popup type on mobile', 'image-points')?></label></th>
                    <td>
                        <div class="tet_style_radio tet_style_radio_banner">
                            <label style="margin-right: 10px;">
                                <input type="radio" name="image_points_options[popup_type]" value="2" <?php checked('2', $popup_type);?>> <?php esc_html_e('Full Screen', 'image-points');?>
                            </label>
                            <label>
                                <input type="radio" name="image_points_options[popup_type]" value="1" <?php checked('1', $popup_type);?>> <?php esc_html_e('Normal - Tooltip', 'image-points');?>
                            </label>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php do_settings_sections('image-points-options-group'); ?>

            <?php submit_button();?>
        </form>
        <p><strong>Buy me a Coffee to keep me awake :)</strong></p>
        <?php echo image_points_donate_shortcode_callback();//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped	?>
    </div>
<?php
}

function image_points_action_links( $links, $file ) {
    if ( strpos( $file, 'image-points.php' ) !== false ) {
        $donate_link = '<a class="image-points-donate-link" href="' . esc_url( 'https://paypal.me/nauhyuh99' ) . '" title="' . esc_attr__( 'Donate', 'image-points' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Donate', 'image-points' ) . '</a>';
        array_unshift( $links, $donate_link );
    }
    return $links;
}
add_filter( 'plugin_action_links_' . IMAGE_POINTS_BASENAME, 'image_points_action_links', 10, 2 );

function image_points_plugin_action_styles() {
    ?>
    <style>
        .plugins a.image-points-donate-link {
            align-items: center;
            color: #b32d2e;
            display: inline-flex;
            font-weight: 600;
            gap: 3px;
        }

        .plugins a.image-points-donate-link:hover,
        .plugins a.image-points-donate-link:focus {
            color: #8a2424;
        }

        .plugins a.image-points-donate-link .dashicons {
            font-size: 16px;
            height: 16px;
            line-height: 1;
            width: 16px;
        }
    </style>
    <?php
}
add_action( 'admin_head-plugins.php', 'image_points_plugin_action_styles' );

function image_points_get_options($name = ''){
    $options = wp_parse_args(get_option('image_points_options'),array(
        'popup_type' => 1,
    ));
    if($name){
        return (isset($options[$name]) && $options[$name]) ? $options[$name] : '';
    }
    return $options;
}

add_filter( 'body_class', 'image_points_body_class' );
function image_points_body_class( $classes ) {
    $popup_type = image_points_get_options('popup_type');
    if ( $popup_type == 2 ) {
        $classes[] = 'image_points_popup_full';
    }
    return $classes;
}
