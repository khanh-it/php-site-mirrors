<?php
/**
 * Helpers class
 *
 * @package Requests_Proxy
 * @subpackage Utilities
 */

/**
 * Helpers class
 *
 * @package Requests_Proxy
 * @subpackage Utilities
 */
class Requests_Proxy_Common {
	/**
	 * Get full server url
	 * 
	 * @param $useQueryString bool Use query string?
	 * @param $useForwardedHost bool Use 'X_FORWARDED..' flag?
	 * @return string
	 */
	public static function serverUrl($useQueryString = true, $useForwardedHost = true) {
		$ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
		$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $_SERVER['SERVER_PORT'];
		$port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
		$host = ($useForwardedHost && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
		$host = isset($host) ? $host : $_SERVER['SERVER_NAME'] . $port;
		$url = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
		// Use query string?
		if (!$useQueryString) {
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
	 * @return array
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
			$urlParts['domain'] = explode('.', $urlParts['host']);
			if (count($urlParts['domain']) > 2) {
				array_shift($urlParts['domain']);
			}
			$urlParts['domain'] = implode('.', $urlParts['domain']);
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
	 * Is POST requests?
	 * @return bool
	 */
	public static function isRequestPOST() {
		return 'POST' == $_SERVER['REQUEST_METHOD'];
	}
	/**
	 * Is GET requests?
	 * @return bool
	 */
	public static function isRequestGET() {
		return 'GET' == $_SERVER['REQUEST_METHOD'];
	}
}