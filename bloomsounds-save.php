<?php
/*
Plugin Name: BloomSounds Save
Plugin URI: http://gingersoulrecords.com
Description: Save mixes
Version: 0.1.0
Author: Dave Bloom
Author URI:  http://gingersoulrecords.com
*/

add_action( 'plugins_loaded', array( 'BloomSounds_Save', 'init' ) );
class BloomSounds_Save {
	public static function init() {
		add_action( 'init', 										array( 'BloomSounds_Save', 'cpt' ) );
		add_action( 'wp_enqueue_scripts', 			array( 'BloomSounds_Save', 'scripts' ) );
		add_action( 'wp_enqueue_scripts', 			array( 'BloomSounds_Save', 'conditional_scripts' ) );
		add_action( 'wp_ajax_bloomsounds_save', array( 'BloomSounds_Save', 'ajax' ) );
		add_shortcode( 'bloomsounds', 					array( 'BloomSounds_Save', 'shortcode' ) );
	}
	public static function conditional_scripts() {
		if ( !function_exists( 'rcp_is_active' ) ) {
			return false;
		}
		wp_register_script( 'bloomsounds-pro', plugins_url( 'bloomsounds-pro.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_register_script( 'bloomsounds-plain', plugins_url( 'bloomsounds-plain.js', __FILE__ ), array( 'jquery' ), false, true );
		wp_register_script( 'bloomsounds-subscriptionjs', plugins_url( 'bloomsounds-subscription.js', __FILE__ ), array( 'jquery' ), false, true );
		
		wp_enqueue_script( 'bloomsounds-subscriptionjs' );
		
		if ( rcp_is_active() ) {
			wp_enqueue_script( 'bloomsounds-pro' );
		} else {
			wp_enqueue_script( 'bloomsounds-plain' );			
		}
	}
	public static function cpt() {
		$args = array(
      'public' => false,
			'show_ui'=> true,
      'label'  => __( 'Sets', 'bloomsounds-save' ),
			'supports'	=> array( 'title', 'custom-fields', 'author' ),
    );
    register_post_type( 'bloomsounds', $args );
	}
	public static function scripts() {
		wp_register_script( 'bloomsounds_save', plugins_url( 'bloomsounds-save.js', __FILE__ ), array( 'jquery' ) );
		$data = array(
			'ajaxurl' 		=> admin_url( 'admin-ajax.php' ),
			'title' 			=> __( 'Please enter a title for the set:'. 'bloomsounds-save' ),
			'placeholder' => __( 'My Set Title', 'bloomsounds-save' ),
			'item' 				=> '<li><a href="%link%">%title%</a></li>',
		);
		wp_localize_script( 'bloomsounds_save', 'bloomsounds_save_data', $data );
		wp_enqueue_script( 'bloomsounds_save' );
	}
	public static function ajax() {
		$args = array(
			'post_type' 	=> 'bloomsounds',
			'post_status'	=> 'publish',
			'post_author' => get_current_user_id(),
			'post_title' 	=> $_REQUEST['title'] ? $_REQUEST['title'] : date('Y-m-d h:i:s'),
		);
		$post_id  = wp_insert_post( $args );
		update_post_meta( $post_id, 'bloomsounds_data', $_REQUEST['link'] );
		$data = array(
			'id'	 	=> 	$post_id,
			'link' 	=> $_REQUEST['link'],
			'title' => $args['post_title'],
		);
		wp_send_json_success( $data );
		die();
	}
	public static function shortcode( $args, $content='' ) {
		$return = '<ul class="bloomsounds-save">';
		$user_id = get_current_user_id();
		$args = array(
			'post_type' => 'bloomsounds',
			'post_status' => 'publish',
			'posts_per_page'	=> 999999,
			'author'	=> $user_id,
		);
		$my_query = new WP_Query( $args );
		while ( $my_query->have_posts() ) : $my_query->the_post();
			$return .= "<li><a href=\"".get_post_meta( $my_query->post->ID, 'bloomsounds_data', true )."\">".get_the_title( $my_query->post->ID )."</a></li>";
		endwhile;
		$return .= '</ul>';
		return $return;
	}
}
