<?php
/* phpEdocr Class 0.1.9
 *
 */ 
require_once("library/class.curl.php");

class phpEdocr {
	var $consumer_key;
	var $consumer_secret;
	var $token_key;
	var $token_secret;
	var $callback_url;
	const REQUEST_TOKEN_ENDPOINT 		= "http://www.edocr.com/api/request_token";
	const AUTHORIZE_ENDPOINT				= "http://www.edocr.com/api/authorize";
	const ACCESS_TOKEN_ENDPOINT			= "http://www.edocr.com/api/access_token";
	const CALL_API_METHOD_ENDPOINT	= "http://www.edocr.com/api/echo_api";	
	const UPLOAD_DOCUMENT_ENDPOINT	= "http://www.edocr.com/api/customer/upload";

	
	function phpEdocr($consumer_key, $consumer_secret, $new_request=true, $require_auth = true ) {
		// Initialize the consumer-key, consumer-secret, callback url. And check if an access-token already exist.
		$this->consumer_key 		= $consumer_key;
		$this->consumer_secret 	= $consumer_secret;
		$this->callback_url 		= "http://edocr.com/userdocs/index.php?callbackid=" . session_id();
		// Check if access-token has already been issued.
		if($this->is_token_exist("access")) {
			// Initialize the access-token, if one is already saved.
			$accessToken = $this->get_access_token();
			$this->token_key=$accessToken->key;
			$this->token_secret=$accessToken->secret;
		}
	}
	/**
	 * Call this function to start an authentication process. 
	 * Sends a request for request-token, fetches a new request-token and redirect the page to edocr.com 
	 * for authorizing the request token. 
	 *
	 */
	public function new_auth_request() {
		try {
			// destroy the token if one exist already
			$this->destroy_token();
			$this->get_new_request_token();
		}
		catch (Exception $e) {
			echo "Error : {$e->getMessage()}";
		}
	}
	
	/**
	 * Call this function once the authorization is complete.
	 * Fetches an access-token once the request-token has been authorized.
	 * 
	 *
	 */
	public function on_callback_after_authorize() {
		try {
			if(!$this->is_token_exist("access")) {
				$requestToken = $this->get_request_token();
				$accessToken  = $this->get_new_access_token($requestToken);
			}
			else {
				//	Access Token found in session
				$accessToken = $this->get_access_token();
			}
			$this->token_key=$accessToken->key;
			$this->token_secret=$accessToken->secret;
		}
		catch (Exception $e) {
			echo "Error : {$e->getMessage()}";
		}
	}
	
	
	/**
	 * Obtain new request token
	 *
	 */
	private function get_new_request_token() {
			$requestToken = $this->fetch_new_token();		
	}
	
	/**
	 * Generate request-token request. Get request token response, process it. Save the token. Redirect 
	 * the page to authorize the request-token
	 *
	 */
	private function fetch_new_token() {
		$requestTokenResponse = $this->fetch_request_token();
		$requestToken = $this->processResponse($requestTokenResponse);
		$this->save_token("request",$requestToken->requestToken);
		$this->authorize_request_token($requestToken->requestToken);
	}
	
	/**
	 * Process the JSON response
	 *
	 * @param string $response
	 * @return array
	 */
	public function processResponse($response) {
		$resp_dec = json_decode($response);
		if($resp_dec->status!="ok") {
			// throw the error message.
			throw new Exception($resp_dec->message);
		}
		return $resp_dec;
	}
	
	/**
	 * Generate new access-token request. Fetch and process response. Save token.
	 *
	 * @param array $requestToken
	 * @return array
	 */
	private function get_new_access_token($requestToken) {
		$accessTokenResponse = $this->fetch_new_access_token($requestToken);
		$accessToken = $this->processResponse($accessTokenResponse);
		$this->save_token("access",$accessToken->accessToken);
		return $accessToken->accessToken;
	}
	
