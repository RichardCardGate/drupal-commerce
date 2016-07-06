<?php
/**
 * CURO Payments Transaction Module
 *
 * @author CURO Payments
 * @package client transaction class for Restfull API
 * @version 1.1 - (C)2015 by DBS for CURO
 *
 * == BEGIN LICENSE ==
 *
 * THIS SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT OF THIRD PARTY RIGHTS.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE
 * OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * == END LICENSE ==
 */

class curo_Transaction implements ArrayAccess {

	protected $_oWrapper;			// CURO Wrapper for Transaction module
	protected $_oCURO;				// CURO object
	protected $_aData = Array(); 	// Last request data/info

	protected $_sCurrency = 'EUR'; 	// Default currency

	/**
	 * Class Constructor
	 * @param CURO Class $oCuro_ Reference to CURO Client API
	 */
	public function __construct( $oCURO_ ) {
		$this->_oWrapper = $oCURO_->getWrapper( 'transaction' );
	}

	/**
	 * Get status of a transaction record
	 * @param string $sTransactionId_ Id of the transaction
	 * @return mixed FALSE or Array with results
	 */
	public function status( $sTransactionId_ ) {
		$this->_aData = Array();
		if( FALSE !== ( $mResult = $this->_oWrapper->status( array( 'transaction_id' => $sTransactionId_ ) ) ) ) {
			if( ! empty( $mResult['success'] ) ) {
				$this->_aData = $mResult;
				return $this->_aData;
			}
		}
		return FALSE;
	}

	/**
	 * Get complete transaction record
	 * @param string $sTransactionId_ Id of the transaction
	 * @return mixed FALSE or Array with results
	 */
	public function get( $sTransactionId_ ) {
		$this->_aData = Array();
		if( FALSE !== ( $mResult = $this->_oWrapper->get( array( 'transaction_id' => $sTransactionId_ ) ) ) ) {
			if( ! empty( $mResult['success'] ) ) {
				$this->_aData = $mResult;
				return $this->_aData;
			}
		}
		return FALSE;
	}

	/**
	 * Get list of iDEAL issuers.
	 * NB: for testmode=1 you will receive a different list!
	 * @return mixed API Result
	 */
	public function getIdealIssuers() {
		if( FALSE !== ( $aResult = $this->_oWrapper->_oCURO->execute( 'ideal', 'issuers' ) ) ) {
			if( ! empty( $aResult['issuers'] ) ) {
				return $aResult['issuers'];
			} 
		}
		return FALSE;
	}

	/**
	 * Get list of all available paymentmethods/billingoptions.
	 * NB: for testmode=1 you can receive a different list!
	 * @return mixed API Result
	 */
	public function getOptions() {
		if( FALSE !== ( $aResult = $this->_oWrapper->_oCURO->execute( 'options', $this->_oWrapper->_oCURO->getSiteId(), ['format' => 'json'] ) ) ) {
			if( !empty( $aResult['options'] ) ) {
				return $aResult['options'];
			} elseif ( !empty( $aResult['error'] ) ) {
				return $aResult;
			}
		}
		return FALSE;
	}

	/**
	 * Get list of all available paymentmethods/billingoptions.
	 * NB: for testmode=1 you can receive a different list!
	 * @return mixed API Result
	 */
	public function pullrequest( $sToken_ ) {
		if( FALSE !== ( $aResult = $this->_oWrapper->_oCURO->execute( 'pullrequest', $sToken_, ['format' => 'json'] ) ) ) {
			if( !empty( $aResult['content'] ) ) {
				return $aResult['content'];
			} elseif ( !empty( $aResult['error'] ) ) {
				return $aResult;
			}
		}
		return FALSE;
	}

	/**
	 * Register a new transaction
	 * @param array $aData_ arguments to the API
	 * @return mixed API result
	 */
	public function register( $aData_ ) {
		return $this->_oWrapper->register( $aData_ );
	}

	/**
	 * Create a payment request
	 * @param array $aData_ With all the necessary params
	 * @param string $sPaymentType_ Payment type to be used
	 * @return mixed API result
	 */
	public function payment( $aData_, $sPaymentType_ = NULL ) {
		if( empty( $sPaymentType_ ) ) {
			$sPaymentType_ = $aData_['payment_method'];
		}
		if( empty( $aData_['currency'] ) ) {
			$aData_['currency'] = $this->getCurrency();
		}
		
		return $this->_oWrapper->payment( $sPaymentType_, $aData_ );
	}

	/**
	 * Create a subscription request
	 * @param array $aData_ With all the necessary params
	 * @param string $sPaymentType_ Payment type to be used
	 * @return mixed API result
	 */
	public function subscription( $aData_, $sPaymentType_ = NULL ) {
		if( empty( $sPaymentType_ ) ) {
			$sPaymentType_ = $aData_['payment_method'];
		}
		if( empty( $aData_['currency'] ) ) {
			$aData_['currency'] = $this->getCurrency();
		}
		
		return $this->_oWrapper->subscription( $sPaymentType_, $aData_ );
	}

