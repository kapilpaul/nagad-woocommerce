<?php

namespace DCoders\Nagad\Admin;

/**
 * Class Payments
 * @package DCoders\Nagad\Admin
 */
class Payments {
    /**
     * @return void
     */
    public function plugin_page() {
        $template = __DIR__ . '/views/payment-list.php';

        if ( file_exists( $template ) ) {
            include $template;
        }
    }
}
