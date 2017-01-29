<?php
if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}

function checkToken($token)
{
	global $db, $errors;

	$token = $db->safesql($token);

	$db->query("SELECT * FROM " . PREFIX . "_api_token WHERE token = '$token' LIMIT 1", false);
	$resToken = $db->get_row();

	if ($resToken['expire'] > time())
		return $resToken;
	else {
		$errors = array('error' => 'token is exipred');
		return false;
	}
}

function checkAuth($request)
{
	global $db;

	if (md5($_REQUEST['android_passw']) != 'e62a660f2f1fa074dc69d4227f319e2b')
		die(json_encode(array('error' => 'Android passw auth failed')));

	$login = $db->safesql($request['login']);
	$password = $db->safesql($request['password']);
	$password = md5(md5($password));

	$row = $db->super_query('SELECT user_id FROM ' . USERPREFIX . "_users WHERE name = '$login' AND password = '$password' LIMIT 1");

	return intval($row['user_id']);
}