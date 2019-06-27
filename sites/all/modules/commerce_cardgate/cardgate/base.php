<?php

require_once (drupal_get_path('module', 'commerce_cardgate') . '/cardgate-clientlib-php/src/Autoloader.php');
cardgate\api\Autoloader::register();

function _cgsettings($settings = null, $base, $payment) {
    
    // reset bank issuers cache
    variable_set('commerce_cardgate_issuerrefresh',0);
    $form = array();
    $currencies = $base . 'currencies';
    // Merge default settings into the stored settings array.
    $default_currency = variable_get('commerce_default_currency', 'EUR');
    
    $options['checkoutview']['logos'] = "Logo's";
    $options['checkoutview']['text'] = 'Text';
    $options['checkoutview']['logosandtext'] = "Logo's and Text";
    
    $settings['gatewayurl'] = 'secure.curopayments.net';
    $settings['testgatewayurl'] = 'secure-staging.curopayments.net';
    
    $settings = (array) $settings + array(
        'merchantid' => '',
        'merchantkey' => '',
        'siteid' => '',
        'hashkey' => '',
        'currency_code' => in_array($default_currency, array_keys($currencies())) ? $default_currency : 'EUR',
        'server' => 'live',
        'omschrijving' => '',
        'pending' => '',
        'complete' => '',
        'cancel' => ''
    );
    $form['paymentcode'] = array(
        '#type' => 'hidden',
        '#default_value' => $payment
    );
    if ($base == 'commerce_cardgate_generic_') {
        $form['server'] = array(
            '#type' => 'radios',
            '#title' => t('Testmode'),
            '#options' => array(
                'testmode' => ('Testmode - use for testing'),
                'live' => ('Live - use for processing real transactions')
            ),
            '#default_value' => $settings['server']
        );
        $form['siteid'] = array(
            '#type' => 'textfield',
            '#title' => t('Site ID'),
            '#description' => t('The Site ID from your CardGate account.'),
            '#default_value' => $settings['siteid'],
            '#required' => TRUE
        );
        $form['hashkey'] = array(
            '#type' => 'textfield',
            '#title' => t('Hash key'),
            '#description' => t('The Hash key from your CardGate account.'),
            '#default_value' => $settings['hashkey'],
            '#required' => TRUE
        );
        $form['merchantid'] = array(
            '#type' => 'textfield',
            '#title' => t('Merchant ID'),
            '#description' => t('The Merchant ID from your CardGate account.'),
            '#default_value' => $settings['merchantid'],
            '#required' => TRUE
        );
        $form['merchantkey'] = array(
            '#type' => 'textfield',
            '#title' => t('API key'),
            '#description' => t('The Merchant Key from your CardGate account.'),
            '#default_value' => $settings['merchantkey'],
            '#required' => TRUE
        );
        $form['checkoutview'] = array(
            '#type' => 'select',
            '#title' => t('Checkout view'),
            '#description' => t('Choose the way you want the payment methods displayed in the checkout.'),
            '#options' => $options['checkoutview'],
            '#default_value' => (empty($settings['checkoutview']) ? 'text' : $settings['checkoutview'])
        );
    }
    $form['omschrijving'] = array(
        '#type' => 'textfield',
        '#title' => t('Description'),
        '#description' => t('Description in front of the order nr.'),
        '#default_value' => $settings['omschrijving'],
        '#required' => TRUE
    );
    
    $form['omschrijving'] = array(
        '#markup' => 'You can set the conditions for displaying a payment method with the payment conditions.'
    );
    return $form;
}

