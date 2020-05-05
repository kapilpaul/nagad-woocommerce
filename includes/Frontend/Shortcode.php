<?php

namespace DCoders\Nagad\Frontend;

/**
 * Class Shortcode
 * @package DCoders\Nagad\Frontend
 */
class Shortcode {

    public function __construct() {
        add_shortcode( 'vue-app', [ $this, 'render_frontend' ] );
    }

    /**
     * Render frontend app
     *
     * @param array $atts
     * @param string $content
     *
     * @return string
     */
    public function render_frontend( $atts, $content = '' ) {
        wp_enqueue_style( 'dc-nagad-frontend' );
        wp_enqueue_script( 'dc-nagad-frontend' );

        return $content;
    }
}
