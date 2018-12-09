<?php
/*
Plugin Name: DownBeat Save
Plugin URI: http://gingersoulrecords.com
Description: Save mixes
Version: 0.2.0
Author: Dave Bloom
Author URI:  http://gingersoulrecords.com
*/

class DownBeat_Save {

    /**
     * Initialize Class
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'cpt' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'conditional_scripts' ) );
        add_action( 'wp_ajax_downbeat_save', array( __CLASS__, 'ajax' ) );
        add_shortcode( 'downbeat', array( __CLASS__, 'shortcode' ) );
    }

    /**
     * Register custom post type `downbeat`
     */
    public static function cpt() {
        $args = array(
            'public' => false,
            'show_ui'=> true,
            'label'  => __( 'Sets', 'downbeat-save' ),
            'supports'	=> array( 'title', 'custom-fields', 'author' ),
        );
        register_post_type( 'downbeat', $args );
    }


    /**
     * Enqueue frontend scripts and stylesheets conditionally - required Restrict Content Pro plugin to be active
     */
    public static function conditional_scripts() {

        if ( !function_exists( 'rcp_is_active' ) ) {
            return false;
        }

        wp_register_script( 'downbeat-pro', plugins_url( 'assets/js/downbeat-pro.js', __FILE__ ), array( 'jquery' ), false, true );
        wp_register_script( 'downbeat-plain', plugins_url( 'assets/js/downbeat-plain.js', __FILE__ ), array( 'jquery' ), false, true );
        wp_register_script( 'downbeat-subscriptionjs', plugins_url( 'assets/js/downbeat-subscription.js', __FILE__ ), array( 'jquery' ), false, true );

        wp_enqueue_script( 'downbeat-subscriptionjs' );

        if ( rcp_is_active() ) {
            wp_enqueue_script( 'downbeat-pro' );
        } else {
            wp_enqueue_script( 'downbeat-plain' );
        }
    }

    /**
     * Enqueue front end scripts
     */
    public static function scripts() {
        wp_register_script( 'downbeat_save', plugins_url( 'assets/js/downbeat-save.js', __FILE__ ), array( 'jquery' ) );
        $data = array(
            'ajaxurl' 		=> admin_url( 'admin-ajax.php' ),
            'title' 			=> __( 'Please enter a title for the set:'. 'downbeat-save' ),
            'placeholder' => __( 'My Set Title', 'downbeat-save' ),
            'item' 				=> '<li><a href="%link%">%title%</a></li>',
        );
        wp_localize_script( 'downbeat_save', 'downbeat_save_data', $data );
        wp_enqueue_script( 'downbeat_save' );
    }

    /**
     * Handle frontend AJAX requests sent to action `downbeat_save`
     */
    public static function ajax() {
        $args = array(
            'post_type' 	=> 'downbeat',
            'post_status'	=> 'publish',
            'post_author' => get_current_user_id(),
            'post_title' 	=> $_REQUEST['title'] ? sanitize_text_field($_REQUEST['title']) : date('Y-m-d h:i:s'),
        );
        $post_id  = wp_insert_post( $args );
        update_post_meta( $post_id, 'downbeat_data', esc_url_raw($_REQUEST['link']) );
        $data = array(
            'id'	 	=> 	$post_id,
            'link' 	=> esc_url_raw($_REQUEST['link']),
            'title' => $args['post_title'],
        );
        wp_send_json_success( $data );
        die();
    }

    /**
     * Supports the `downbeat` shortcode
     * @param $args
     * @param string $content
     * @return string
     */
    public static function shortcode( $args, $content='' ) {
        $return = '<ul class="downbeat-save">';
        $user_id = get_current_user_id();
        $args = array(
            'post_type' => 'downbeat',
            'post_status' => 'publish',
            'posts_per_page'	=> 999999,
            'author'	=> $user_id,
        );
        $my_query = new WP_Query( $args );
        while ( $my_query->have_posts() ) : $my_query->the_post();
            $return .= "<li><a href=\"".get_post_meta( $my_query->post->ID, 'downbeat_data', true )."\">".get_the_title( $my_query->post->ID )."</a></li>";
        endwhile;
        $return .= '</ul>';
        return $return;
    }

}

/* Initialize DownBeat_Save Class */
add_action( 'plugins_loaded', array( 'DownBeat_Save', 'init' ) );
