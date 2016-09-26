<?php
/**
 *
 * @package Requests_Proxy
 */

// === Require files ===
// +++  
require_once dirname(__FILE__) . '/Requests.php';
Requests::register_autoloader();
// +++  
require_once dirname(__FILE__) . '/Proxy/Hooks.php';
require_once dirname(__FILE__) . '/Proxy/Common.php';
require_once dirname(__FILE__) . '/Proxy/MimeType.php';
 
/**
 *
 * @package Requests_Proxy
 */
class Requests_Proxy {
	/**
	 * @var array Proxy options
	 */
	protected $_prx = array();
	
	/**
	 * Get assets url
	 * 
	 * @return string
	 */
	public function getAssetsUrl() {
		if ($this->_prx['assets']) {
			$assets = str_replace(
				array('https://', 'http://'),
				array('', ''), 
				$this->_prx['assets']
			);
			$filename = $this->_assetsDir . "/{$assets}";
			if (is_file($filename)) {
				return $this->_originUrlParts['origin'] . "/{$assets}";
			}
			return $this->_prx['assets'];
		}
	}
	
	/**
	 * @var Requests_Proxy_Hooks 
	 */
	protected $_hooksUtil;
	/**
	 * Get hooks utility
	 * @return Requests_Proxy_Hooks 
	 */
	public function getHooksUtil() {
		return $this->_hooksUtil;
	}
	
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
		$this->_urlParts = Requests_Proxy_Common::parseUrl($url, $this->_baseUrl);
		if (empty($this->_urlParts)) {
			throw new Exception('Invalid $url provided!');
		}
		// Return
		return $this;
	}
	
	/**
	 * @var string Base url
	 */
	protected $_baseUrl = '';
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
	 * Hooks handler
	 * @return Requests_Proxy
	 */
	public function _hooksHandler($evt, &$arguments) {
		switch ($evt) {
			// requests.before_request
			case 'requests.before_request': {
				//list($url, $headers, $data, $type, $options) = $arguments;
			} break;
			// #end#requests.before_request
			
			// requests.before_parse
			case 'requests.before_parse': {
			
			} break;
			// #end#requests.before_parse
			
			
			// multiple.request.complete
			case 'multiple.request.complete': {
					
			} break;
			// #end#multiple.request.complete
			
			// request.before_redirect_check
			case 'request.before_redirect_check': {
					
			} break;
			// #end#request.before_redirect_check
			
			// request.after_request
			case 'request.after_request': {
					
			} break;
			// #end#request.after_request
		}
	}
	
	/**
	 * Initilize
	 */
	protected function _init() {
		// Assign self use
		$proxy = $this;
		
		// Init hooks
		$hooksEvts = array(
			'requests.before_request',
			'requests.before_parse',
			'multiple.request.complete',
			'requests.before_redirect_check',
			'requests.after_request'
		);
		if (!($this->_options['hooks'] instanceof Requests_Hooks)) {
			$this->_options['hooks'] = ($hooks = new Requests_Hooks());
		}
		foreach ($hooksEvts as $hooksEvt) {
			$hooks->register($hooksEvt, function() use ($proxy, $hooksEvt) {
				$proxy->_hooksHandler($hooksEvt, func_get_args());
			});
		} unset($hooksEvts, $hooksEvt);
		
		// Prepare request headers
		$this->_headers = Requests_Proxy_Common::getAllRequestHeaders();
		// , remove unnecessary keys
		unset(
			// Those was set by `Requests` library.
			$this->_headers['Host'],
			$this->_headers['Referer']
		);
		
		// Prepare request options
		// +++ Auto set 'useragent'
		if (!$this->_options['useragent'] && $this->_headers['User-Agent']) {
			$this->_options['useragent'] = $this->_headers['User-Agent'];
		}
		
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
					if ('/' == $this->_baseUrl) {
						$this->_baseUrl = '';
					}
				} break;
			// Assets dir
				case 'assets_dir': {
					$assetsDir = trim($value);
				} break;
			// Proxy options
				case 'prx': {
					$prx = (array)$value;
				} break;
			}
		}
		// Set default assets dir?
		$this->_assetsDir = $assetsDir ?: $_SERVER['DOCUMENT_ROOT'];
		
		// Get, + proxy options
		$this->_prx = array_merge((array)$_GET['_prx'], (array)$prx);
		unset($_GET['_prx']);
		
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
		// Set options
		$this->setOptions($options);
		
		// Set origin url
		$this->_originUrlParts = Requests_Proxy_Common::parseUrl(
			Requests_Proxy_Common::serverUrl()
		);
		
		// Set proxy url
		// +++ Case: proxy assets?
		$assetsUrl = $this->getAssetsUrl();
		if ($assetsUrl && $url != $assetsUrl) {
			$url = $assetsUrl;
		}
		// +++ Set proxy url
		$this->setUrl($url);
		
		// Init session data
		/*@session_start();
		if (!isset($_SESSION[__CLASS__])) {
			$_SESSION[__CLASS__] = new stdClass();
		}
		$this->_session = $_SESSION[__CLASS__];*/
		
		// Init hooks utility
		$this->_hooksUtil = new Requests_Proxy_Hooks();
		
		// Initilize
		$this->_init();
	}
	
	/**
	 * Rewrite response body `html`.
	 * 
	 * @param $resBody string Response body `html`.
	 * @return mixed
	 */
	protected function _rewriteResponseHtml($resBody) {
		// Get params...
		// +++ Origin url parts
		$originUrlParts = $this->getOriginUrlParts();
		// +++ Url parts
		$urlParts = $this->getUrlParts();
		// +++ Baseurl
		$baseUrl = $this->getBaseUrl();
		
		// Rewrite response's body content
		// +++ 
		$pattern = '/<(\w+)[^>]*((src|href|action|url)\s*=\s*([\'"]?)([^\'"]+)([\'"]?))[^>]*\/?>/is';
		$resBody = preg_replace_callback($pattern, function($matches) use($urlParts, $originUrlParts, $baseUrl) {
			// 
			list($eleStr, $tagName, $attrStr, $attrName, $attrOpen, $attrValue, $attrClose) = $matches;
			// Case: 
			if (0 === strpos($attrValue, '//')) {
				$attrValue = "{$urlParts['protocol']}:{$attrValue}";
			}
			if ((0 === strpos($attrValue, $protocol = 'http://'))
				|| (0 === strpos($attrValue, $protocol = 'https://'))
			) {
				//
				$originHost = "{$protocol}{$originUrlParts['host']}";
				//
				$requestHost = "{$protocol}{$urlParts['host']}";
				//
				if ($originMatched = (0 === strpos($attrValue, $requestHost))) {
					$attrValue = str_replace($requestHost, $originHost, $attrValue);
				}
				// 
				if (!$originMatched) {
					$attrValue = "?_prx[assets]=" . urlencode($attrValue);
				}
			}
			// Case: 
			if (0 === strpos($attrValue, $protocol = '/')) {
				$attrValue = $baseUrl . $attrValue;
			}
			$eleStr = str_replace($attrStr, "{$attrName}={$attrOpen}{$attrValue}{$attrClose}", $eleStr);
			
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
	 * Rewrite response body `js`.
	 * 
	 * @param $resBody string Response body `js`.
	 * @return mixed
	 */
	protected function _rewriteResponseJs($resBody) {
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
	 * 
	 * @param string $url
	 * @param array $headers
	 * @param array $data
	 * @param array $options
	 * @return Requests_Response
	 * @return Requests_Proxy
	 */
	public function request($url = null, $headers = array(), $data = array(), $options = array())
	{
		// POST requests?
		if (Requests_Proxy_Common::isRequestPOST()) {
			$this->_res = $this->_req->post($url, $headers, array_merge($_POST, $data), $options);
		// 	GET requests
		} elseif (Requests_Proxy_Common::isRequestGET()) {
			$this->_res = $this->_req->get($url, $headers, $options);
		}
		//require_once "\Zend\Debug.php";
		//Zend_Debug::dump('===== Response body: ');
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
			, $headers['content-length']
			//, $headers['content-security-policy']
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
			foreach ((array)$hValues as $hValue) {
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
		//Zend_Debug::dump(headers_list());die();
		// Return
		return $this;
	}

	/**
	 * Send response body
	 * @return mixed
	 */
	public function response() {
		// Write response headers
		$this->_writeHeaders();
		// Write response body
		// +++ Format data
		$resBody = $this->_res->body;
		if (is_string($resBody)) {
			// Get response header `content-type`.
			$mimeType = $this->_res->headers['content-type'];
			// Case: content is 'html'
			if (Requests_Proxy_MimeType::isHtml($mimeType)) {
				$resBody = $this->_rewriteResponseHtml($resBody);
			}
			// Case: content is 'css'
			if (Requests_Proxy_MimeType::isCss($mimeType)) {
				$resBody = $this->_rewriteResponseCss($resBody);
			}
			// Case: content is 'csjs'
			if (Requests_Proxy_MimeType::isJs($mimeType)) {
				$resBody = $this->_rewriteResponseJs($resBody);
			}
		}
		// +++ Write
		echo $resBody;
		// Return
		return $this;
	}
	
	/**
	 * Main function, run proxy requests
	 * @return mixed
	 */
	public function run()
	{
		// Proxy requests, write headers, and then render response
		return $this
			->request()
			->response()
		;
	}
}