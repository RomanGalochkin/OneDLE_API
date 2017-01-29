<?php
/*
OneDLE External API v.1.0.1
Copyright 2015 (c) Admin-Club.ru
E-mail: support@admin-club.ru
site: http://admin-club.ru
ICQ: 709056
*/

@error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL ^ E_WARNING ^ E_NOTICE);

define('DATALIFEENGINE', true);
define('ROOT_DIR', substr(dirname(__FILE__), 0, -7));

define('ENGINE_DIR', dirname(__FILE__) . "/");
include ENGINE_DIR . '/data/config.php';

if (!$config['version_id']) {
	if (file_exists(ROOT_DIR . '/install.php') AND !file_exists(ENGINE_DIR . '/data/config.php')) {
		header("Location: " . str_replace("index.php", "install.php", $_SERVER['PHP_SELF']));
		die ("Datalife Engine not installed. Please run install.php");
	} else {
		die ("Datalife Engine not installed. Please run install.php");
	}
}


date_default_timezone_set($config['date_adjust']);

$result = array();
$errors = array('error' => '');

require_once ENGINE_DIR . '/classes/mysql.php';
require_once ENGINE_DIR . '/data/dbconfig.php';
require_once ENGINE_DIR . '/modules/functions.php';
require_once ENGINE_DIR . '/onedle/functions.php';


if ($config['charset'] == "windows-1251")
	$db->query('SET CHARACTER SET utf8');


if ($config['version_id'] == '10.4')
	$verDir = '104';
elseif ($config['version_id'] == '10.5')
	$verDir = '105';
elseif ($config['version_id'] == '10.6')
	$verDir = '106';
else
	die('OneDLE supports only 10.4, 10.5 and 10.6 DLE versions.');

require_once(ENGINE_DIR . '/api/api.class.php');
require_once(ENGINE_DIR . '/onedle/onedle.class.php');
require_once(ENGINE_DIR . "/onedle/api.php");