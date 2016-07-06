<?php

/**
 * @file
 * Administrative page callbacks for the Shipping UI module.
 */

/**
 * Builds the shipping settings page using the Rules UI overview table filtered
 * to display shipping method rules.
 */
function commerce_cardgate_ui_admin_page() 
{ 
    /*
	if(isset($_GET['actie']))
	{
		require_once(drupal_get_path('module', 'commerce_cardgate') . '/cardgate/cardgate.cls.php');
		$methode = commerce_payment_method_load('ecare');
		$methode = commerce_payment_method_instance_load($methode['method_id'] . '|' . "commerce_payment_" . $methode['method_id']);
		$cardgate = new Cardgate($payment_method['settings']['merchantid'], $payment_method['settings']['merchantkey'], $payment_method['settings']['siteid'], $payment_method['settings']['gatewayurl']);
		$actie = $_GET['actie'];
		$status = '';
		$bedrag = '';
		
		$order = commerce_order_load($_GET['orderid']);
		
		$transaction = commerce_payment_transaction_new($methode['method_id'], $_GET['orderid']);
		$transaction->instance_id = $methode['instance_id'];
		$transaction->remote_id = $_GET['trxid'];
		foreach($order->commerce_order_total as $line)
		{
			$transaction->currency_code = $line['0']['currency_code'];
			$bedrag = $line['0']['amount'];
		}
		
		
		if($actie == 'factuur')
		{
			$posts = '';
			if(($ex = $cardgate->InvoiceRequest($_GET['trxid'], $posts)) < 0)
			{
				print_r($ex);
				exit;
			}
			else
			{
				$transaction->remote_status = 'Success';
				$transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
				$transaction->amount = $bedrag;
				$transaction->message = t('Invoice created.');
				
				db_query("UPDATE {commerce_cardgate} SET status = 'Success', invoicenr = '".$cardgate->documentId."' WHERE trxid = '".$_GET['trxid']."'");
			}
		}
		elseif($actie == 'cancel')
		{
			$posts = '';
			
			if(($ex = $cardgate->CancelReservationRequest($_GET['trxid'], $posts)) < 0)
			{
				print_r($ex);
				exit;
			}
			else
			{
				$transaction->remote_status = 'Cancelled';
				$transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
				$transaction->amount = '0';
				$transaction->message = t('Reservation cancelled');

				db_query("UPDATE {commerce_cardgate} SET status = 'Cancelled' WHERE trxid = '".$_GET['trxid']."'");
			}
		}
		elseif($actie == 'credit')
		{
			$posts = '';
			if(($ex = $cardgate->CreditInvoiceRequest($_GET['trxid'], $posts)) < 0)
			{
				print_r($ex);
				exit;
			}
			else
			{
				$transaction->remote_status = 'Cancelled';
				$transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
				$transaction->amount = $bedrag * -1;
				$transaction->message = t('Invoice credited');

				db_query("UPDATE {commerce_cardgate} SET status = 'Credited', creditnr = '".$cardgate->documentId."' WHERE trxid = '".$_GET['trxid']."'");
			}
		}
		
		commerce_payment_transaction_save($transaction);
	}


	$transacties = get_transactions();
	
	$content = '<table>';
		$content .= '<tr>';
			$content .= '<td>Order Number</td>';
			$content .= '<td>Name</td>';
			$content .= '<td>Bedrag</td>';
			$content .= '<td>Status</td>';
			$content .= '<td>actie</td>';
			$content .= '</tr>';
			
			foreach($transacties as $transactie)
			{
				$order = commerce_order_load($transactie['orderid']);
				
				$bedrag = 0;
				$valuta = '';
				foreach($order->commerce_order_total as $totals)
				{
					$bedrag += $totals['0']['amount'];
					$valuta = $totals['0']['currency_code'];
				}
				
				$bedrag = $bedrag / 100;
				
				switch($transactie['status'])
				{
					case 'Reservation':
						$inhoud = '<a href="?actie=factuur&trxid='.$transactie['trxid'].'&orderid='.$transactie['orderid'].'">Make invoice</a>&nbsp&nbsp&nbsp<a href ="?actie=cancel&trxid='.$transactie['trxid'].'&orderid='.$transactie['orderid'].'">Cancel reservation</a>';
						break;
					case 'Success':
						$methode = commerce_payment_method_load('ecare');
						$methode = commerce_payment_method_instance_load($methode['method_id'] . '|' . "commerce_payment_" . $methode['method_id']);
						
						$sha1 = sha1($transactie['invoicenr'] . $methode['settings']['merchantid'] . $methode['settings']['merchantkey']);
						
						$inhoud = '<a href="https://www.cardgate.nl/Cardgate/Opdrachtgever/download.aspx?' .
                                                            'merchantid=' . $methode['settings']['merchantid'] . 
                                                            '&doc=' . $transactie['invoicenr'] .
                                                            '&sha1=' . $sha1 . '">Open Invoice</a>&nbsp&nbsp&nbsp<a href ="?actie=credit&trxid='.$transactie['trxid'].'&orderid='.$transactie['orderid'].'">Credit Invoice</a>';
						break;
					case 'Credited':
						$methode = commerce_payment_method_load('ecare');
						$methode = commerce_payment_method_instance_load($methode['method_id'] . '|' . "commerce_payment_" . $methode['method_id']);
						
						$sha1 = sha1($transactie['invoicenr'] . $methode['settings']['merchantid'] . $methode['settings']['merchantkey']);
						
						$inhoud = '<a href="https://www.cardgate.nl/Cardgate/Opdrachtgever/download.aspx?' .
                                                            'merchantid=' . $methode['settings']['merchantid'] . 
                                                            '&doc=' . $transactie['invoicenr'] .
                                                            '&sha1=' . $sha1 . '">Open invoice</a>&nbsp&nbsp&nbsp<a href="https://www.cardgate.nl/Cardgate/Opdrachtgever/download.aspx?' .
                                                            'merchantid=' . $methode['settings']['merchantid'] . 
                                                            '&doc=' . $transactie['creditnr'] .
                                                            '&sha1=' . $sha1 . '">Open Credit</a>';
						break;
					case 'Cancelled':
						$inhoud = '';
						break;
				}
				
				$content .= '<tr>';
					$content .= '<td><a href="'.url('#overlay=admin/commerce/orders/'.$transactie['orderid']).'">Open Order</a></td>';
					$content .= '<td>Name</td>';
					$content .= '<td>'.$bedrag.' '.$valuta.'</td>';
					$content .= '<td>'.$transactie['status'].'</td>';
					$content .= '<td>'.$inhoud.'</td>';
				$content .= '</tr>';
			}
			
		
	$content .= '</table>';
	
	return $content;
     * 
     */
}

function get_transactions()
{
    /*
	$result = db_query('SELECT s.trxid, s.orderid, s.status, s.invoicenr, s.creditnr FROM {commerce_cardgate} s WHERE s.payment = :payment ORDER BY s.orderid DESC', array(':payment' => 'ecare'));
	$transacties = array();
	
	foreach ($result as $record) {
		$transacties[] = array('trxid'=>$record->trxid, 'orderid'=>$record->orderid, 'status'=>$record->status, 'invoicenr'=>$record->invoicenr, 'creditnr'=>$record->creditnr);
	}

	return $transacties;
     * 
     */
}