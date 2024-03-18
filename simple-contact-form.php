<?php
/**
 * Plugin Name: Simple Contact Form
 * Description: Test plugin
 * Author: André
 * Version: 1.0.0
 * Text Domain: simple-contact-form
 */

if ( !defined('ABSPATH') ) {
    exit;
}

class SimpleContactForm {

    public function __construct()
    {
        // Create custom post type
        add_action( 'init', array($this, 'create_custom_post_type') );

        // Add assets (js, css, etc)
        add_action( 'wp_enqueue_scripts', array($this, 'load_assets') );

        // Add shortcode
        add_shortcode( 'contact-form', array($this, 'load_shortcode') );

        // Load javascript
        add_action( 'wp_footer', array($this, 'load_scripts') );

        // Register REST API
        add_action( 'rest_api_init', array($this, 'register_rest_api') );

        // Create meta boxes
        add_action( 'add_meta_boxes', array($this, 'create_meta_box') );

        // Make custom submission columns
        add_filter('manage_simple_contact_form_posts_columns', 'custom_submission_columns');

        // Fill custom submission columns
        add_action('manage_simple_contact_form_posts_custom_column', 'fill_submission_columns', 10, 2);
    }

    public function create_custom_post_type()
    {
        $args = array(
            'public' => true,
            'has_archive' => true, 
            'supports' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capabilities' => array('manage_options' => true, 'create_posts' => false),
            'map_meta_cap' => true,
            'labels' => array(
                'name' => 'Contact Form',
                'singular_name' => 'Contact Form Entry'
            ),
            'menu_icon' => 'dashicons-media-text',
        );

        register_post_type('simple_contact_form', $args);
    }

    public function load_assets()
    {
        wp_enqueue_style(
            'simple_contact_form',
            plugin_dir_url( __FILE__ ) . 'css/simple-contact-form.css',
            array(),
            1,
            'all'
        );

        wp_enqueue_script(
            'simple-contact-form',
            plugin_dir_url( __FILE__ ) . 'js/simple-contact-form.js',
            array('jquery'),
            1,
            true
        );
    }

    public function load_shortcode()
    { ?>
        <div id="form_success" style="font-weight:bold; color:green;"></div>
        <div id="form_error" style="font-weight:bold; color:red;"></div>
        <div class="simple-contact-form">
            <h1>Send us an email</h1>
            <p>Please fill in the form below<p>

            <form id="simple-contact-form__form">
                <div class="form-group mb-2"><input type="text" name="name" placeholder="Name" class="form-control"></div>
                <div class="form-group mb-2"><input type="email" name="email" placeholder="Email" class="form-control"></div>
                <div class="form-group mb-2"><input type="tel" name="phone" placeholder="Phone" class="form-control"></div>
                <div class="form-group mb-2"><textarea name="message" placeholder="Enter your message here." class="form-control"></textarea></div>
                <div class="form-group mb-2"><button type="submit" class="btn btn-success btn-block">Send Message</button></div>
            </form>
        </div>
    <?php }

    public function load_scripts()
    { ?>
        <script>
            let nonce = '<?php echo wp_create_nonce('wp_rest'); ?>';

            jQuery('#simple-contact-form__form').submit( function(event) {
                event.preventDefault();
                
                let form = jQuery(this);

                jQuery.ajax({
                    method: 'post',
                    url: '<?php echo get_rest_url(null, 'simple-contact-form/v1/send-email'); ?>',
                    headers: { 'X-WP-Nonce': nonce },
                    data: form.serialize(),
                    success: function(){
                        form.hide();
                        jQuery("#form_success").html("Your message was sent!").fadeIn();
                    },
                    error: function(){
                        jQuery("#form_error").html("There was an error submitting your form!").fadeIn();
                    }
                })
            });
        </script>
    <?php }

    public function register_rest_api()
    {
        register_rest_route( 'simple-contact-form/v1', 'send-email', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_contact_form'),
        ) );
    }

    public function handle_contact_form($data)
    {
        $headers = $data->get_headers();
        $params = $data->get_params();
        $nonce = $headers['x_wp_nonce'][0];

        if (!wp_verify_nonce( $nonce, 'wp_rest' )) {
            return new WP_REST_Response('Message not sent', 422);
        }

        $post_id = wp_insert_post( [
            'post_type' => 'simple_contact_form',
            'post_title' => $params['name'],
            'post_status' => 'publish'
        ] );
        
        if ($post_id) {
            foreach($params as $label => $value) {
                add_post_meta($post_id, $label, $value);
            }

            return new WP_REST_Response('Thank you for your email', 200);
        }

    }

    public function create_meta_box()
    {
        add_meta_box( 'custom_contact_form', 'Submission', array($this, 'display_submission'), 'simple_contact_form' );
    }

    public function display_submission()
    {
        $post_meta = get_post_meta(get_the_ID());
        unset($post_meta["_edit_lock"]);
        unset($post_meta["_edit_last"]);

        echo "<ul>";
        foreach($post_meta as $key => $value) {
            echo "<li><b>" . ucfirst($key) . ":</b> " . $value[0] . "</li>";
        }
        echo "</ul>";
    }

    public function custom_submission_columns($columns) 
    {
        $columns = array(
            'cb' => $columns['cb'],
            'name' => 'Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'message' => 'Message'
        );

        return $columns;
    }

    public function fill_submission_columns($column, $post_id)
    {
        switch($column) {
            case 'name':
                echo get_post_meta($post_id, 'name', true);
                break;
            case 'email':
                echo get_post_meta($post_id, 'email', true);
                break;
            case 'phone':
                echo get_post_meta($post_id, 'phone', true);
                break;
            case 'message':
                echo get_post_meta($post_id, 'message', true);
                break;
        }
    }

}

new SimpleContactForm;
