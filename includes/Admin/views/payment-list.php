<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e( 'Nagad Payments List', 'dc-nagad' ); ?>
        <img src="<?php echo DC_NAGAD_ASSETS . '/images/nagad.png'; ?>" alt="">
    </h1>

    <?php if ( isset( $_GET['inserted'] ) ) { ?>
        <div class="notice notice-success">
            <p><?php _e( 'Payment has been added successfully!', 'dc-nagad' ); ?></p>
        </div>
    <?php } ?>

    <?php if ( isset( $_GET['payment-deleted'] ) && $_GET['payment-deleted'] == 'true' ) { ?>
        <div class="notice notice-success">
            <p><?php _e( 'Payment has been deleted successfully!', 'dc-nagad' ); ?></p>
        </div>
    <?php } ?>

    <form action="" method="post">
        <p style="float: left">All list of payments made with Nagad</p>

		<?php
        $table = new \DCoders\Nagad\Admin\Payment_List();
        isset( $_POST['s'] ) ? $table->prepare_items( $_POST['s'] ) : $table->prepare_items();
		$table->search_box( 'Search', 'nagadpay' );
		$table->display();
		?>
    </form>
</div>
