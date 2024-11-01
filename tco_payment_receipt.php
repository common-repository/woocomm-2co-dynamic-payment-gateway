<?php
class WCTCO_Shortcode_payment {
    public static function get($atts) {
        global $woocommerce;
        return $woocommerce->shortcode_wrapper(array(__CLASS__, 'output'), $atts);
    }
    public static function output($atts) {
        echo showMessage('error', '2Checkout payment failure. Click <a href="' . get_permalink(woocommerce_get_page_id('shop')) . '">here</a> to continue');
    }
}
class WCTCO_Shortcode_received{
      public static function get($atts) {
        global $woocommerce;
        return $woocommerce->shortcode_wrapper(array(__CLASS__, 'output'), $atts);
    }
    public static function output($atts) {          
        global $woocommerce;
        $woocommerce->show_messages();
        $order = false;
        // Get the order
        $order_id  = apply_filters( 'woocommerce_thankyou_order_id', empty( $_GET['order'] ) ? 0 : absint( $_GET['order'] ) );
        $order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : woocommerce_clean( $_GET['key'] ) );
        if ( $order_id > 0 ) {
                $order = new WC_Order( $order_id );
                if ( $order->order_key != $order_key )
                        unset( $order );
        }
        // Empty awaiting payment session
        unset( $woocommerce->session->order_awaiting_payment );       
        woocommerce_get_template( 'checkout/thankyou.php', array( 'order' => $order ) );
        $order->payment_complete();           
    }
}