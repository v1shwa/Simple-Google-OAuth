<?php 
/*
 * Copyright 2015 Vishwa Datta
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Simple Google OAuth Library
 * 
 * @version     1.0-dev
 * @author      Vishwa <vishwadatta05@gmail.com>
 */


/**
* Google Auth
*/
class SimpleGoogleOAuth
{
	/**
	 * Authorization URL
	 * @var string
	 */		
	protected $auth_endpoint  = 'https://accounts.google.com/o/oauth2/auth';
	

	/**
	 * Access Token URl
	 * @var string
	 */
	protected $token_endpoint = 'https://accounts.google.com/o/oauth2/token';


	/**
	 * Access Token Data
	 * @var array
	 */
	protected $token_data = array();


	/**
	 * Client Id
	 * @var string
	 */
	protected $client_id = null;


	/**
	 * Client Secret
	 * @var string
	 */
	protected $client_secret = null;



	/**
	 * Configuration file (adapted from google's official PHP client).
	 * @var array
	 */
	private $configuration = array(
			'appname'                    => 'My Google App',
			'response_type'              => 'code',
			'redirect_uri'               => '',
			'scope'                      => '',
			
			//Server key
			'developer_key'              => '',
			
			// Other parameters.
			'hd'                         => '',
			'prompt'                     => '',
			'openid.realm'               => '',
			'include_granted_scopes'     => '',
			'login_hint'                 => '',
			'request_visible_actions'    => '',
			'access_type'                => 'online',
			'approval_prompt'            => 'auto',
			'federated_signon_certs_url' => 'https://www.googleapis.com/oauth2/v1/certs'
      );


	/**
	 * Constructing the GoogleOAuth Library.
	 * @param string $client_id     Client Id (required)
	 * @param string $client_secret Client Secret (required)
	 */
	function __construct($client_id=null, $client_secret=null) 
	{
		if ( !($client_id AND $client_secret) ) {
			throw new Exception("client_id & client_secret are mandatory.");
		}

		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}


	/**
	 * Set different attributes in the $configuration error.
	 * @param string $key   name
	 * @param string $value value
	 */
	public function setAttr($key, $value) 
	{
		if ( array_key_exists($key, $this->configuration) )
			 $this->configuration[$key] = $value;
		else 
			throw new Exception("Invalid Configuration Key: $key");
	}


	/**
	 * Get the $configuration values.
	 * @param  string $key name
	 * @return string      value
	 */
	public function getAttr($key)
	{
		if (array_key_exists($key, $configuration))
			return $this->configuration[$key];
		else 
			throw new Exception("Configuration Key $key doesn't exist");
	}


	/**
	 * Build the Authentication URL.
	 * @return string URL
	 */
	public function createAuthUrl()
	{
		$params = array('client_id' => $this->client_id);
		foreach ($this->configuration as $k => $v) {
			if ($v != '') $params[$k] = $v;
		}

		return $this->auth_endpoint .'?'. http_build_query($params, '', '&');
	}


	/**
	 * Set the access Token.
	 * @param mixed $tok access tokens (could be Array or JSON)
	 */
	public function setAccessToken($tok) {

		// If not array, this should be JSON.
		if (!is_array($tok)) $tok = json_decode($tok, true);
		
	    if ($tok == null) 
	      throw new Exception("Invalid token format. Expecting be array or json string");
	    
	    if ( !isset($tok['access_token']) ) 
	      throw new Exception("Token must contain access_token key");
	    
	    $this->token_data = $tok;
	}


	/**
	 * Create Access Token from Code.
	 * @param  string $code Code value
	 * @return array       accesstoken
	 */
	public function createAccessToken($code)
	{
		if (empty($code)) {
			throw new Exception("code cannot be empty.");
		}


		$params = array(
          'code' => $code,
          'grant_type' => 'authorization_code',
          'client_id' =>  $this->client_id,
          'client_secret' => $this->client_secret,
          'redirect_uri' => $this->configuration['redirect_uri']
    	);

		$this->token_data    = $this->makeRequest($this->token_endpoint , $params);
		return $this->token_data;
	}

	/**
	 * Get Access Token in JSON to store in database or files.
	 * @return json access token data
	 */
	public function getAccessToken()
	{
		return json_encode($this->token_data);
	}

	/**
	 * Check if Access Token has expired.
	 * @return boolean true if expired
	 */
	public function isAccessTokenExpired() 
	{
		if (isset($this->token_data['created_at'])) {
			$expired = ( $this->token_data['created_at']+ ($this->token_data['expires_in'] - 30) ) < time();
		} else {
			$query   = @file_get_contents("https://www.googleapis.com/oauth2/v1/tokeninfo?access_token=".$this->token_data['access_token']);
			$expired = ( json_decode($query,true)['expires_in'] ) ? false : true;
		}
		return $expired;
	}


	/**
	 * Creates a new access Token.
	 * @return array refreshed token
	 */
	public function refreshToken()
	{
		if (! array_key_exists('refresh_token', $this->token_data) )
			throw new Exception("refresh_token doesn't exist");
			

	    $params =  array(
          'client_id' => $this->client_id,
          'client_secret' => $this->client_secret,
          'refresh_token' => $this->token_data['refresh_token'],
          'grant_type' => 'refresh_token'
        );

	    $this->token_data    = $this->makeRequest($this->token_endpoint , $params);
		return $this->token_data;
	}


	/**
	 * Perform Curl Operation.
	 * @param  string $url    Url
	 * @param  array $params arguments to POST
	 * @return array         result
	 */
	private function makeRequest($url, $params=array() )
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_USERAGENT, 'User-Agent: '. $this->configuration['appname']);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, count($params));
		curl_setopt($ch,CURLOPT_POSTFIELDS,  http_build_query($params, null, '&') );

		$result        = curl_exec($ch);

		$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ( $curl_error = curl_error($ch) )
            throw new Exception("Curl Request Failed: $curl_error");

		curl_close($ch);

        $result = json_decode($result, true);

        // Check for valid http code.
        if ( 200 != $response_code) 
        	throw new Exception("Error:". $result['error'] . '-'.$result['error_description']);

        // Check if error code exists.
		if (@$result['code']){
			$err_msg = "Error: ". $result['result']['error'] . ' - '.  $result['result']['error_description'];
			throw new Exception($err_msg);
		}
		
		$result['created_at'] = time();
	    return $result;
	}


}

?>