function _cgbetaling($order, $payment_method) 
{
    // Load the payment method instance and determine availability.
    // $payment_method = commerce_payment_method_load($method_id);
    //
    // load generic variables
    $genericdata = commerce_payment_method_instance_load('generic|commerce_payment_generic');
    $genericsettings = $genericdata['settings'];
    
    if ($genericdata) {
        $merchantid = (int) $genericsettings['merchantid'];
        $merchantkey = $genericsettings['merchantkey'];
        $siteid = (int) $genericsettings['siteid'];
        $hashkey = $genericsettings['hashkey'];
        if ($genericsettings['server'] == 'testmode') {
            $testmode = TRUE;
        } else {
            $testmode = FALSE;
        }
    }
    
    try {
        // Return an error if the enabling action's settings haven't been configured.
        if (empty($merchantid) || empty($merchantkey)) {
            drupal_set_message($payment_method['title'] . t(": generic module isn't configured yet."), 'error');
            return array();
        }
        
        $oCardGate = new \cardgate\api\Client($merchantid, $merchantkey, $testmode);
        $oCardGate->setIp($_SERVER['REMOTE_ADDR']);
        
        $shop_data = system_get_info('module', 'commerce');
        $plugin_data = system_get_info('module', 'commerce_cardgate');
        
        $oCardGate->version()->setPlatformName('Drupal7_commerce');
        $oCardGate->version()->setPlatformVersion($shop_data['version']);
        $oCardGate->version()->setPluginName('drupal_commerce');
        $oCardGate->version()->setPluginVersion($plugin_data['version']);
        
        $order_wrapper = entity_metadata_wrapper('commerce_order', $order);
        $order_total = $order_wrapper->commerce_order_total->value();
        
        $amount = (int) $order_total['amount'];
        $currency = $order_total['currency_code'];
        $option = $payment_method['settings']['paymentcode'];
        
        $oTransaction = $oCardGate->transactions()->create($siteid, $amount, $currency);
        
        // Configure payment option.
        $oTransaction->setPaymentMethod($option);
        if ('ideal' == $option) {
            $oTransaction->setIssuer($order->data['bankkeuze']);
        }
        
        // Configure customer.
        $oConsumer = $oTransaction->getConsumer();
        $oConsumer->setEmail($order->mail);
        
        if (! empty($order_wrapper->commerce_customer_billing->commerce_customer_address)) {
            $billing_address = $order_wrapper->commerce_customer_billing->commerce_customer_address->value();
            $oConsumer->address()->setFirstName($billing_address['first_name']);
            $oConsumer->address()->setLastName(($billing_address['last_name'] != '') ? $billing_address['last_name'] : $billing_address['name_line']);
            $oConsumer->address()->setAddress($billing_address['thoroughfare']);
            $oConsumer->address()->setCity($billing_address['locality']);
            $oConsumer->address()->setZipCode($billing_address['postal_code']);
            $oConsumer->address()->setCountry($billing_address['country']);
        }
        
        // cart items
        $tax_rate = 0;
        if (module_exists('commerce_tax')) {
            $taxes = commerce_tax_rates();
            foreach ($taxes as $tax_name => $tax_data) {
                $tax_rate += $tax_data['rate'];
            }
        }
        
        // collect cartitems
        $items = [];
        $nr = 0;
        $cartitems = array();
        $orderitems = $order->commerce_line_items['und'];
        
        foreach ($orderitems as $line) {
            $line_item = commerce_line_item_load($line['line_item_id']);
            
            rules_invoke_event('commerce_product_calculate_sell_price', $line_item);
            
            if ($line_item) {
                if ($line_item->type == 'product') {
                    $product_id = $line_item->commerce_product['und'][0]['product_id'];
                    $product = commerce_product_load($product_id);
                    // $price_data = entity_metadata_wrapper( 'commerce_product', $product )->commerce_price->value();
                    $price_data = entity_metadata_wrapper('commerce_line_item', $line_item)->commerce_unit_price->value();
                    $price = $price_data['amount'];
                    $vat = 0;
                    $vat_amount = $price;
                    
                    if ($tax_rate > 0) {
                        $price = $price * (1 + $tax_rate);
                        $vat = $tax_rate * 100;
                        $vat_amount = $price * $tax_rate;
                    }
                    $nr ++;
                    $items[$nr]['type'] = 'product';
                    $items[$nr]['model'] = $product->sku;
                    $items[$nr]['name'] = $product->title;
                    $items[$nr]['quantity'] = $line_item->quantity;
                    $items[$nr]['price_wt'] = round($price, 0);
                    $items[$nr]['vat'] = $vat;
                    $items[$nr]['vat_amount'] = round($vat_amount, 0);
                    $items[$nr]['vat_inc'] = 1;
                }
            }
            
            if ($line_item->type == 'shipping') {
                
                $data = $line_item->data;
                if ($data['shipping_service']['shipping_method'] == 'flat_rate') {
                    $price = round(floatval($line_item->commerce_unit_price['und'][0]['amount']), 0);
                    $vat = 0;
                    $vat_amount = 0;
                    if ($tax_rate > 0) {
                        $vat = $tax_rate * 100;
                        $vat_amount = $price / (1 + $tax_rate) * $tax_rate;
                    }
                    $nr ++;
                    $items[$nr]['type'] = 'shipping';
                    $items[$nr]['model'] = $line_item->line_item_label;
                    $items[$nr]['name'] = $line_item->line_item_label;
                    $items[$nr]['quantity'] = $line_item->quantity;
                    $items[$nr]['price_wt'] = $price;
                    $items[$nr]['vat'] = $vat;
                    $items[$nr]['vat_amount'] = $vat_amount;
                    $items[$nr]['vat_inc'] = 1;
                }
            }
        }
        
        if (count($items) > 0) {
            $carttotal = 0;
            foreach ($cartitems as $key => $cartitem) {
                foreach ($cartitem as $cartitemtype => $value) {
                    if ($cartitemtype == 'price_wt') {
                        $carttotal += $value;
                    }
                }
            }
            
            if ($aArgs['amount'] < $carttotal) {
                $discount = $aArgs['amount'] - $carttotal;
                $vat = 0;
                $vat_amount = 0;
                if ($tax_rate > 0) {
                    $vat = $tax_rate * 100;
                    $vat_amount = $discount / (1 + $tax_rate) * $tax_rate;
                }
                $nr ++;
                $items[$nr]['type'] = 'product';
                $items[$nr]['model'] = 'Correction';
                $items[$nr]['name'] = 'item_correction';
                $items[$nr]['quantity'] = 1;
                $items[$nr]['price_wt'] = $iCorrection;
                $items[$nr]['vat'] = $vat;
                $items[$nr]['vat_amount'] = round($vat_amount, 0);
                $items[$nr]['vat_inc'] = 0;
            }
        }
      
        // add cartitems
        
        $oCart = $oTransaction->getCart();
        
        foreach ($items as $item) {
            
            switch ($item['type']) {
                case 'product':
                    $iItemType = \cardgate\api\Item::TYPE_PRODUCT;
                    break;
                case 'shipping':
                    $iItemType = \cardgate\api\Item::TYPE_SHIPPING;
                    break;
                case 'paymentfee':
                    $iItemType = \cardgate\api\Item::TYPE_HANDLING;
                    break;
                case 'discount':
                    $iItemType = \cardgate\api\Item::TYPE_DISCOUNT;
                    break;
            }
            
            $oItem = $oCart->addItem($iItemType, $item['model'], $item['name'], (int) $item['quantity'], (int) $item['price_wt']);
            $oItem->setVat($item['vat']);
            $oItem->setVatAmount($item['vat_amount']);
            $oItem->setVatIncluded($item['vat_inc']);
        }
        
        $oTransaction->setCallbackUrl(commerce_cardgate_notify_url() . '/' . $payment_method['instance_id']);
        $oTransaction->setSuccessUrl(commerce_cardgate_notify_url() . '/' . $payment_method['instance_id']);
        $oTransaction->setFailureUrl(url('checkout/' . $order->order_id . '/payment/back/' . $order->data['payment_redirect_key'], array(
            'absolute' => TRUE
        )));
        
        $oTransaction->setReference('O' . time() . '_' . $order->order_number);
        $oTransaction->setDescription('Order ' . $order->order_number);
        
        $oTransaction->register();
        
        $sActionUrl = $oTransaction->getActionUrl();
        var_dump($sActionUrl);
        
        if (NULL !== $sActionUrl) {
            
            drupal_goto(trim($sActionUrl));
            return true;
        } else {
            drupal_set_message(t('CardGate error: ' . htmlspecialchars($oException_->getMessage())), 'error');
        }
    } catch (cardgate\api\Exception $oException_) {
        
        drupal_set_message(t('CardGate error: ' . htmlspecialchars($oException_->getMessage())), 'error');
    }
    
    // on error
    unset($order->data['payment_method']);
    commerce_payment_redirect_pane_previous_page($order, t('Redirect to Payment method failed.'));
    exit();
}

