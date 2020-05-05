<?php

namespace DCoders\Nagad;

/**
 * Scripts and Styles Class
 */
class Assets {
    /**
     * Assets constructor.
     */
    function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'register' ], 5 );
        } else {
            add_action( 'wp_enqueue_scripts', [ $this, 'register' ], 5 );
        }
    }

    /**
     * Register our app scripts and styles
     *
     * @return void
     */
    public function register() {
        $this->register_scripts( $this->get_scripts() );
        $this->register_styles( $this->get_styles() );
    }

    /**
     * Register scripts
     *
     * @param array $scripts
     *
     * @return void
     */
    private function register_scripts( $scripts ) {
        foreach ( $scripts as $handle => $script ) {
            $deps      = isset( $script['deps'] ) ? $script['deps'] : false;
            $in_footer = isset( $script['in_footer'] ) ? $script['in_footer'] : false;
            $version   = isset( $script['version'] ) ? $script['version'] : DC_NAGAD_VERSION;

            wp_register_script( $handle, $script['src'], $deps, $version, $in_footer );
        }
    }

    /**
     * Register styles
     *
     * @param array $styles
     *
     * @return void
     */
    public function register_styles( $styles ) {
        foreach ( $styles as $handle => $style ) {
            $deps = isset( $style['deps'] ) ? $style['deps'] : false;

            wp_register_style( $handle, $style['src'], $deps, DC_NAGAD_VERSION );
        }
    }

    /**
     * Get all registered scripts
     *
     * @return array
     */
    public function get_scripts() {
        $pluginJSAssetsPath = DC_NAGAD_ASSETS . '/js/';

        $scripts = [
            'dc-nagad-frontend' => [
                'src'       => $pluginJSAssetsPath . 'frontend.js',
                'deps'      => [ 'jquery' ],
                'version'   => filemtime( DC_NAGAD_PATH . '/assets/js/frontend.js' ),
                'in_footer' => true,
            ]
        ];

        return $scripts;
    }

    /**
     * Get registered styles
     *
     * @return array
     */
    public function get_styles() {
        $pluginJSAssetsPath = DC_NAGAD_ASSETS . '/css/';

        $styles = [
            'dc-nagad-frontend' => [
                'src' => $pluginJSAssetsPath . '/frontend.css',
            ],
            'dc-nagad-admin'    => [
                'src' => $pluginJSAssetsPath . '/admin.css',
            ],
        ];

        return $styles;
    }

}
