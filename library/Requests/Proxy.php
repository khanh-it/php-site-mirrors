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

// === Require files ===
// +++  
require_once dirname(__FILE__) . '/Requests.php';
Requests::register_autoloader();
// +++  
require_once dirname(__FILE__) . '/Proxy/MimeType.php';
 
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
	 * @var array Proxy data (for private uses)
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
	protected $_data = array();
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
	protected $_options = array();
	/**
	 * Get default request options 
	 * @return array 
	 */
	public function getOptions() {
		return $this->_options;
	}
	
	/**
	 * Get full url origin
	 * @author khanhdtp 2016-08-10
	 */
	public static function serverUrl($use_query_string = true, $use_forwarded_host = true) {
		$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $_SERVER['SERVER_PORT'];
		$port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
		$host = ($use_forwarded_host && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
		$host = isset($host) ? $host : $_SERVER['SERVER_NAME'] . $port;
		$url = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
		// Use query string?
		if (!$use_query_string) {
			$url = str_replace("?{$_SERVER['QUERY_STRING']}", '', $url);
		}
		// Return
		return $url;
	}
	
	/**
	 * Parse url parts
	 * 
	 * @param $url string Url string
	 * @param $baseUrl string Base url string
	 * @return string 
	 */
	public static function parseUrl($url, $baseUrl = '') {
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
			// +++ Domain with no subdomain
			$domain = explode('.', $urlParts['host']);
			$urlParts['domain'] = ($domain[($count = count($domain)) - 2]
				? ($domain[$count - 2] . '.') : '') . $domain[$count - 1]
			;
			unset($domain, $count);
		}
		// Format href with no `baseurl`.
		if ($baseUrl) {
			$href = $urlParts['href'];
			if (($urlParts['pathname'] != $baseUrl)
				&& (0 === strpos($urlParts['pathname'], $baseUrl))
			) {
				$href = str_replace(
					$urlParts['pathname'], 
					str_replace($baseUrl, '', $urlParts['pathname']), 
					$urlParts['href']
				);
			}
			$urlParts['href'] = $href;
		}
		// Return
		return $urlParts;
	}
	
	/**
	 * Get all request's headers
	 * 
	 * @return array
	 */
	public static function getAllRequestHeaders() {
		$headers = array();
		// Parse headers
		$httpK = 'HTTP_';
		foreach ($_SERVER as $key => $value) {
			if (0 === strpos($key, $httpK)) {
				$key = array_map(function($value){
					return ucfirst(strtolower($value));
				}, explode('_', str_replace($httpK, '', $key)));
				$key = implode('-', $key);
				if (!$headers[$key]) {
					$headers[$key] = $value;
				}
			}
		} unset($httpK, $key, $value);
		// Return
		return $headers;
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
		$this->_urlParts = self::parseUrl($url, $this->_baseUrl);
		if (empty($this->_urlParts)) {
			throw new Exception('Invalid $url provided!');
		}
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
		// Prepare request headers
		$this->_headers = self::getAllRequestHeaders();
		$this->_headers['Host'] = $this->_urlParts['host'];
		if ($this->_headers['Referer']) {
			$this->_headers['Referer'] = $this->_headers['Referer'];
		}
		
		/*require_once "\Zend\Debug.php";
		Zend_Debug::dump('===== Request headers: ');
		Zend_Debug::dump($this->_headers);
		Zend_Debug::dump($this->_data);
		Zend_Debug::dump($this->_options);
		Zend_Debug::dump($this->_urlParts);
		die();*/
		
		// Init Requests_Session
		$this->_req = new Requests_Session($this->_urlParts['href'], $this->_headers, $this->_data, $this->_options);
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
		
		// Init session data
		@session_start();
		if (!isset($_SESSION[__CLASS__])) {
			$_SESSION[__CLASS__] = new stdClass();
		}
		$this->_session = $_SESSION[__CLASS__];
		
		// Set origin url
		$this->_originUrlParts = self::parseUrl(self::serverUrl()); 
		// Set proxy url
		// +++ Case: proxy assets?
		if ($this->_prx['assets'] && ($url != $this->_prx['assets'])) {
			$url = $this->_prx['assets'];
		}
		$this->setUrl($url);
		
		// Initilize
		$this->_init();
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
	 * @return Requests_Response
	 */
	protected function _replace302Response(Requests_Response $response) {
		if (!$response->success && 302 == $response->status_code) {
			$response = $this->_req->get($response->headers['location']);
		}
		return $response;
	}
	
	/**
	 * Rewrite response body `html`.
	 * 
	 * @param $resBody string Response body `html`.
	 * @return mixed
	 */
	protected function _rewriteResponseHtml($resBody) {
		// Get params...
		// +++ Url parts
		$urlParts = $this->getUrlParts();
		// +++ Baseurl
		$baseUrl = $this->getBaseUrl();
		//
		$pattern = '/<(\w+)[^>]*((src|href|action|url)\s*=\s*([\'"]?)([^\'"]*)[\'"]?)[^>]*\/?>/is';
		$resBody = preg_replace_callback($pattern, function($matches) use($urlParts, $baseUrl) {
			// 
			list($eleStr, $tagName, $attrStr, $attrName, $attrQuote, $attrValue) = $matches;
			//require_once "\Zend\Debug.php";
			//Zend_Debug::dump($matches);
			// Case: 
			if (0 === strpos($attrValue, '//')) {
				$attrValue = "{$urlParts['protocol']}:{$attrValue}";
			}
			if ((0 === strpos($attrValue, 'http://'))
				|| (0 === strpos($attrValue, 'https://'))
			) {
				$attrValue = "?_prx[assets]=" . urlencode($attrValue);
			}
			// Case: 
			if (0 === strpos($attrValue, '/')) {
				$attrValue = $baseUrl . $attrValue;
				$eleStr = str_replace($attrStr, "{$attrName}={$attrQuote}{$attrValue}{$attrQuote}", $eleStr);
			}
			// Return;
			return $eleStr;
		}, $resBody);
		// ---
		$resBody = $this->_rewriteResponseCss($resBody);
		// Return
		return $resBody;
	}
	/**
	 * Rewrite response body `css`.
	 * 
	 * @param $resBody string Response body `css`.
	 * @return mixed
	 */
	protected function _rewriteResponseCss($resBody) {
		// Get params...
		// +++ Url parts
		$urlParts = $this->getUrlParts();
		// +++ Baseurl
		$baseUrl = $this->getBaseUrl();
		//
		$pattern = '/((url)\s*\(\s*[\'"]?(\/[^\'"()]+)[\'"]?\s*\))|((@import)\s[\'"]([^\'"]+)[\'"])/is';
		$resBody = preg_replace_callback($pattern, function($matches) use($urlParts, $baseUrl) {
			//  
			list($_, $attrStr, $attrName, $attrValue) = $matches;
			// Case:
			if (0 === strpos($attrValue, '//')) {
				$attrValue = "{$urlParts['protocol']}:{$attrValue}";
			}
			if ((0 === strpos($attrValue, 'http://'))
				|| (0 === strpos($attrValue, 'https://'))
			) {
				//$attrValue = "?_prx[assets]=" . urlencode($attrValue);
			}
			// Case: 
			if (0 === strpos($attrValue, '/')) {
				$attrStr = str_replace($attrValue, $baseUrl . $attrValue, $attrStr);
			}
			// Return;
			return $attrStr;
		}, $resBody);
		// Return
		return $resBody;
	}
	
	/**
	 * Rewrite cookie string 
	 * 
	 * @param $str string Cookie string 
	 * @return string
	 */
	protected function _rewriteCookieStr($str) {
		$proxy = $this;
		$pattern = '/(path|domain)\s*=\s*([^;]+);/';
		$str = preg_replace_callback($pattern, function($matches) use ($proxy) {
			list($str, $key, $val) = $matches;
			// Case: path
			if ('path' == $key) {
				$str = "{$key}=" . $proxy->getBaseUrl() . ';';
			} elseif ('domain' == $key) {
				$urlParts = $proxy->getOriginUrlParts(); 
				$str = "{$key}=" . "{$urlParts['domain']};";
			}
			return $str;
		}, $str);
		// Return
		return $str;
	}
	
	/**
	 * Send request
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
	 * Write response headers
	 * @return Requests_Proxy
	 */
	protected function _writeHeaders() {
		// Remove previously set headers
		header_remove();
		// Send HTTP response code
		if (!preg_match('/^HTTP.*/i', $this->_res->raw, $HTTPResponseCode)) {
			throw new Exception('`HTTP Response Code` could not be parsed');
		}
		header($HTTPResponseCode = trim($HTTPResponseCode[0]));
		//
		// Send new headers
		$headers = array_change_key_case($this->_res->headers->getAll(), CASE_LOWER);
		// +++ Clear unuse headers
		unset(
			// +++ Don't use this, as it should be controled by ours web server. 
			$headers['content-encoding']
		);
		// +++ Set required headers...
		// +++ +++ 
		if ($headers[$hKey = 'access-control-allow-origin']) {
			$headers[$hKey] = $this->_originUrlParts['host'];
		}
		// +++ +++ 
		if ($headers[$hKey = 'timing-allow-origin']) {
			$headers[$hKey] = $this->_originUrlParts['host'];
		} unset($hKey);
		// +++ Write headers
		foreach ($headers as $hKey => $hValues) {
			foreach ($hValues as $hValue) {
				// Rewrite cookies (if any)
				if ('set-cookie' == $hKey) {
					$hValue = $this->_rewriteCookieStr($hValue);
				}
				// Send header
				header("{$hKey}: {$hValue}", false);
			}
		}
		//require_once "\Zend\Debug.php";
		//Zend_Debug::dump('===== Response headers: ');
		//Zend_Debug::dump(headers_list());//die();
		// Return
		return $this;
	}

	/**
	 * Send response body
	 * @return mixed
	 */
	protected function _response() {
		$resBody = $this->_res->body;
		if (is_string($resBody)) {
			// Get response header `content-type`.
			$mimeType = $this->_res->headers['content-type'];
			
			/*$host = $this->_urlParts['host'];
			$nHost = $_SERVER['SERVER_NAME'] ?: $_SERVER['HTTP_HOST'];
			$resBody = str_replace(
				array("'{$host}'", "\"{$host}\""), 
				array("'{$nHost}'", "\"{$nHost}\""), 
				$resBody
			);*/
			// Case: content is 'html'
			if (Requests_Proxy_MimeType::isHtml($mimeType)) {
				$resBody = $this->_rewriteResponseHtml($resBody);
			}
			// Case: content is 'css'
			if (Requests_Proxy_MimeType::isCss($mimeType)) {
				$resBody = $this->_rewriteResponseCss($resBody);
			}
		}
		// Return
		return $resBody;
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