	/**
	 * Build and call URL for fetching request-token
	 *
	 * @return string
	 */
	private function fetch_request_token() {
		list($url,$params) = $this->build_request(
			self::REQUEST_TOKEN_ENDPOINT ,
			array(
				"oauth_consumer_key" 	=> $this->consumer_key,
			),
			array(
				"token_secret" 				=> null
			)
		);
		$response = $this->get_response($url,$params);
		return $response;
	}
	
	
	
	/**
	 * Build and redirect the page to URL for authorizing request-token
	 *
	 * @param array $requestToken
	 */
	private function authorize_request_token($requestToken) {
		list($auth_url,$params) = $this->build_request(
			self::AUTHORIZE_ENDPOINT ,
			array(
				"oauth_consumer_key" 	=> $this->consumer_key,
				"oauth_token" 				=> $requestToken->key,
				"oauth_callback" 			=> $this->callback_url
			),
			array(
				"consumer_secret" 		=> $this->consumer_secret,
				"token_secret" 				=> $requestToken->secret
			)
		);
		header("Location: " . $this->to_url($auth_url,$params));
	}
	
	/**
	 * Build and call URL for fetching access-token
	 *
	 * @param array $requestToken
	 * @return string
	 */
	private function fetch_new_access_token($requestToken) {
		list($url,$params) = $this->build_request(
			self::ACCESS_TOKEN_ENDPOINT ,
			array(
				"oauth_consumer_key" 	=> $this->consumer_key,
				"oauth_token" 				=> $requestToken->key,
			),
			array(
				"token_secret" 				=> $requestToken->secret
			)
		);
		$response = $this->get_response($url,$params);
		return $response;
	}
	
	
	/**
	 * Call API method.
	 *
	 * @param string $method
	 * @param array $extra_params
	 * @param bool $require_auth
	 * @param string $http_method
	 * @return string
	 */
	public function call_api_method($method,$extra_params,$require_auth=true,$http_method) {
		$req_params = array(
				"oauth_consumer_key" 	=> $this->consumer_key,
				"method" 							=> $method
			);
		if($require_auth==true) {
			$req_params["oauth_token"] = $this->token_key;
		}
		
		$req_params = array_merge($req_params,$extra_params);
		list($api_method_url,$params) = $this->build_request(
			self::CALL_API_METHOD_ENDPOINT ,
			$req_params,
			array(
				"consumer_secret" => $this->consumer_secret,
				"token_secret" 		=> (($require_auth==true)? $this->token_secret : "")
			),
			$http_method
		);
		$response = $this->get_response($api_method_url,$params,$http_method);
		return $response;
	}
	
	
	/**
	 * Call this function to upload document.
	 *
	 * @param string $doc_path
	 * @param array $extra_params
	 * @return string
	 */
	public function upload_document($doc_path,$extra_params) {
		$req_params = array(
				"oauth_consumer_key" 	=> $this->consumer_key,
				"oauth_token" 				=> $this->token_key
			);
		$req_params = array_merge($req_params,$extra_params);
		list($upload_url,$params) = $this->build_request(
			self::UPLOAD_DOCUMENT_ENDPOINT  ,
			$req_params,
			array(
				"consumer_secret" => $this->consumer_secret,
				"token_secret" 		=> $this->token_secret
			),
			"POST"
		);
		$response = $this->curl_upload($upload_url,$doc_path,$params);
		return $response;
	}
	
	
	
	
	/**
	 * Get response from the passed URL
	 *
	 * @param string $url
	 * @param string $http_method
	 * @return string
	 */
	private function get_response($url,$params,$http_method="GET") {
		return $this->curl_execute($url,$params,$http_method);
	}
	
	
	/**
	 * Send request to API call URL using cUrl. This function make use of the curl class.
	 *
	 * @param string $url
	 * @param string $http_method
	 * @return string
	 */
	private function curl_execute($url,$params,$http_method) {
		if($http_method=="POST") {
			$req = curl_init();
			curl_setopt($req, CURLOPT_URL,$url);
			curl_setopt($req, CURLOPT_TIMEOUT, 0);
			curl_setopt($req, CURLOPT_POSTFIELDS, $params);
			curl_setopt($req, CURLOPT_CONNECTTIMEOUT, 3600);
			curl_setopt($req, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($req, CURLOPT_HEADER, 0);
			curl_setopt($req, CURLOPT_RETURNTRANSFER, 1);
			$resp = curl_exec($req);
			return $resp;
			if (curl_errno($req)) {
				echo (curl_error($req));
			}
			curl_close($req);
		}
		else {
			$c = new curl($this->to_url($url,$params));
			$c->setopt(CURLOPT_FOLLOWLOCATION, true) ;
			return $c->exec() ;
			if ($theError = $c->hasError()) {
			  throw new Exception($theError);
			}
			$c->close() ;
		}
	}
	
	/**
	 * Send request to API call URL using cUrl for uploading documents.
	 *
	 * @param unknown_type $url
	 * @param unknown_type $doc_path
	 * @param unknown_type $params
	 * @return unknown
	 */
	private function curl_upload($url,$doc_path,$params) {
		$params['document'] = "@".$doc_path;
		$c = new curl($url) ;
		$c->setopt(CURLOPT_FOLLOWLOCATION, 1) ;
		$c->setopt(CURLOPT_POSTFIELDS, $params);
		$c->setopt(CURLOPT_TIMEOUT, 0);
		$c->setopt(CURLOPT_CONNECTTIMEOUT, 3600);
		$c->setopt(CURLOPT_HEADER, 0);
		$c->setopt(CURLOPT_RETURNTRANSFER, 1);
		$res = $c->exec();
		return $res;
		if ($theError = $c->hasError()) {
		  throw new Exception($theError);
		}
		$c->close();
	}
	
	
	
	/**
	 * Get the saved request token.
	 *
	 * @return array
	 */
	private function get_request_token() {
		if($this->is_token_exist("request")) {
			$token = substr($_SESSION['token'],8);
			return (unserialize($token));
		}
		else return false;
	}
	
	
	/**
	 * Get the saved access token (from session)
	 *
	 * @return array
	 */
	private function get_access_token() {
		if($this->is_token_exist("access")) {
			$token = substr($_SESSION['token'],7);
			return (unserialize($token));
		}
		else return false;
	}
	
	/**
	 * Save the token (in session)
	 *
	 * @param string $type
	 * @param array $token
	 */
	public function save_token($type,$token) {
		session_unregister("token");
		$_SESSION['token'] = "{$type}_".serialize($token);
	}
	
	/**
	 * Destroy the token (from session)
	 *
	 */
	public function destroy_token() {
		session_unregister("token");
		unset($this->token_key);
		unset($this->token_secret);
	}
	
	/**
	 * Check if the mentioned token type is saved.
	 *
	 * @param string $token_type
	 * @return bool
	 */
	public function is_token_exist($token_type) {
		return (isset($_SESSION['token']) && substr($_SESSION['token'],0,strlen($token_type))==$token_type);
	}

	
	/**
	 * Build request and sign using the $sig_params
	 *
	 * @param string $http_url
	 * @param array $req_params
	 * @param array $sig_params
	 * @param string $http_method
	 * @return array
	 */
	private function build_request($http_url,$req_params, $sig_params,$http_method = "GET") {
		$misc_req_params = $this->misc_req_params();
		
		$req_params = array_merge($req_params,$misc_req_params);
		$signature = $this->sign_request($http_url,$req_params,$sig_params,$http_method);
		$req_params = array_merge($req_params,array("oauth_signature" => $signature));
		// return url and the parameters
		return array(
			$http_url,
			$req_params
		);
	}

	/**
	 * All parameter names and values are escaped using the [RFC3986] percent-encoding (%xx) mechanism.
	 *
	 * @param unknown_type $http_url
	 * @param unknown_type $req_params
	 * @return unknown
	 */
  private function to_url($http_url,$req_params) {
		return $this->get_normalized_http_url($http_url) . "?". $this->to_postdata($req_params);
  }
  
  /**
   * Encode the parameters in the request
   *
   * @param array $req_params
   * @return string
   */
  private function to_postdata($req_params) {
    $total = array();
    foreach ($req_params as $k => $v) {
      $total[] = $this->urlencodeRFC3986($k) . "=" . $this->urlencodeRFC3986($v);
    }
    $out = implode("&", $total);
    return $out;
  }
  
  /**
   * Miscellaneous parameters
   *
   * @return array
   */
	private function misc_req_params() {
		return array(
			"oauth_timestamp"	=> time()
		);
	}
	
	/**
	 * Sign the request using HMAC-SHA1
	 *
	 * @param string $http_url
	 * @param array $req_params
	 * @param array $sig_params
	 * @param string $http_method
	 * @return string
	 */
	private function sign_request($http_url,$req_params,$sig_params,$http_method) {
		// base string to be signed
		$base_string = $this->get_signature_base_string($http_url,$req_params,$http_method);
		// key for signing the base string
    $key_parts = array(
      $this->consumer_secret,
      ($sig_params['token_secret']) ? $sig_params['token_secret'] : ""
    );
    $key = implode('&', $key_parts);
    
    // signature method used is HMAC-SHA1
    return base64_encode( hash_hmac('sha1', $base_string, $key, true));
	}
	
	/**
	 * Get the base string to generate the signature
	 *
	 * @param string $http_url
	 * @param array $req_params
	 * @param string $http_method
	 * @return string
	 */
  private function get_signature_base_string($http_url,$req_params,$http_method) {
    $parts = array(
    	strtoupper($http_method),
      $this->get_normalized_http_url($http_url),
      $this->get_signable_parameters($req_params)
    );

    $parts = array_map(array('phpEdocr', 'urlencodeRFC3986'), $parts);

    return implode('&', $parts);
  }
	
  /**
   * Normalize the URL
   *
   * @param string $http_url
   * @return string
   */
	private function get_normalized_http_url($http_url) {
    $parts = parse_url($http_url);

    $port = @$parts['port'];
    $scheme = $parts['scheme'];
    $host = $parts['host'];
    $path = @$parts['path'];

    $port or $port = ($scheme == 'https') ? '443' : '80';

    if (($scheme == 'https' && $port != '443')
        || ($scheme == 'http' && $port != '80')) {
      $host = "$host:$port";
    }
    return "$scheme://$host$path";
  }	
  
  /**
   * Get the parameters to be signed.
   *
   * @param array $req_params
   * @return string
   */
	private function get_signable_parameters($req_params) {
    // Grab all parameters
    $params = $req_params;
		
    // Remove oauth_signature if present
    if (isset($params['oauth_signature'])) {
      unset($params['oauth_signature']);
    }
		
    // Urlencode both keys and values
    $keys = array_map(array('phpEdocr', 'urlencodeRFC3986'), array_keys($params));
    $values = array_map(array('phpEdocr', 'urlencodeRFC3986'), array_values($params));
    $params = array_combine($keys, $values);

    // Sort by keys (natsort)
    uksort($params, 'strnatcmp');

    // Generate key=value pairs
    $pairs = array();
    foreach ($params as $key=>$value ) {
      if (is_array($value)) {
        // If the value is an array, it's because there are multiple 
        // with the same key, sort them, then add all the pairs
        natsort($value);
        foreach ($value as $v2) {
          $pairs[] = $key . '=' . $v2;
        }
      } else {
        $pairs[] = $key . '=' . $value;
      }
    }
		
    // Return the pairs, concated with &
    return implode('&', $pairs);
  }
  
  /**
   * All parameter names and values are escaped using the [RFC3986] percent-encoding (%xx) mechanism. 
   *
   * @param string $string
   * @return string
   */
  private static function urlencodeRFC3986($string) {
    return str_replace('+', ' ',
                       str_replace('%7E', '~', rawurlencode($string)));
    
  }
}
