<?php

require_once(drupal_get_path( 'module', 'commerce_cardgate' ) . '/curolib/curo.php');
require_once(drupal_get_path( 'module', 'commerce_cardgate' ) . '/curolib/curo_transaction.php');

function _cgsettings( $settings = null, $base, $payment ) {

    $form = array();
    $currencies = $base . 'currencies';
    // Merge default settings into the stored settings array.
    $default_currency = variable_get( 'commerce_default_currency', 'EUR' );

    if ( empty( $settings['gatewayurl'] ) ) {
        $settings['gatewayurl'] = 'secure.curopayments.net';
    }
    if ( empty( $settings['testgatewayurl'] ) ) {
        $settings['testgatewayurl'] = 'secure-staging.curopayments.net';
    }

    $settings = ( array ) $settings + array(
        'merchantid' => '',
        'merchantkey' => '',
        'siteid' => '',
        'currency_code' => in_array( $default_currency, array_keys( $currencies() ) ) ? $default_currency : 'EUR',
        'server' => 'live',
        'omschrijving' => '',
        'pending' => '',
        'complete' => '',
        'cancel' => ''
    );
    $form['paymentcode'] = array(
        '#type' => 'hidden',
        '#default_value' => $payment,
    );
    if ( $base == 'commerce_cardgate_generic_' ) {
        $form['merchantid'] = array(
            '#type' => 'textfield',
            '#title' => t( 'Merchant ID' ),
            '#description' => t( 'The Merchant ID from your CardGate account.' ),
            '#default_value' => $settings['merchantid'],
            '#required' => TRUE,
        );
        $form['merchantkey'] = array(
            '#type' => 'textfield',
            '#title' => t( 'Merchant API Key' ),
            '#description' => t( 'The Merchant Key from your CardGate account.' ),
            '#default_value' => $settings['merchantkey'],
            '#required' => TRUE,
        );
        $form['siteid'] = array(
            '#type' => 'textfield',
            '#title' => t( 'Site ID' ),
            '#description' => t( 'The Site ID from your CardGate account.' ),
            '#default_value' => $settings['siteid'],
            '#required' => TRUE,
        );
        $form['gatewayurl'] = array(
            '#type' => 'textfield',
            '#title' => t( 'Gateway Url' ),
            '#description' => t( 'The gateway Url your data will be sent to.<br>For <b>live</b> transactions use the url <b>secure.curopayments.net</b>' ),
            '#default_value' => $settings['gatewayurl'],
            '#required' => TRUE,
        );
        $form['testgatewayurl'] = array(
            '#type' => 'textfield',
            '#title' => t( 'Test Gateway Url' ),
            '#description' => t( 'The gateway Url your data will be sent to.<br>For <b>test</b> transactions use the url <b>secure-staging.curopayments.net</b> <br>' ),
            '#default_value' => $settings['testgatewayurl'],
            '#required' => TRUE,
        );
        $form['server'] = array(
            '#type' => 'radios',
            '#title' => t( 'Testmode' ),
            '#options' => array(
                'testmode' => ('Testmode - use for testing'),
                'live' => ('Live - use for processing real transactions'),
            ),
            '#default_value' => $settings['server'],
        );
    }
    $form['omschrijving'] = array(
        '#type' => 'textfield',
        '#title' => t( 'Description' ),
        '#description' => t( 'Description in front of the order nr.' ),
        '#default_value' => $settings['omschrijving'],
        '#required' => TRUE,
    );

    $form['omschrijving'] = array(
        '#markup' => 'The conditions for displaying a payment method can you set with the payment conditions.',
    );
    return $form;
}