function _cgnotify($trxid, $orderid, $payment_method) {
    $aRequest = $_GET;
    
    /* Drupal 7 query */
    $query = db_select('commerce_cardgate', 's');
    $query->condition('s.trxid', $trxid, '=')
        ->fields('s', array(
        'status'
    ))
        ->range(0, 50);
    $result = $query->execute();
    
    $order = commerce_order_load($orderid);
    
    $genericdata = commerce_payment_method_instance_load('generic|commerce_payment_generic');
    $genericsettings = $genericdata['settings'];
    $siteid = '';
    $gatewayurl = '';
    
    if ($genericdata) {
        $iMerchantId = (int) $genericsettings['merchantid'];
        $sMerchantApiKey = $genericsettings['merchantkey'];
        $sHashkey = $genericsettings['hashkey'];
        $siteid = $genericsettings['siteid'];
    }
    
    $testMode = ($aRequest['testmode'] == 1 ? FALSE : FALSE);
    $verify = FALSE;
    try {
        $oCardGate = new \cardgate\api\Client($iMerchantId, $sMerchantApiKey, $testMode);
        $oCardGate->setIp($_SERVER['REMOTE_ADDR']);
        
        if (FALSE == $oCardGate->transactions()->verifyCallback($aRequest, $sHashkey)) {
            // false
        } else {
            $verify = TRUE;
        }
    } catch (cardgate\api\Exception $oException_) {
        // false
    }
    
    // process for commerce_cardgate table
    if ($result->rowCount() == 0) {
        $record = array(
            "trxid" => $trxid,
            "orderid" => $orderid,
            "status" => $aRequest['code'],
            "invoicenr" => '',
            "creditnr" => '',
            "payment" => $payment_method['title']
        );
    } else {
        $aResult = $result->fetchAssoc();
        if ($aResult['status'] != '200') {
            $cardgate->statusRequest($trxid, $testmode);
            $record = array(
                "trxid" => $trxid,
                "orderid" => $orderid,
                "status" => $aRequest['code'],
                "invoicenr" => '',
                "creditnr" => '',
                "payment" => $payment_method['title']
            );
        }
    }
    
    drupal_write_record('commerce_cardgate', $record, 'trxid');
    
    $redirect = empty($aRequest['hash']);
    
    // process notify url
    if ($verify && ! $redirect) {
        
        $transaction_id = commerce_cardgate_get_payment_transaction($trxid, $payment_method['method_id']);
        $completed = false;
        
        if (! $transaction_id) {
            $transaction = commerce_payment_transaction_new($payment_method['method_id'], $order->order_id);
        } else {
            $transaction = commerce_payment_transaction_load($transaction_id);
            if ($transaction->remote_status == 200) {
                $completed = true;
            }
        }
        
        if (! $completed) {
            $transaction->instance_id = $payment_method['instance_id'];
            $transaction->remote_id = $trxid;
            $transaction->amount = $order->commerce_order_total['und']['0']['amount'];
            $transaction->currency_code = $order->commerce_order_total['und']['0']['currency_code'];
            // Set the transaction's statuses based on the IPN's payment_status.
            $transaction->remote_status = $aRequest['code'];
            
            // If we didn't get an approval response code...
            switch ($aRequest['code']) {
                case 200:
                    // success
                    $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
                    $transaction->message = t('The payment has completed.');
                    break;
                case 300:
                case 301:
                    // failed
                    $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
                    $transaction->message = t("The payment has failed.");
                    break;
                case 308:
                    // expired
                    $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
                    $transaction->message = t('The payment has expired.');
                    break;
                case 309:
                    // cancelled
                    $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
                    $transaction->message = t("The payment has cancelled.");
                    break;
                case 700:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t('The payment is pending, user action required.');
                    break;
                case 701:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t('The payment is pending, waiting for confirmation.');
                case 710:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t('The recurring payment is pending, waiting for confirmation.');
                    break;
                case 711:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t('The payment is pending, subscription waiting for payment.');
                    break;
                default:
                    $transaction->status = COMMERCE_PAYMENT_STATUS_PENDING;
                    $transaction->message = t('The payment status is still open.');
                    break;
            }
            
            // Save the transaction information.
            commerce_payment_transaction_save($transaction);
            watchdog($payment_method['base'], 'CardGate Notify processed for Order @order_number with ID @txn_id.', array(
                '@txn_id' => $trxid,
                '@order_number' => $order->order_number
            ), WATCHDOG_INFO);
        }
    }
    
    if ($redirect) {
        header('location: ' . url('checkout/' . $order->order_id . '/payment/return/' . $order->data['payment_redirect_key'], array(
            'absolute' => TRUE
        )));
    } else {
        echo $trxid . '.' . $aRequest['code'];
    }
    exit();
}

function commerce_cardgate_get_payment_transaction($trxid, $method_id) {
    $feedback_remote_id = $trxid;
    $query = new EntityFieldQuery();
    
    $result = $query->entityCondition('entity_type', 'commerce_payment_transaction')
        ->propertyCondition('payment_method', $method_id)
        ->propertyCondition('remote_id', $feedback_remote_id)
        ->execute();
    if (isset($result['commerce_payment_transaction']) && count($result['commerce_payment_transaction']) > 0) {
        $transaction = array_pop($result['commerce_payment_transaction']);
        return $transaction->transaction_id;
    }
    return FALSE;
}

/**
 * Functions
 */
function _cggetImage($img) {
    global $base_url;
    if (file_exists(drupal_get_path('module', 'commerce_cardgate') . '/images/' . $img)) {
        
        return '<img width="75px;" height="30px;" src="' . $base_url . '/' . drupal_get_path('module', 'commerce_cardgate') . '/images/' . $img . '" border="0" >';
    } else
        return "";
}

?>