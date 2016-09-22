<?php
/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */

// 
require_once dirname(__FILE__) . '/Requests.php';
Requests::register_autoloader();
 
/**
 * Requests for PHP
 *
 * Inspired by Requests for Python.
 *
 * Based on concepts from SimplePie_File, RequestCore and WP_Http.
 *
 * @package Requests
 */
class Requests_Proxy {
	/**
	 * @var array Proxy data 
	 */
	protected $_prx = array();
	
	/**
	 * @var array Default request headers! 
	 */
	protected $_headers = array();
	/**
	 * Get default request headers 
	 * @return array 
	 */
	public function getHeaders() {
		return $this->_headers;
	}
	
	/**
	 * @var array Default request data! 
	 */
	protected $_data = array(
		'GET' => null,
		'POST' => null,
	);
	/**
	 * Get default request data 
	 * @return array 
	 */
	public function getData() {
		return $this->_data;
	}
	
	/**
	 * @var array Default request options! 
	 */
	protected $_options = array(
		//'transport' => 'Requests_Transport_fsockopen'
	);
	/**
	 * Get default request options 
	 * @return array 
	 */
	public function getOptions() {
		return $this->_options;
	}
	
	/**
	 * Parse url parts
	 * 
	 * @param $url string Url string
	 * @return string 
	 */
	public static function parseUrl($url) {
		$urlParts = array();
		// Get url parts
		$pattern = '/((https?):\/\/([^\/:]+)):?(\d+)?([^?]*)(\??.*)/i';
		if (preg_match($pattern, $url, $matches)) {
			$urlParts['href'] = $matches[0];
			$urlParts['origin'] = $matches[1];
			$urlParts['protocol'] = $matches[2];
			$urlParts['host'] = $matches[3];
			$urlParts['hostname'] = $matches[3];
			$urlParts['port'] = $matches[4];
			$urlParts['pathname'] = $matches[5];
			$urlParts['search'] = $matches[6];
		}
		// Return
		return $urlParts;
	}
	
	/**
	 * @var string Origin proxy url parts
	 */
	protected $_originUrlParts = array();
	/**
	 * Get origin proxy url parts
	 * @return string 
	 */
	public function getOriginUrlParts() {
		return $this->_originUrlParts;
	}
	
	/**
	 * @var string Proxy url parts
	 */
	protected $_urlParts = array();
	/**
	 * Get proxy url parts
	 * @return string 
	 */
	public function getUrlParts() {
		return $this->_urlParts;
	}
	
	/**
	 * Set proxy url
	 * 
	 * @param $url string Proxy url (exp: https://google.com)
	 * @return Requests_Proxy 
	 */
	public function setUrl($url) {
		// Get url parts
		$this->_urlParts = self::parseUrl($url);
		if (empty($this->_urlParts)) {
			throw new Exception('Invalid $url provided!');
		}
		// +++ Url, href with no `baseurl`.
		$url = $this->_urlParts['href'];
		if (($this->_urlParts['pathname'] != $this->_baseUrl)
			&& (0 === strpos($this->_urlParts['pathname'], $this->_baseUrl))
		) {
			$url = str_replace(
				$this->_urlParts['pathname'], 
				str_replace($this->_baseUrl, '', $this->_urlParts['pathname']), 
				$this->_urlParts['href']
			);
		}
		$this->_urlParts['url'] = $url;
		// Return
		return $this;
	}
	
	/**
	 * @var string Base url
	 */
	protected $_baseUrl = '/';
	/**
	 * Get proxy url
	 * @return string 
	 */
	public function getBaseUrl() {
		return $this->_baseUrl;
	}
	
	/**
	 * @var string Assets dir
	 */
	protected $_assetsDir;
	/**
	 * Get Assets dir
	 * @return string 
	 */
	public function getAssetsDir() {
		return $this->_assetsDir;
	}
	
	/**
	 * @var stdClass Session data 
	 */
	protected $_session;
	
	/**
	 * @var Requests_Session
	 */
	protected $_req;
	
	/**
	 * @var Requests_Response
	 */
	protected $_res;
	
	/**
	 * Initilize
	 */
	protected function _init() {
		// Set default headers
		$httpK = 'HTTP_';
		foreach ($_SERVER as $key => $value) {
			if (0 === strpos($key, $httpK)) {
				$key = array_map(function($value){
					return ucfirst(strtolower($value));
				}, explode('_', str_replace($httpK, '', $key)));
				$key = implode('-', $key);
				if (!$this->_headers[$key]) {
					$this->_headers[$key] = $value;
				}
			}
		} unset($httpK, $key, $value);
		// +++
		$this->_headers['Host'] = $this->_urlParts['host'];
		if ($this->_headers['Referer']) {
			$this->_headers['Referer'] = $this->_rewriteResource($this->_headers['Referer']);
		}
		
		// Init session data
		/*@session_start();
		if (!isset($_SESSION[__CLASS__])) {
			$_SESSION[__CLASS__] = new stdClass();
		}
		$this->_session = $_SESSION[__CLASS__];*/
		
		require_once "\Zend\Debug.php";
		Zend_Debug::dump($this->_headers);
		/*Zend_Debug::dump($this->_data);
		Zend_Debug::dump($this->_options);*/
		die();
		
		// Init Requests_Session
		$this->_req = new Requests_Session($this->_urlParts['url'], $this->_headers, $this->_data, $this->_options);
	}