function _cgbetaling( $order, $payment_method ) {

    // Load the payment method instance and determine availability.
    // $payment_method = commerce_payment_method_load($method_id);
    // 
    // load generic variables
    $genericdata = commerce_payment_method_instance_load( 'generic|commerce_payment_generic' );
    $genericsettings = $genericdata['settings'];
    $siteid = '';
    $gatewayurl = '';

    if ( $genericdata ) {
        $merchantid = $genericsettings['merchantid'];
        $merchantkey = $genericsettings['merchantkey'];
        $siteid = $genericsettings['siteid'];
        if ( $genericsettings['server'] == 'testmode' ) {
            $gatewayurl = $genericsettings['testgatewayurl'];
            $testmode = true;
        } else {
            $gatewayurl = $genericsettings['gatewayurl'];
            $testmode = false;
        }
    }

    $oCURO_Transaction = new curo_Transaction( new CURO() );
    $oCURO_Transaction->setMerchantId( $merchantid );
    $oCURO_Transaction->setMerchantSecret( $merchantkey );
    $oCURO_Transaction->setSiteId( $siteid );
    $oCURO_Transaction->setTestMode( $testmode );
    $sCurrent_Url = $gatewayurl;

    if ( !empty( $sCurrent_Url ) ) {
        $oCURO_Transaction->setApiHost( $sCurrent_Url );
    }

    // Return an error if the enabling action's settings haven't been configured.
    if ( empty( $merchantid ) || empty( $merchantkey ) ) {
        drupal_set_message( $payment_method['title'] . t( ": generic module isn't configured yet." ), 'error' );
        return array();
    }

    $settings = array(
        // Return to the previous page when payment is canceled
        'cancel_return' => url( 'checkout/' . $order->order_id . '/payment/back/' . $order->data['payment_redirect_key'], array( 'absolute' => TRUE ) ),
        // Return to the payment redirect page for processing successful payments
        'return' => url( 'checkout/' . $order->order_id . '/payment/return/' . $order->data['payment_redirect_key'], array( 'absolute' => TRUE ) ),
        // Specify the current payment method instance ID in the notify_url
        'payment_method' => $payment_method['instance_id'],
    );

    $order_wrapper = entity_metadata_wrapper( 'commerce_order', $order );

    $order_total = $order_wrapper->commerce_order_total->value();

    $amount = $order_total['amount'];
    $currency = $order_total['currency_code'];

    $aArgs['payment'] = $payment_method['settings']['paymentcode'];
    $aArgs['site_id'] = $siteid;
    $aArgs['url_success'] = commerce_cardgate_notify_url() . '/' . $settings['payment_method'];
    $aArgs['url_failure'] = $settings['cancel_return'];
    $aArgs['url_callback'] = commerce_cardgate_notify_url() . '/' . $settings['payment_method'];
    $aArgs['reference'] = 'O' . time() . '_' . $order->order_number;
    $aArgs['amount'] = $amount;
    $aArgs['currency'] = $currency;
    $aArgs['description'] = 'Order ' . $order->order_number;
    $aArgs['ip'] = $oCURO_Transaction->getUserIP();

    $aArgs['email'] = $order->mail;

    if ( !empty( $order_wrapper->commerce_customer_billing->commerce_customer_address ) ) {
        $billing_address = $order_wrapper->commerce_customer_billing->commerce_customer_address->value();
        $aArgs['first_name'] = $billing_address['first_name'];
        $aArgs['last_name'] = ($billing_address['last_name'] != '') ? $billing_address['last_name'] : $billing_address['name_line'];
        $aArgs['address'] = $billing_address['thoroughfare'];
        $aArgs['city'] = $billing_address['locality'];
        $aArgs['postal_code'] = $billing_address['postal_code'];
        $aArgs['country'] = $billing_address['country'];
    }

    if ( $payment_method['settings']['paymentcode'] == 'ideal' ) {
        $aArgs['issuer'] = $order->data['bankkeuze'];
    }

    if ( $testmode ) {
        $aArgs['testmode'] = '1';
        //For testing
        //$aArgs['bypass_simulator'] = '1';
    }

    $tax_rate = 0;
    if ( module_exists( 'commerce_tax' ) ) {
        $taxes = commerce_tax_rates();
        foreach ( $taxes as $tax_name => $tax_data ) {
            $tax_rate += $tax_data['rate'];
        }
    }
    
    //collect cartitems
    $cartitems = array();
    $orderitems = $order->commerce_line_items['und'];

    foreach ( $orderitems as $line ) {
        $line_item = commerce_line_item_load( $line['line_item_id'] );
        
    
  rules_invoke_event('commerce_product_calculate_sell_price', $line_item);
  
        if ( $line_item ) {
            if ( $line_item->type == 'product' ) {
                $product_id = $line_item->commerce_product['und'][0]['product_id'];
                $product = commerce_product_load( $product_id );
                //$price_data = entity_metadata_wrapper( 'commerce_product', $product )->commerce_price->value();
                $price_data = entity_metadata_wrapper('commerce_line_item', $line_item)->commerce_unit_price->value();
                $price = $price_data['amount'];
                $vat = 0;
                $vat_amount = $price;

                if ( $tax_rate > 0 ) {
                    $price = $price * (1 + $tax_rate);
                    $vat = $tax_rate * 100;
                    $vat_amount = $price * $tax_rate;
                }
                $item['quantity'] = $line_item->quantity;
                $item['sku'] = $product->sku;
                $item['name'] = $product->title;
                $item['price'] = round( $price, 0 );
                $item['vat'] = $vat;
                $item['vat_amount'] = round( $vat_amount, 0 );
                $item['vat_inc'] = 1;
                $item['type'] = 1;
                $cartitems[] = $item;
            }
        }


        if ( $line_item->type == 'shipping' ) {
            $item = array();
            $data = $line_item->data;
            if ( $data['shipping_service']['shipping_method'] == 'flat_rate' ) {
                $price = round( floatval( $line_item->commerce_unit_price['und'][0]['amount'] ), 0 );
                $vat = 0;
                $vat_amount = 0;
                if ( $tax_rate > 0 ) {
                    $vat = $tax_rate * 100;
                    $vat_amount = $price / (1 + $tax_rate) * $tax_rate;
                }
                $item['quantity'] = $line_item->quantity;
                $item['sku'] = $line_item->line_item_label;
                $item['name'] = $line_item->line_item_label;
                $item['price'] = $price;
                $item['vat'] = $vat;
                $item['vat_amount'] = $vat_amount;
                $item['vat_inc'] = 1; //shipping
                $item['type'] = 2;
                $cartitems[] = $item;
            }
        }
    }

    if ( count( $cartitems ) > 0 ) {
        $carttotal = 0;
        foreach ( $cartitems as $key => $cartitem ) {
            $carttotal +=$cartitem['price'];
        }

        if ( $aArgs['amount'] < $carttotal ) {
            $discount = $aArgs['amount'] - $carttotal;
            $vat = 0;
            $vat_amount = 0;
            if ( $tax_rate > 0 ) {
                $vat = $tax_rate * 100;
                $vat_amount = $discount / (1 + $tax_rate) * $tax_rate;
            }
            $item = array();
            $item['quantity'] = 1;
            $item['sku'] = 'total_discount';
            $item['name'] = 'total discount';
            $item['price'] = round( $discount, 0 );
            $item['vat'] = $vat;
            $item['vat_amount'] = round( $vat_amount, 0 );
            $item['vat_inc'] = 1; //shipping
            $item['type'] = 4;
            $cartitems[] = $item;
        }
        $aArgs['cartitems'] = $cartitems;
    }

    $shop_data = system_get_info( 'module', 'commerce' );
    $plugin_data = system_get_info( 'module', 'commerce_cardgate' );

    $aArgs['shop_name'] = 'Drupal7_commerce';
    $aArgs['shop_version'] = $shop_data['version'];
    $aArgs['plugin_name'] = 'drupal_commerce';
    $aArgs['plugin_version'] = $plugin_data['version'];

    $aResult = $oCURO_Transaction->payment( $aArgs, $aArgs['payment'] );

    if ( !empty( $aResult['payment'] ) ) {
        drupal_goto( $aResult['payment']['url'] );
        return true;
    } else {
        drupal_set_message( t( 'Betalen met ' . $payment_method['title'] . ' is nu niet mogelijk.' ) . ' (' . $oCURO_Transaction->getError() . ')', 'error' );
        return false;
    }
}

