<?php
function do_tco_fill_anyway($a, $b) {
    global $tco_shipping_need;
    if (trim($a) == '')
        return $b;
    $tco_shipping_need = TRUE;
    return $a;
}

function do_tco_payment_action($order, $_this) {
    include_once ('tco_payment_class.php');
    $newSale = new TCO_Payment();
    $debug = 'N';
    if ($_this->settings['debug'] == 'yes')
        $debug = 'Y';
    $newSale->setAcctInfo($_this->settings['storeNo'], $_this->settings['secretword'], $debug);
    $newSale->setCheckout('multi_page');

    $newSale->addParam('mode', '2CO');
    $newSale->addParam('li_0_type', 'product');
    $newSale->addParam('li_0_name', 'Cart Purchase:' . $order->order_key);
    $newSale->addParam('li_0_price', $order->order_total);
    $newSale->addParam('li_0_product_id', $order->order_key);
    $newSale->addParam('li_0_tangible', 'N');

    //Customer Billing Information
    $newSale->addParam('first_name', $order->billing_first_name);
    $newSale->addParam('last_name', $order->billing_last_name);
    $newSale->addParam('email', $order->billing_email);
    $newSale->addParam('phone', $order->billing_phone);
    $newSale->addParam('street_address', $order->billing_address_1);
    $newSale->addParam('street_address2', $order->billing_address_2);
    $newSale->addParam('city', $order->billing_city);
    $newSale->addParam('state', $order->billing_state);
    $newSale->addParam('zip', $order->billing_postcode);
    $newSale->addParam('country', $order->billing_country);
    global $tco_shipping_need;
    $tco_shipping_need = FALSE;
    $newSale->addParam('ship_name', do_tco_fill_anyway($order->shipping_first_name, $order->billing_first_name) . ' ' . do_tco_fill_anyway($order->shipping_last_name, $order->billing_last_name));
    $newSale->addParam('ship_street_address', do_tco_fill_anyway($order->shipping_address_1, $order->billing_address_1));
    $newSale->addParam('ship_street_address2', do_tco_fill_anyway($order->shipping_address_2, $order->billing_address_2));
    $newSale->addParam('ship_city', do_tco_fill_anyway($order->shipping_city, $order->billing_city));
    $newSale->addParam('ship_state', do_tco_fill_anyway($order->shipping_state, $order->billing_state));
    $newSale->addParam('ship_zip', do_tco_fill_anyway($order->shipping_postcode, $order->billing_postcode));
    $newSale->addParam('ship_country', do_tco_fill_anyway($order->shipping_country, $order->billing_country));
    if (!$tco_shipping_need)
        unset($newSale->params['ship_name'], $newSale->params['ship_street_address'], $newSale->params['ship_street_address2'], $newSale->params['ship_city'], $newSale->params['ship_state'], $newSale->params['ship_zip'], $newSale->params['ship_country']);

	
    $_url = add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('thanks'))));
    if (strpos($_url, '?') !== FALSE)
        $_url.='&tcoid=' . md5('successful');
    else
        $_url.='?tcoid=' . md5('successful');
    $newSale->addParam('x_receipt_link_url', $_url);
    $newSale->addParam('return_url', $_url);
    $_url = add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))));
    if (strpos($_url, '?') !== FALSE)
        $_url.='&cancleID=' . md5('cancled');
    else
        $_url.='?cancleID=' . md5('cancled');
    $newSale->addParam('cancel_return', $_url);
    $newSale->addParam('pay_method', 'CC');
    $newSale->addParam('skip_landing', '1');
    $newSale->submitPayment();
}