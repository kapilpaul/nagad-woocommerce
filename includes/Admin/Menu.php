<?php

namespace DCoders\Nagad\Admin;

/**
 * Admin Pages Handler
 *
 * Class Menu
 * @package DCoders\Nagad\Admin
 */
class Menu {
    /**
     * Menu constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
    }

    /**
     * Register our menu page
     *
     * @return void
     */
    public function admin_menu() {
        $parent_slug = 'dc-nagad';
        $capability  = 'manage_options';

        $hook = add_menu_page( __( 'Nagad Payments', 'dc-nagad' ), __( 'Nagad', 'dc-nagad' ), $capability, $parent_slug, [
            $this,
            'nagad_page',
        ], DC_NAGAD_ASSETS . '/images/nagad.png' );

        add_action( 'load-' . $hook, [ $this, 'init_hooks' ] );
    }

    /**
     * Initialize our hooks for the admin page
     *
     * @return void
     */
    public function init_hooks() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Load scripts and styles for the app
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 'dc-nagad-admin' );
    }

    /**
     * Render admin plugin page
     *
     * @return void
     */
    public function nagad_page() {
        $payments = new Payments();
        $payments->plugin_page();
    }
}