function _cgnotify( $trxid, $orderid, $payment_method ) {

    $aRequest = $_GET + $_POST;

    /* Drupal 7 query */
    $query = db_select( 'commerce_cardgate', 's' );
    $query->condition( 's.trxid', $trxid, '=' )
            ->fields( 's', array( 'status' ) )
            ->range( 0, 50 );
    $result = $query->execute();

    $order = commerce_order_load( $orderid );

    $genericdata = commerce_payment_method_instance_load( 'generic|commerce_payment_generic' );
    $genericsettings = $genericdata['settings'];
    $siteid = '';
    $gatewayurl = '';

    if ( $genericdata ) {
        $merchantid = $genericsettings['merchantid'];
        $merchantkey = $genericsettings['merchantkey'];
        $siteid = $genericsettings['siteid'];
        if ( $genericsettings['server'] == 'testmode' ) {
            $gatewayurl = $genericsettings['testgatewayurl'];
            $testmode = true;
        } else {
            $gatewayurl = $genericsettings['gatewayurl'];
            $testmode = false;
        }
    }

    $oCURO_Transaction = new curo_Transaction( new CURO() );
    $oCURO_Transaction->setMerchantId( $merchantid );
    $oCURO_Transaction->setMerchantSecret( $merchantkey );
    $oCURO_Transaction->setSiteId( $siteid );
    $sCurrent_Url = $gatewayurl;

    if ( !empty( $sCurrent_Url ) ) {
        $oCURO_Transaction->setApiHost( $sCurrent_Url );
    }

    $verify = $oCURO_Transaction->verifyHash( $aRequest );

    // process for commerce_cardgate table
    if ( $result->rowCount() == 0 ) {
        $record = array(
            "trxid" => $trxid,
            "orderid" => $orderid,
            "status" => $aRequest['code'],
            "invoicenr" => '',
            "creditnr" => '',
            "payment" => $payment_method['title'],
        );
    } else {
        $aResult = $result->fetchAssoc();
        if ( $aResult['status'] != '200' ) {
            $cardgate->statusRequest( $trxid, $testmode );
            $record = array(
                "trxid" => $trxid,
                "orderid" => $orderid,
                "status" => $aRequest['code'],
                "invoicenr" => '',
                "creditnr" => '',
                "payment" => $payment_method['title'],
            );
        }
    }

    drupal_write_record( 'commerce_cardgate', $record, 'trxid' );

    $redirect = empty( $aRequest['hash'] );

    if ( !$verify ) {
        watchdog( 'commerce_cardgate', 'Status Request Failed', array( '@code' => $aRequest['code'], '@error' => 'Hash did not match' ), WATCHDOG_ERROR );
    }
    // process notify url
    if ( $verify && !$redirect ) {

        $transaction_id = commerce_cardgate_get_payment_transaction( $trxid, $payment_method['method_id'] );
        $completed = false;

        if ( !$transaction_id ) {
            $transaction = commerce_payment_transaction_new( $payment_method['method_id'], $order->order_id );
        } else {
            $transaction = commerce_payment_transaction_load( $transaction_id );
            if ( $transaction->remote_status == 200 ) {
                $completed = true;
            }
        }

        if ( !$completed ) {
            $transaction->instance_id = $payment_method['instance_id'];
            $transaction->remote_id = $trxid;
            $transaction->amount = $order->commerce_order_total['und']['0']['amount'];
            $transaction->currency_code = $order->commerce_order_total['und']['0']['currency_code'];
            // Set the transaction's statuses based on the IPN's payment_status.
            $transaction->remote_status = $aRequest['code'];

            // If we didn't get an approval response code...
            switch ( $aRequest['code'] ) {
                case 200:
                    //success
                    $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
                    $transaction->message = t( 'The payment has completed.' );
                    break;
                case 300:
                case 301:
                    //failed
                    $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
                    $transaction->message = t( "The payment has failed." );
                    break;
                case 308:
                    //expired
                    $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
                    $transaction->message = t( 'The payment has expired.' );
                    break;
                case 309:
                    //cancelled
                    $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
                    $transaction->message = t( "The payment has cancelled." );
                    break;
                case 700:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t( 'The payment is pending, user action required.' );
                    break;
                case 701:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t( 'The payment is pending, waiting for confirmation.' );
                case 710:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t( 'The recurring payment is pending, waiting for confirmation.' );
                    break;
                case 711:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t( 'The payment is pending, subscription waiting for payment.' );
                    break;
                default:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t( 'The payment status is still open.' );
                    break;
            }

            // Save the transaction information.
            commerce_payment_transaction_save( $transaction );
            watchdog( $payment_method['base'], 'CardGate Notify processed for Order @order_number with ID @txn_id.', array( '@txn_id' => $trxid, '@order_number' => $order->order_number ), WATCHDOG_INFO );
        }
    }

    if ( $redirect ) {
        header( 'location: ' . url( 'checkout/' . $order->order_id . '/payment/return/' . $order->data['payment_redirect_key'], array( 'absolute' => TRUE ) ) );
    } else {
        echo $trxid . '.' . $aRequest['code'];
    }
    exit();
}

function commerce_cardgate_get_payment_transaction( $trxid, $method_id ) {
    $feedback_remote_id = $trxid;
    $query = new EntityFieldQuery;

    $result = $query
            ->entityCondition( 'entity_type', 'commerce_payment_transaction' )
            ->propertyCondition( 'payment_method', $method_id )
            ->propertyCondition( 'remote_id', $feedback_remote_id )
            ->execute();
    if ( isset( $result['commerce_payment_transaction'] ) && count( $result['commerce_payment_transaction'] ) > 0 ) {
        $transaction = array_pop( $result['commerce_payment_transaction'] );
        return $transaction->transaction_id;
    }
    return FALSE;
}

?>