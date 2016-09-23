<?php
/**
 *
 * @package Requests_Proxy
 */

/**
 *
 * @package Requests_Proxy
 */
class Requests_Proxy_MimeType {
	/**
	 * @var array An array of mime types
	 */
	protected static $_mimeTypes;
	/**
	 * Get list of mime types
	 * 
	 * @param $mimeType string Mime type
	 * @return array
	 */
	public static function getMimeTypes($mimeType = null) {
		// Format input
		if (!is_null($mimeType)) {
			$mimeType = explode(';', $mimeType);
			$mimeType = trim(array_shift($mimeType));
		}
		// 
		if (!self::$_mimeTypes) {
			self::$_mimeTypes = require_once dirname(__FILE__) . '/mime-types.data.php';
		}
		// Return
		return is_null($mimeType) ? self::$_mimeTypes : self::$_mimeTypes[$mimeType];
	}
	
	/**
	 * Check mime type by `extension`
	 * 
	 * @param $mimeType string Mime type string to check for
	 * @param $ext string File extension
	 * @return bool
	 */
	public static function isTypeOfExtension($mimeType, $ext) {
		$mimeType = self::getMimeTypes($mimeType);
		// Return
		return (".{$ext}" == $mimeType['ext']);
	}
	
	/**
	 * Is mime type 'html'?
	 * 
	 * @param $mimeType string Mime type string to check for
	 * @return bool
	 */
	public static function isHtml($mimeType) {
		return self::isTypeOfExtension($mimeType, 'html');
	}
	
	/**
	 * Is mime type 'js'?
	 * 
	 * @param $mimeType string Mime type string to check for
	 * @return bool
	 */
	public static function isJs($mimeType) {
		return self::isTypeOfExtension($mimeType, 'js');
	}
	
	/**
	 * Is mime type 'json'?
	 * 
	 * @param $mimeType string Mime type string to check for
	 * @return bool
	 */
	public static function isJson($mimeType) {
		return self::isTypeOfExtension($mimeType, 'json');
	}
	
	/**
	 * Is mime type 'css'?
	 * 
	 * @param $mimeType string Mime type string to check for
	 * @return bool
	 */
	public static function isCss($mimeType) {
		return self::isTypeOfExtension($mimeType, 'css');
	}
	
	/**
	 * Is mime type images?
	 * 
	 * @param $mimeType string Mime type string to check for
	 * @return bool
	 */
	public static function isImages($mimeType) {
		$mimeType = self::getMimeTypes($mimeType);
		// Return
		return (0 === strpos($mimeType['type'], 'image'));
	}
}