	/**
	 * Set options
	 * @param $options array An array of options
	 * @return Requests_Proxy
	 */
	public function setOptions(array $options = array()) {
		// 
		foreach ($options as $key => $value) {
			switch (strtolower($key)) {
			// Request headers
				case 'headers': {
					$this->_headers = array_merge($this->_headers, (array)$value);
				} break;
			// Request data
				case 'data': {
					$this->_data = array_merge($this->_data, (array)$value);
				} break;
			// Request options
				case 'options': {
					$this->_options = array_merge($this->_options, (array)$value);
				} break;
			// Base url
				case 'base_url': {
					$this->_baseUrl = trim($value);
				} break;
			// Assets Dir
				case 'assets_dir': {
					$this->_assetsDir = trim($value);
				} break;
			}
		}
		// Return
		return $this;
	}
	
	/**
	 * Constructor
	 * 
	 * @param $url string Proxy url
	 * @param $options array An array of options
	 * @return void
 	 */
	public function __construct($url, array $options = array())
	{
		// Get internal proxy params
		$this->_prx = (array)$_GET['_prx'];
		unset($_GET['_prx']);
		
		// Set options
		$this->setOptions($options);
		
		// Set proxy url
		// +++ Case: proxy assets?
		if ($this->_prx['assets'] && ($url != $this->_prx['assets'])) {
			$this->_originUrlParts = self::parseUrl($url);
			$url = $this->_prx['assets'];
		}
		$this->setUrl($url);
		
		// Initilize
		$this->_init();
	}
	
	/**
	 * 
	 */
	public function _rewriteProxyAssets($matches) {
		// 
		list($eleStr, $tagName, $attrStr, $attrName, $attrQuote, $attrValue) = $matches;
		/*require_once "\Zend\Debug.php";
		Zend_Debug::dump($matches);
		return $eleStr;*/
		
		// Case: 
		if (0 === strpos($attrValue, '//')) {
			$attrValue = "{$this->_urlParts['protocol']}:{$attrValue}";
		}
		if ((0 === strpos($attrValue, $protocol = 'http://'))
			|| (0 === strpos($attrValue, $protocol = 'https://'))
		) {
			//$attrValue = "?_prx[assets]=" . urlencode($attrValue);
		}
		// Case: 
		if (0 === strpos($attrValue, '/')) {
			$attrValue = $this->_baseUrl . $attrValue;
		}
		
		// Return;
		return str_replace($attrStr, "{$attrName}={$attrQuote}{$attrValue}{$attrQuote}", $eleStr);
	}

	/**
	 * Rewrite resource
	 * @return mixed
	 */
	protected function _rewriteResource($resource) {
		if (is_string($resource) /*&& false*/) {
			/*$host = $this->_urlParts['host'];
			$nHost = $_SERVER['SERVER_NAME'] ?: $_SERVER['HTTP_HOST'];
			$resource = str_replace(
				array("'{$host}'", "\"{$host}\""), 
				array("'{$nHost}'", "\"{$nHost}\""), 
				$resource
			);*/
			//
			//require_once "\Zend\Debug.php";
			//Zend_Debug::dump($this->_res->headers['content-type']);
			if ((0 === strpos($this->_res->headers['content-type'], 'text/html;'))) {
				$pattern = '/<(\w+)[^>]*((src|href|action)\s*=\s*([\'"])([^\'"]*)[\'"])[^>]*\/?>/is';
				$resource = preg_replace_callback($pattern, array($this, '_rewriteProxyAssets'), $resource);
			}
		}
		// Return
		return $resource;
	}

	/**
	 * Is POST requests?
	 * @return bool
	 */
	protected function _isRequestPOST() {
		return 'POST' == $_SERVER['REQUEST_METHOD'];
	}
	/**
	 * Is GET requests?
	 * @return bool
	 */
	protected function _isRequestGET() {
		return 'GET' == $_SERVER['REQUEST_METHOD'];
	}
	
	/**
	 * 
	 */
	protected function _response() {
		return $this->_rewriteResource($this->_res->body);
	}
	
	/**
	 * 
	 * @return Requests_Response
	 */
	protected function _replace302Response(Requests_Response $response) {
		if (!$response->success && 302 == $response->status_code) {
			$response = $this->_req->get($response->headers['location']);
		}
		return $response;
	}
	
	/**
	 * 
	 * @return Requests_Proxy
	 */
	protected function _request($url = null)
	{
		// POST requests?
		if ($this->_isRequestPOST()) {
			$this->_res = $this->_req->post($url, null, $_POST, null);
		// 	GET requests
		} elseif ($this->_isRequestGET()) {
			$this->_res = $this->_req->get($url);
		}
		//require_once "\Zend\Debug.php";
		//Zend_Debug::dump($this->_res);die();
		// Return
		return $this;
	}
	
	/**
	 * @return Requests_Proxy
	 */
	protected function _writeHeaders() {
		// Get all headers
		$headers = $this->_res->headers->getAll();
		//if ((0 === strpos($this->_res->headers['content-type'], 'text/html;'))) {
			// Clear does not use headers
			unset(
			// Don't use this, as it should be controled by ours web server. 
				$headers['content-encoding']
			);
			// Set required headers...
			// +++ 
			$host = $_SERVER['SERVER_NAME'] ?: $_SERVER['HTTP_HOST'];
			$headers['access-control-allow-origin']
				= $headers['timing-allow-origin'] 
				= array("http://{$host}")
			;
			//require_once "\Zend\Debug.php";
			//Zend_Debug::dump($headers);die();
			
			// 
			foreach ((array)$headers as $key => $value) {
				header("{$key}: " . implode('', $value));
			}
		//}
		// Return
		return $this;
	}
	
	/**
	 * Main function
	 */
	public function run()
	{
		/*if ($this->_assetsDir) {
			$filename = $this->_assetsDir . $this->_urlParts['pathname'];
			if (($uri == $this->_baseUrl) || (!is_file($filename) && !is_dir($filename))) {}
		}*/
		// Handle requests
		echo $this
			->_request()
			->_writeHeaders()
			->_response()
		;
		// Return
		return $this;
	}
}