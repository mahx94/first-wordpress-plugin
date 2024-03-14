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
    }

    public function create_custom_post_type()
    {
        $args = array(
            'public' => true,
            'has_archive' => true, 
            'supports' => array('title'),
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'capability' => 'manage_options',
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
    {
        return 'Hello, the shortcode is working!';
    }

}

new SimpleContactForm;
