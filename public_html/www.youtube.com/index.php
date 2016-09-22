<?php
// Define path to project directory
define('PROJECT_ROOT', realpath(dirname(__FILE__)) . '/../..');

// Define path to library directory
define('LIB_PATH', PROJECT_ROOT . '/library');

// Define application environment
define('APP_ENV', (getenv('APP_ENV') ? getenv('APP_ENV') : 'development'));

// Typically, you will also want to add your library directory
// to the include_path, particularly if it contains your ZF installed
set_include_path(implode(PATH_SEPARATOR, array(LIB_PATH, get_include_path())));

// --- Require files ---
require_once 'Requests/Proxy.php';
//
$requestsProxy = new Requests_Proxy(
	'https://www.youtube.com' . $_SERVER['REQUEST_URI'], 
	array(
		'base_url' => '/www.youtube.com',
	)
);
$requestsProxy->run();