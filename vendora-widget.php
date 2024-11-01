<?php
/*
Plugin Name: Vendora.gr Widget & Shortcode
Plugin URI: https://support.vendora.gr/wordpress-plugin
Description: This plugin adds a custom widget and shortcode to display your Vendora ads on your own site
Version: 1.0.2
Author: Vendora.gr
Author URI: https://vendora.gr
License: GPL2
*/

class Vendora_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'vendora_widget',
            __('Vendora.gr Widget', 'text_domain'),
            array(
                'customize_selective_refresh' => true,
            )
        );
    }

    public function form( $instance ) {
        // Set widget defaults
        $defaults = array(
            'title' => 'Vendora.gr',
            'user' => '',
            'size' => '12'
        );

        // Parse current settings with defaults
        extract( wp_parse_args( ( array ) $instance, $defaults ) ); ?>

        <?php // Widget Title ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Widget Title', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
        </p>

        <?php // User ?>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'user' ) ); ?>"><?php _e( 'User ID or URL', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'user' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'user' ) ); ?>" type="text" value="<?php echo esc_attr( $user ); ?>" /><br>
            <small>E.g. &quot;https://vendora.gr/users/a0b1c2&quot;</small>
        </p>

        <?php // Size ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'size' ); ?>"><?php _e( 'Size', 'text_domain' ); ?></label>
            <select name="<?php echo $this->get_field_name( 'size' ); ?>" id="<?php echo $this->get_field_id( 'size' ); ?>" class="widefat">
            <?php
            $options = array(
                ''        => __( 'Select', 'text_domain' ),
                '12' => '12',
                '24' => '24',
                '36' => '36',
                '48' => '48'
            );

            foreach ( $options as $key => $name ) {
                echo '<option value="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" '. selected( $size, $key, false ) . '>'. $name . '</option>';
            } ?>
            </select>
        </p>

        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        $user = isset( $new_instance['user'] ) ? vendora_str_to_user($new_instance['user']) : '';
        $size = isset( $new_instannce['size'] ) ? vendora_sanitize_size($new_instance['size']) : 12;

        $instance['title'] = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
        $instance['user'] = $user;
        $instance['size'] = $size;
        return $instance;
    }

    public function widget( $args, $instance ) {

        extract( $args );

        $title = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
        $user = isset( $instance['user'] ) ? $instance['user'] : '';
        $size = isset( $instance['size'] ) ? $instance['size'] : 12;

        $params = http_build_query([
            'user' => $user,
            'size' => $size
        ]);

        echo $before_widget;

        echo '<div class="widget-text wp_widget_plugin_box">';

        if ( $title ) {
            echo $before_title . $title . $after_title;
        }

        wp_enqueue_script('vendora_widget');

        echo '<div class="vendora-widget" data-params="'.htmlspecialchars($params).'"></div>';
        echo '<noscript><a href="https://vendora.gr/users/'.htmlspecialchars(urlencode($user)).'">'.__('View all my ads at', 'text_domain').' Vendora.gr</a></noscript>';
        echo '</div>';

        echo $after_widget;

    }

}

function vendora_str_to_user($str) {
    if (preg_match('/\/users\/([a-z0-9]{5,})/', $str, $match)) {
        return $match[1];
    } elseif (!preg_match('/^[a-z0-9]{5,}$/', $str)) {
        return $str;
    } else {
        return '';
    }
}

function vendora_sanitize_size($str) {
    return min(120, max(12, ceil(intval($str) / 12) * 12));
}

function vendora_register_widget() {
    register_widget( 'Vendora_Widget' );
}

function vendora_register_scripts() {
    wp_register_script(
        'vendora_widget',
        'https://vendora.gr/js/vendora-widget.js',
        null,
        null,
        true
    );
}

function vendora_shortcode( $atts ) {
    $a = shortcode_atts( array(
        'user' => '',
        'q' => '',
        'size' => '12',
    ), $atts );

    $a['user'] = vendora_str_to_user($a['user']);
    $a['size'] = vendora_sanitize_size($a['size']);
    $params = http_build_query(array_filter($a));

    wp_enqueue_script('vendora_widget');

    return '<div class="vendora-widget" data-params="'.htmlspecialchars($params).'"></div>' . 
        '<noscript><a href="https://vendora.gr/items?'.htmlspecialchars($params).'">Vendora.gr</a></noscript>';
}

add_action( 'widgets_init', 'vendora_register_widget' );
add_action( 'wp_enqueue_scripts', 'vendora_register_scripts' );
add_shortcode( 'vendora', 'vendora_shortcode' );
