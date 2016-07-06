<?php
/**
 * CURO Payments for Restfull API
 *
 * @author CURO Payments
 * @package clientlib class for Restfull API
 * @version 1.31 - (C)2015 by DBS for CURO
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

class CURO {

	protected $_mMerchantId		= '';
	protected $_sMerchantSecret	= '';
	protected $_mSiteId			= '';
	protected $_sGatewayUrl		= '';

	protected $_bTestMode		= TRUE;

	protected $_sErrorStr		= '';
	protected $_iErrorNo		= 0;

	const API_URL_LIVE = 'secure.curopayments.net';
	const API_PORT_LIVE = 443;
	// const API_URL_TEST	= 'secure-staging.curopayments.net';
	const API_URL_TEST	= 'ralph.api.curopayments.dev';
	const API_PORT_TEST = 443;

	protected $_sApiHost;
	protected $_iApiPort;
	protected $_sApiMethod	 = 'curl';
	protected $_sApiFormat	 = 'json';

	protected $_sLastRequest = '';
	protected $_sLastResult	 = '';

	/**
	 * Class Constructor
	 * @param String $sConfigFile_ Optionally you may provide the path to the config file. If you omit it, the
	 * default config file located in the SDK folder will be used.
	 */
	public function __construct( $sConfigFile_ = '' ) {
		if ( empty( $sConfigFile_ ) ) {
			$sConfigFile_ = dirname( __FILE__ ) . '/curo.config.php';
		}
		require( $sConfigFile_ );

		if ( $this->_bTestMode = (bool) $aConfig['testmode'] ) {
			$this->_sApiHost = self::API_URL_TEST;
			$this->_iApiPort = self::API_PORT_TEST;
		} else {
			$this->_sApiHost = self::API_URL_LIVE;
			$this->_iApiPort = self::API_PORT_LIVE;
		}
		// Default to json output
		if ( empty( $this->_sApiFormat ) ) {
			$this->_sApiFormat = 'json';
		}
	}

	/**
	 * Execute API function
	 * @param string $sService_ Service module to call
	 * @param string $sAction_ Action with the module
	 * @param array $aData_ Arguments
	 * @return mixed FALSE or Array with result
	 */
	public function execute( $sService_, $sAction_, $aData_ = array() ) {
		$aData_['testmode'] = $this->_bTestMode ? '1' : '0';
		$aData_['site_id'] = $this->_mSiteId;

		// Check for file uploads which can only be done with CURL
		$bHasFiles = FALSE;
		foreach( $aData_ as $sKey => $mValue ) {
			if (
				is_string( $mValue )
				&& $mValue[0] == '@'
			) {
				$bHasFiles = TRUE;
				break;
			}
		}

		if ( $bHasFiles ) {
			// We need to encode the elements ourselves or CURL will fail
			$this->_flattenArray( $aData_ );
			// Return CURL result
			return $this->_requestUsingCurl( $this->_sApiHost, $this->_iApiPort, "/rest/v1/curo/{$sService_}/{$sAction_}/", $aData_ );
		}
		return $this->_request( "/rest/v1/curo/{$sService_}/{$sAction_}/", $aData_ );
	}

	function _flattenArray( &$aArray_ ) {
		$aKeysToRemove = Array();
		foreach( $aArray_ as $sKey => $mValue ) {
			if ( is_array( $mValue ) ) {
				$this->_getSubArray( $aArray_, $mValue, $sKey );
				$aKeysToRemove[] = $sKey;
			}
		}
		for( $i=0, $e=count($aKeysToRemove); $i<$e; $i++ ) {
			unset( $aArray_[$aKeysToRemove[$i]] );
		}
	}

	function _getSubArray( &$aRoot_, &$aArray_, $sKey_ ) {
		foreach( $aArray_ as $sKey => $mValue ) {
			if ( is_array( $mValue ) ) {
				$this->_getSubArray( $aRoot_, $mValue, $sKey_ . "[$sKey]" );
			} else {
				$aRoot_[$sKey_ . "[$sKey]" ]= $mValue;
			}
		}
	}

	/**
	 * Internal request preparation method
	 * @param string $sPath_ URI
	 * @param mixed $mData_ POST data
	 * @return mixed
	 */
	protected function _request( $sPath_, $aData_ ) {
		$this->_iErrorNo = 0;
		$this->_sErrorStr = '';
		$this->_sLastRequest = json_encode( $aData_ );
		if ( function_exists( 'curl_init' ) ) {
			return $this->_requestUsingCurl( $this->_sApiHost, $this->_iApiPort, $sPath_, $this->_sLastRequest );
		} else {
			return $this->_requestUsingFsock( $this->_sApiHost, $this->_iApiPort, $sPath_, $this->_sLastRequest );
		}
	}

	private function _parseResponse( $sResponse_ ) {
		if ( $sResponse_[0] == 'a' ) {
			// Assume serializes
			$mData = @unserialize( $sResponse_ );
		} elseif ( $sResponse_[0] == '{' ) {
			$mData = @json_decode( $sResponse_, 1 );
		} elseif ( $sResponse_[0] == '<' ) {
			// XML
			$mData = @simplexml_load_string( $sResponse_ );
			$mData = @json_decode( @json_encode( $mData ), 1 );
		} else {
			$mData = $sResponse_;
		}
		return $mData;
	}

	/**
	 * Send HTTP request using CURL
	 * @param string $sHost_
	 * @param integer $iPort_
	 * @param string $sPath_
	 * @param string $sData_
	 * @return array
	 */
	protected function _requestUsingCurl( $sHost_, $iPort_, $sPath_, $aData_ ) {
		$this->_sApiMethod = 'curl';
		$sHost_ = ( $iPort_ == 443 ? 'https://' : 'http://' ) . $sHost_;

		$rCh = curl_init();
		curl_setopt_array( $rCh, [
			 CURLOPT_URL				=> $sHost_ . $sPath_
			, CURLOPT_PORT				=> $iPort_
			, CURLOPT_HTTPAUTH			=> CURLAUTH_BASIC
			, CURLOPT_USERPWD			=> $this->_mMerchantId . ':' . $this->_sMerchantSecret
			, CURLOPT_RETURNTRANSFER 	=> 1
			, CURLOPT_TIMEOUT			=> 60
			, CURLOPT_HEADER			=> FALSE
			, CURLOPT_HTTPHEADER  		=> ["Accept: application/$this->_sApiFormat"]
			, CURLOPT_POST				=> TRUE
			, CURLOPT_POSTFIELDS 		=> $aData_
			, CURLOPT_VERBOSE 			=> 1
		] );

		// For testing we need to disable this
		curl_setopt( $rCh, CURLOPT_SSL_VERIFYPEER, 0 );

		$sResults = curl_exec( $rCh );
		if ( FALSE == $sResults ) {
			$sResults = [ 'error' => 
				[ 'code' => curl_errno( $rCh )
				, 'message' => curl_error( $rCh ) ]
			];
		}
		curl_close( $rCh );
		
		$this->_sLastResult = $sResults;
		$mData = $this->_parseResponse( $sResults );
		if ( is_array( $mData ) ) {
			if ( isset( $mData['error'] ) ) {
				$this->_sErrorStr = isset( $mData['error']['message'] ) ? $mData['error']['message'] : 'unknown error';
				$this->_iErrorNo = isset( $mData['error']['code'] ) ? $mData['error']['code'] : 0;
				return FALSE;
			} else {
				return $mData;
			}
		} else {
			$this->_sErrorStr = 'unrecognized reply: ' . trim( $sResults );
			$this->_iErrorNo = 0;
			return FALSE;
		}
	}

	/**
	 * Send HTTP/1.0 request using socket connection
	 * @param string $sHost_
	 * @param integer $iPort_
	 * @param string $sPath_
	 * @param string $sData_
	 * @return mixed
	 */
	protected function _requestUsingFsock( $sHost_, $iPort_, $sPath_, $sData_ ) {
		$this->_sApiMethod = 'fsock';
		if ( FALSE == ( $rFp = @fsockopen( $sHost_, $iPort_, $iErrorNo, $sErrorStr ) ) ) {
			// Retry with tls
			if ( FALSE == ( $rFp = @fsockopen( 'tls://' . $sHost_, $iPort_, $iErrorNo, $sErrorStr ) ) ) {
				$this->_sErrorStr = 'unable to connect to server: ' . $sErrorStr;
				$this->_iErrorNo = 0;
				return FALSE;
			}
		}
		@fputs( $rFp, "POST {$sPath_} HTTP/1.0\n" ); // Use 1.0 to prevent chunked response
		@fputs( $rFp, "Host: {$sHost_}\n" );
		@fputs( $rFp, "Authorization: Basic " . base64_encode(  $this->_mMerchantId . ':' . $this->_sMerchantSecret ) . "\n" );
		@fputs( $rFp, "Content-type: application/x-www-form-urlencoded\n" );
		@fputs( $rFp, "Content-length: " . strlen( $sData_ ) . "\n" );
		@fputs( $rFp, "Connection: close\n\n" );
		@fputs( $rFp, $sData_ );

		$sBuffer = '';
		while ( ! feof( $rFp ) ) {
			$sBuffer .= fgets( $rFp, 128 );
		}
		fclose( $rFp );
		return $this->_processRawResults( $sBuffer );
	}

	/**
	 * Process HTTP results
	 * @param string $sResults_
	 * @return mixed
	 */
	protected function _processRawResults( $sResults_ ) {
		$this->_sLastResult = $sResults_;
		list( $sHeaders, $sBody ) = preg_split( "/(\r?\n){2}/", $sResults_, 2 );
		if ( (integer) strpos( $sHeaders, '200 OK' ) > 0 ) {
			if ( ! empty( $sBody ) ) {
				$mData = $this->_parseResponse( $sBody );
				if ( is_array( $mData ) ) {
					if (
						! isset( $mData['success'] ) ||
						$mData['success'] == FALSE
					) {
						$this->_sErrorStr = isset( $mData['message'] ) ? $mData['message'] : 'unknown error';
						$this->_iErrorNo = isset( $mData['code'] ) ? $mData['code'] : 0;
						return FALSE;
					} else {
						return $mData;
					}
				} else {
					$this->_sErrorStr = 'unrecognized reply: ' . trim( $sBody );
					$this->_iErrorNo = 0;
					return FALSE;
				}
			} else {
				$this->_sErrorStr = 'zero-sized reply';
				$this->_iErrorNo = 0;
				return FALSE;
			}
		} else {
			$this->_sErrorStr = 'invalid service';
			$this->_iErrorNo = 0;
			return FALSE;
		}
	}

	/**
	 * Handle uploaded files to pass thru
	 * NB: this routine will use the (tmp)dir where the files are received
	 *     to create the new files to upload under the original name!
	 * @param array (byref) $aData_ Array to include files into
	 * @return void
	 */
	public function handleFiles( &$aData_ ) {
		foreach( $_FILES as $sField => $aFileInfo ) {
			if ( ! empty( $aFileInfo['tmp_name'] ) ) {
				if( ! is_array( $aFileInfo['tmp_name'] ) ) {
					$aFile = array();
					foreach( $aFileInfo as $sKey => $sValue ) {
						$aFile[$sKey] = array( $sValue );
					}
				} else {
					$aFile = $aFileInfo;
				}
				for( $i = 0, $e = count( $aFile['tmp_name'] ) ; $i < $e ; $i++ ) {
					if(
						is_uploaded_file( $aFile['tmp_name'][$i] )
						&& move_uploaded_file(
								$aFile['tmp_name'][$i]
								, $sFile = ( dirname( $aFile['tmp_name'][$i] ) . '/' . $aFile['name'][$i] )	)
					) {
						$aData_[$sField . "[$i]"] = '@' . $sFile;
					}
				}
			}
		}
	}

	public function getUserIP() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			//check ip from share internet
			$sIp = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$sIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$sIp = $_SERVER['REMOTE_ADDR'];
		}
		return $sIp;
	}

	/**
	 * Setters for protected members
	 */
	public function setApiHost( $sApiHost_ ) {
		$this->_sApiHost = $sApiHost_;
	}

	public function setApiPort( $iApiPort_ ) {
		$this->_iApiPort = $iApiPort_;
	}

	public function setMerchantId( $mMerchantId_ ) {
		$this->_mMerchantId = $mMerchantId_;
	}

	public function setMerchantSecret( $sMerchantSecret_ ) {
		$this->_sMerchantSecret = $sMerchantSecret_;
	}

	public function setSiteId( $mSiteId_ ) {
		$this->_mSiteId = $mSiteId_;
	}

	public function setTestMode( $bTestMode_ = TRUE ) {
		$this->_bTestMode = $bTestMode_;
	}

	public function getURL() {
		return ( $this->_iApiPort == 443 ? 'https://' : 'http://' ) . $this->_sApiHost;
	}

	/**
	 * Getters for protected members
	 */
	public function getMerchantId() {
		return $this->_mMerchantId;
	}

	public function getMerchantSecret() {
		return $this->_sMerchantSecret;
	}

	public function getSiteId() {
		return $this->_mSiteId;
	}

	public function getError() {
		return $this->_sErrorStr;
	}

	public function getErrorNo() {
		return $this->_iErrorNo;
	}

	// Debug info
	public function getLastRequest() {
		return $this->_sLastRequest;
	}

	public function getLastResult() {
		return $this->_sLastResult;
	}

	// General wrapper for API modules
	public function getWrapper( $sModule_ ) {
		return new curo_Wrapper( $this, $sModule_ );
	}
}

// Wrapper class for CURO Methods
class curo_Wrapper {

	var $_oCURO;
	var $_sModule;

	public function __construct( $oCURO_, $sModule_ ) {
		$this->_oCURO = $oCURO_;
		$this->_sModule = $sModule_;
	}

	public function __call( $sName_, $aArgs_ ) {
		return $this->_oCURO->execute( $aArgs_[0], $sName_, $aArgs_[1] );
	}
}