	/**
	 * Refund (part of a) transaction
	 * @param string $sTransactionId_ Transaction id to refund on
	 * @param integer $iAmount_ Amount (in cents) to refund
	 * @param string $sDescription_ Optional description for the refund
	 * @param string $aExtraData_ Optional extra data
	 * @return mixed API result
	 */
	public function refund( $sTransactionId_, $iAmount_, $sDescription_ = NULL, $aExtraData_ = NULL ) {
		if( ! is_array( $aExtraData_ ) ) {
			$aExtraData_ = array();
		}
		$aExtraData_ = array_merge(
			$aExtraData_
			, array(
				'transaction_id' => $sTransactionId_
				, 'amount' => $iAmount_
				, 'description' => $sDescription_
			)
		);
		if( empty( $aExtraData_['currency'] ) ) {
			$aExtraData_['currency'] = $this->getCurrency();
		}
		return $this->_oWrapper->refund( $aExtraData_ );
	}

	/**
	 * Cancel a transaction (i.e. a refund or direct debit that is not processed yet)
	 * @param string $sTransactionId_ Transaction id to refund on
	 * @param string $aExtraData_ Optional extra data
	 * @return mixed API result
	 */
	public function cancel( $sTransactionId_, $aExtraData_ = NULL ) {
		if( ! is_array( $aExtraData_ ) ) {
			$aExtraData_ = array();
		}
		$aExtraData_ = array_merge(
			$aExtraData_
			, array(
				'transaction_id' => $sTransactionId_
			)
		);
		return $this->_oWrapper->cancel( $aExtraData_ );
	}

	/**
	 * Recur for a (master) transaction
	 * @param string $sTransactionId_ Transaction id to refund on
	 * @param integer $iAmount_ Amount (in cents) to refund
	 * @param string $sDescription_ Optional description for the refund
	 * @param string $aExtraData_ Optional extra data
	 * @return mixed API result
	 */
	public function recur( $sTransactionId_, $iAmount_, $sDescription_ = NULL, $aExtraData_ = NULL ) {
		if( ! is_array( $aExtraData_ ) ) {
			$aExtraData_ = array();
		}
		$aExtraData_ = array_merge(
			$aExtraData_
			, array(
				'transaction_id' => $sTransactionId_
				, 'amount' => $iAmount_
				, 'description' => $sDescription_
			)
		);
		if( empty( $aExtraData_['currency'] ) ) {
			$aExtraData_['currency'] = $this->getCurrency();
		}
		return $this->_oWrapper->recur( $aExtraData_ );
	}

	/**
	 * Validate hash for a transaction callback
	 * @param array $aRequest_ Request parameters
	 * @return boolean Check result
	 */
	public function verifyHash( $aRequest_ ) {
            
		if(
			empty( $aRequest_['transaction'] )
			|| empty( $aRequest_['currency'] )
			|| empty( $aRequest_['amount'] )
			|| empty( $aRequest_['reference'] )
			|| ! isset( $aRequest_['code'] )
		) {
			return FALSE;
		}
		$sHash = md5(
			( ! empty( $aRequest_['testmode'] ) ? 'TEST' : '' )
			. $aRequest_['transaction']
			. $aRequest_['currency']
			. $aRequest_['amount']
			. $aRequest_['reference']
			. $aRequest_['code']
			. $this->getMerchantSecret()
		);
		return ( $_REQUEST['hash'] === $sHash );
	}

	/**
	 * Getters and settings
	 **/
	public function getCurrency() {
		return $this->_sCurrency;
	}
	public function setCurrency( $sCurrency_ ) {
		$sOldCurrency = $this->_sCurrency;
		$this->_sCurrency = $sCurrency_;
		return $sOldCurrency;
	}

	/**
	 * Get the curl error
	 * @return string Error result
	 */
	public function getError() {
		return $this->_oWrapper->_oCURO->getError();
	}

	/**
	 * Get the curl error number
	 * @return integer Error number
	 */
	public function getErrorNo() {
		return $this->_oWrapper->_oCURO->getErrorNo();
	}
	
	/**
	 * Get the ipaddress off the user
	 * @return string ipnumber
	 */
	public function getUserIP() {
		return $this->_oWrapper->_oCURO->getUserIP();
	}

	/**#@+
	 * ArrayAccess methods
	 * @ignore
	 */
	public function offsetSet( $mKey_, $mValue_ ) {
		return $this->__set( $mKey_, $mValue_ );
	}
	public function offsetExists( $mKey_ ) {
		return ( isset( $this->_aData[$mKey_] ) );
	}
	public function offsetUnset( $mKey_ ) {
		if ( isset( $this->_aData[$mKey_] ) ) {
			unset( $this->_aData[$mKey_] );
		}
	}
	public function offsetGet( $mKey_ ) {
		return $this->__get( $mKey_ );
	}
	public function __toString() {
		return function_exists( 'get_called_class' ) ? get_called_class() : get_class();
	}
	public function __set( $mKey_, $mValue_ ) {
		if( $mKey_ == 'data' ) {
			$this->_aData = $mValue_;
		} else {
			$this->_aData[$mKey_] = $mValue_;
		}
	}
	public function __get( $mKey_ ) {
		if( $mKey_ == 'data' ) {
			return $this->_aData;
		} else if ( array_key_exists( $mKey_, $this->_aData ) ) {
			return $this->_aData[$mKey_];
		} else {
			return NULL;
		}
	}

	/**
	 * Proxy other methods to CURO client class
	 */
	public function __call( $sName_, $aArgs_ ) {
		return call_user_func_array( array( $this->_oWrapper->_oCURO, $sName_), $aArgs_ );
	}
}
