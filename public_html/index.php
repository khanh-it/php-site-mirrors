<?php
// Define path to project directory
define('PROJECT_ROOT', realpath(dirname(__FILE__)) . '/..');

// Define path to library directory
define('LIB_PATH', PROJECT_ROOT . '/library');

// Define application environment
define('APP_ENV', (getenv('APP_ENV') ? getenv('APP_ENV') : 'development'));

// Typically, you will also want to add your library directory
// to the include_path, particularly if it contains your ZF installed
set_include_path(implode(PATH_SEPARATOR, array(LIB_PATH, get_include_path())));

// List of websites
// Noted: no trailing forward
$urls = array(
	/* 0: */ array(
		'https://www.google.com.vn', array()
	),
	/* 1: */ array(
		'https://www.youtube.com', array()
	),
	/* 2: */ array(
		'https://github.com', array()
	),
	/* 3: */ array(
		'http://sea.ign.com', array()
	),
	/* 4: */ array(
		'http://gamespot.com', array()
	)
);
$use = 2;

// --- Require files ---
require_once 'Requests/Proxy.php';
$requestsProxy = new Requests_Proxy(
	"{$urls[$use][0]}{$_SERVER['REQUEST_URI']}", $urls[$use][1]
);
$requestsProxy->run();