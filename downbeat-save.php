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
        add_action( 'init', array( __CLASS__, 'register_cpt' ) );
        add_action( 'add_meta_boxes',  array( __CLASS__, 'register_metaboxes' ) );
        add_action( 'save_post_downbeat',  array( __CLASS__, 'metabox_save_downbeat_config' ) , 10 , 1 );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'scripts' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'conditional_scripts' ) );
        add_action( 'wp_ajax_downbeat_load', array( __CLASS__, 'ajax_downbeat_load' ) );
        add_action( 'wp_ajax_downbeat_save', array( __CLASS__, 'ajax_downbeat_save' ) );
        add_action( 'wp_ajax_downbeat_update', array( __CLASS__, 'ajax_downbeat_update' ) );
        add_action( 'wp_ajax_downbeat_delete', array( __CLASS__, 'ajax_downbeat_delete' ) );
        add_shortcode( 'downbeat', array( __CLASS__, 'shortcode' ) );
    }

    /**
     * Register custom post type `downbeat`
     */
    public static function register_cpt() {
        $args = array(
            'public' => false,
            'show_ui'=> true,
            'label'  => __( 'Sets', 'downbeat-save' ),
            'supports'	=> array( 'title', 'custom-fields', 'author' ),
        );
        register_post_type( 'downbeat', $args );
    }

    /**
     * Register metaboxes
     */
    public static function register_metaboxes() {
        add_meta_box(
            'downbeat_config_field', // $id
            __('Downbeat Configuration String' , 'downbeat-save'), // $title
            array( __CLASS__ , 'metabox_display_downbeat_config'), // $callback
            'downbeat', // $screen
            'normal', // $context
            'high' // $priority
        );
    }

    /**
     * Metabox Callback: Renders field containing downbeat configuration screen
     */
    public static function metabox_display_downbeat_config() {
        global $post;

        $downbeat_config = get_post_meta( $post->ID, 'downbeat_config', true );
        ?>
        <input type="hidden" name="downbeat_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">
        <input name="downbeat_config" value="<?php echo $downbeat_config; ?>">
        <?php
    }

    /**
     * Saves additional metadat related to the
     */
    public static function metabox_save_downbeat_config( $post_id ) {

        if ( !wp_verify_nonce( $_POST['downbeat_nonce'], basename(__FILE__) ) ) {
            return $post_id;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }

        $downbeat_config = ($_REQUEST['downbeat_config']) ? $_REQUEST['downbeat_config'] : '';

        /* update post meta */
        update_post_meta( $post_id , 'downbeat_config' , santize_text_field($downbeat_config) );
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
        wp_register_script( 'downbeat-methods', plugins_url( 'assets/js/downbeat-methods.js', __FILE__ ), array( 'jquery' ) );
        wp_localize_script( 'downbeat-methods', 'dbwp',  array(
            'ajaxurl' 		=> admin_url( 'admin-ajax.php' )
        ) );
        wp_enqueue_script( 'downbeat-methods' );
    }

    /**
     * AJAX Handler: Loads Downbeat Sessions for logged in user
     */
    public static function ajax_downbeat_load() {

        /* if user is not logged in then return empty data set */
        if ( !is_user_logged_in() ) {
            $sessions_prepared = array();
            $sessions_prepared['error'] = 'user is not logged in';
            $sessions_prepared['sessions'] = array();
            wp_send_json_error( $sessions_prepared );
            die();
        }

        $current_user_id = get_current_user_id();

        $args = array(
            'post_per_page' => -1,
            'post_type' 	=> 'downbeat',
            'post_status'	=> 'publish',
            'author' => $current_user_id,
            'orderby'          => 'date',
            'order'            => 'DESC',
        );

        $sessions = get_posts( $args );
        $sessions_prepared = array();

        if (!$sessions) {
            $sessions_prepared['sessions'] = array();
            wp_send_json_success( $sessions_prepared );
            die();
        }

        $sessions_prepared = array();
        $sessions_prepared['sessions'] = array();

        foreach ($sessions as $key => $session) {
            $config = get_post_meta( $session->ID, 'downbeat_config' , true );
            $sessions_prepared['sessions'][$key]['id'] = $session->ID;
            $sessions_prepared['sessions'][$key]['title'] = $session->post_title;
            $sessions_prepared['sessions'][$key]['config'] = $config;
        }

        wp_send_json_success( $sessions_prepared );
        die();
    }

    /**
     * AJAX Handler: Saves a downbeat session
     */
    public static function ajax_downbeat_save() {

        /* if user is not logged in then return empty data set */
        if ( !is_user_logged_in() ) {
            $sessions_prepared = array();
            $sessions_prepared['error'] = 'user is not logged in';
            return wp_send_json_success( $sessions_prepared );
            die();
        }

        $args = array(
            'post_type' 	=> 'downbeat',
            'post_status'	=> 'publish',
            'post_author' => get_current_user_id(),
            'post_title' 	=> $_REQUEST['title'] ? sanitize_text_field($_REQUEST['title']) : date('Y-m-d h:i:s'),
        );

        $post_id  = wp_insert_post( $args );

        update_post_meta( $post_id, 'downbeat_config', sanitize_text_field($_REQUEST['config']) );

        $data = array(
            'id'	 	=> 	$post_id,
            'config' 	=> sanitize_text_field($_REQUEST['config']),
            'title' => $args['post_title'],
        );

        wp_send_json_success( $data );
        die();
    }

    /**
     * AJAX Handler: Updates a downbeat session.
     */
    public static function ajax_downbeat_update() {

        $session_id = (int) $_REQUEST['id'];

        /* Make sure this call request is coming from a user with ownership */
        if (!self::current_user_owns_session( $session_id )) {
            wp_send_json_error( array(
                'error' => 'Calling user does not own session ID',
            ) );
            die();
        }

        $args = array(
            'ID' => $session_id,
            'post_title' 	=> $_REQUEST['title'] ? sanitize_text_field($_REQUEST['title']) : date('Y-m-d h:i:s'),
        );

        wp_update_post( $args );

        update_post_meta( $session_id , 'downbeat_config', sanitize_text_field($_REQUEST['config']) );

        $data = array(
            'id'	 	=> 	$session_id,
        );

        wp_send_json_success( $data );
        die();
    }

    /**
     * AJAX Handler : Deletes a downbeat session
     */
    public static function ajax_downbeat_delete() {

        $session_id = (int) $_REQUEST['id'];

        /* Make sure this call request is coming from a user with ownership */
        if (!self::current_user_owns_session( $session_id )) {
            /* throw error */
            wp_send_json_error( array(
                'error' => 'Calling user does not own session ID',
            ) );
            die();
        }

        wp_trash_post( $session_id );

        $data = array(
            'id'	 	=> 	$session_id,
        );

        wp_send_json_success( $data );
        die();
    }

    /**
     * @param $session_id
     * @returns BOOL true when ownership verified
     */
    public static function current_user_owns_session( $session_id ) {

        $session = get_post($session_id);

        if ( (int) get_current_user_id() === (int) $session->post_author ) {
            return true;
        }

        return false;

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
            $return .= "<li><a href=\"".get_post_meta( $my_query->post->ID, 'downbeat_config', true )."\">".get_the_title( $my_query->post->ID )."</a></li>";
        endwhile;
        $return .= '</ul>';
        return $return;
    }

}

/* Initialize DownBeat_Save Class */
add_action( 'plugins_loaded', array( 'DownBeat_Save', 'init' ) );
