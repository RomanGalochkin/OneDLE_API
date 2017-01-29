<?php
if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}

$one_api = new ONE_API ();
if (!$config['version_id']) {
	include_once(ENGINE_DIR . '/data/config.php');
	date_default_timezone_set($config['date_adjust']);
}
$one_api->dle_config = $config;
if (!isset($db)) {
	include_once(ENGINE_DIR . '/classes/mysql.php');
	include_once(ENGINE_DIR . '/data/dbconfig.php');
}
$one_api->db = $db;

if ($_REQUEST['token'] && isset($_REQUEST['token'])) {
	$check = checkToken($_REQUEST['token']);

	if (is_array($check))
		if ($_REQUEST['action'])
			require_once('actions.php');
		else die(json_encode(array('error' => 'no action')));
	else die(json_encode($errors));
} else require_once('auth.php');