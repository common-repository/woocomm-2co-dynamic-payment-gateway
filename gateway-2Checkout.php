<?php
/**
 * Plugin Name: 2Checkout Dynamic Payment Gateway for WooCommerce 
 * Plugin URI: http://themejung.com/shop/wp-plugins/2checkout-dynamic-payment-gateway-for-woocommerce/
 * Description: Integrate 2checkout dynamic payment gateway to your woocommerce powered website.
 * Version: 1.1
 * Author: Vinoj Randika @ThemeJung
 * Author URI: http://themejung.com/
 */

add_action('plugins_loaded', 'woocommerce_2checkout_commerce_init', 0);

function woocommerce_2checkout_commerce_init() {    
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    };
    
    if(isset($_GET['msg'])&&$_GET['msg']!=''){
        add_action('the_content', 'showMessage');
    }
    if(!function_exists('showMessage')){
        function showMessage($type,$content){
            return '<div class="woocommerce-'.$type.'">'.$content.'</div>';
        }
   }   
    /**
     * 2Checkout Commerce Gateway Class
    */
    class WC_2Checkout extends WC_Payment_Gateway {
        protected $msg = array();
        function __construct() {     
              global $post;
              $this->id	= 'TJ_2Checkout_Dynamic';
	      $this->has_fields = false;
              $this->supports   = array(
               'products',
               'subscriptions',
               'subscription_cancellation',
               'subscription_suspension',
               'subscription_reactivation',
               'subscription_date_changes',
             );    
            
            $this->icon =plugin_dir_url(__FILE__).'tj-wc2co_icons.png'; 
              
            $this->method_title = __( '2CO Payment Gateway - Dynamic', 'iflex' );
            $this->method_description =__( '2checkout works by adding credit card fields on the checkout and then sending the details to 2checkout for verification.', 'iflex' );
            
            $this -> init_form_fields();
            $this -> init_settings();
            
             foreach ($this->settings as $key => $val)
                $this->$key = $val;
             
            $this -> msg['message'] = "";
            $this -> msg['class']   = "";

            if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
                 add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            } else {
                add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
            }
            add_action('woocommerce_success_handler_'.$this->id,array($this, 'success_receipt_page',2));
             
              
            
            if(is_object($post))
              $this->receipt_page($this->id,$post->ID);
            
            $this->ischeckout_success();   
        } 
        function ischeckout_success(){
              global $post,$woocommerce;
            if($this->check_get_option('TWC-2checkout','complete')&&woocommerce_get_page_id('thanks')==$post->ID){
                $parts = parse_url($_GET['x_receipt_link_url']);
                parse_str($parts['query'], $query);               
                $order = new WC_Order($query['order']);
              
                $order->payment_complete();                 
                
		$woocommerce->cart->empty_cart();                
            }
        }
        function edit_payment_methods($content){}
        
        function check_get_option($key,$value){
            if(isset($_GET[$key])&&trim($_GET[$key])==$value)return TRUE;
            return FALSE;
        }
        
        function write_log($msg, $order) {
            $line = '';
            $file_ = @fopen(dirname(__FILE__) . '/wc2co_log.txt', "a");
            @fwrite($file_, $line . $msg . '  ' . $order->order_key . '  ' . $order->billing_email . '  ' . $order->completed_date);
            @fclose($file_);
        }

        function receipt_page($_id,$pID){
           if($this->check_get_option('cancleID',md5('cancled'))&& $pID==woocommerce_get_page_id('pay' )) {
                include_once dirname(__FILE__).'/tco_payment_receipt.php';
                $order = false;
                $order_id  = apply_filters( 'woocommerce_thankyou_order_id', empty( $_GET['order'] ) ? 0 : absint( $_GET['order'] ) );
                $order_key = apply_filters( 'woocommerce_thankyou_order_key', empty( $_GET['key'] ) ? '' : woocommerce_clean( $_GET['key'] ) );
                if ( $order_id > 0 ) {
                        $order = new WC_Order( $order_id );
                        if ( $order->order_key != $order_key )
                                unset( $order );
                }
                $this->write_log('2checkout payment failure',$order);
                add_shortcode( 'woocommerce_pay', array( $this, 'payment_failings' ) );
           }else  if($this->check_get_option('tcoid',md5('successful'))&& $pID==woocommerce_get_page_id('thanks' )) {
                include_once dirname(__FILE__).'/tco_payment_receipt.php';
                if(isset($_GET['key'])&&trim($_GET['key'])!='')
                     do_action('woocommerce_success_handler_'.$_id);
           }
        }
        
        function thankyou_page(){
            global $woocommerce;
	    return $woocommerce->shortcode_wrapper( array( 'WCTCO_Shortcode_received', 'output' ) );           
        }
        
        function payment_failings(){
              global $woocommerce;
	      return $woocommerce->shortcode_wrapper( array( 'WCTCO_Shortcode_payment', 'output' ) );
        }
        
        function init_form_fields() {
             $_tpmp=get_permalink(woocommerce_get_page_id('thanks'));
            if(strpos($_tpmp,'?')===FALSE)$_tpmp=$_tpmp.'?';
            else $_tpmp=$_tpmp.'&';
            $_url='<span style="color: #378EC4;margin-left: 10px;text-decoration: underline;">'.$_tpmp.'TWC-2checkout=complete'.'</span>
                   <div style="color: #7D7B7B;font: italic 12px sans-serif;margin-left: 50px;">Set url in your 2checkout account.</div>';
            $_urlC='<span style="color: #378EC4;margin-left: 10px;text-decoration: underline;">'.$_tpmp.'TWC-2checkout=fail'.'</span>
                   <div style="color: #7D7B7B;font: italic 12px sans-serif;margin-left: 50px;">Set url in your 2checkout account.</div>';
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'iflex'),
                    'label' => __('Enable 2Checkout - Dynamic', 'iflex'),
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'iflex'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'iflex'),
                    'default' => __('2Checkout - Dynamic', 'iflex')
                ),
                'description' => array(
                    'title' => __('Description', 'iflex'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'iflex'),
                    'default' => 'Pay with 2Checkout, Paypal and Credit Card Supported.'
                ),
                'storeNo' => array(
                    'title' => __('Login/Store Number', 'iflex'),
                    'type' => 'text',
                    'description' => __('Get your Account Number credentials from 2Checkout.', 'iflex'),
                    'default' => ''
                ),
                'secretword' => array(
                    'title' => __('Secret Word', 'iflex'),
                    'type' => 'text',
                    'description' => __('Set by yourself.', 'iflex'),
                    'default' => ''
                ),
                'purchaseroutine' => array(
                    'title' => __('Purchase Routine', 'iflex'),
                    'type' => 'select',
                    'description' => __('For more information , see <a href="">here</a>.', 'iflex'),
                    'options' => array(
                        'standard' => 'Standard Purchase Routine',
                    ),
                    'default' => 'Authorize &amp; Capture'
                ),
                'testmode' => array(
                    'title' => __('Test Mode', 'iflex'),
                    'type' => 'checkbox',
                    'label' => __('Enable 2Checkout Test', 'iflex'),
                    'description' => __('Process transactions in Test Mode via the 2Checkout Test account.', 'iflex'),
                    'default' => 'no'
                ),
                'debug' => array(
                    'title' => __('Debug', 'iflex'),
                    'type' => 'checkbox',
                    'label' => __('Enable logging(woocommerce/logs/pm2co.txt)', 'iflex'),
                    'description' => __('', 'iflex'),
                    'default' => 'no'
                ),               
                'successpath' => array(
                    'title' => __('Success Url Path : '.$_url, 'iflex'),
                    'type' => 'title',
                    'description' => __('', 'iflex'),
                    'default' => ''
                )             
            );
        }
        
        function get_pages($title = false, $indent = true) {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title) $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                        // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while($has_parent) {
                        $prefix .=  ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                        // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
            return $page_list;
        }
        
        function process_payment($order_id){     
            $order = new WC_Order($order_id);
            include_once dirname(__FILE__).'/do_tco_payment.php';
            do_tco_payment_action($order,$this); 
          ?>  
            <script type="text/javascript">
                function myfunc () {}
                window.onload = myfunc;
               var frm = document.getElementById("tco_payment_submit_id");
               frm.submit();
            </script>
               <?php            
            exit();
        }
        
        function payment_fields(){
           if($this -> description) echo wpautop(wptexturize($this -> description));
        } 
        
        function success_receipt_page(){
             add_shortcode( 'woocommerce_thankyou', array( $this, 'thankyou_page' ) );             
        }
    }
    
     /**
     * Add the gateway to woocommerce
     */
    function add_2checkout_commerce_gateway($methods) {
        $methods[] = 'WC_2Checkout';
        return $methods;
    }
    
    add_filter( 'woocommerce_payment_gateways', 'add_2checkout_commerce_gateway' );
    
}