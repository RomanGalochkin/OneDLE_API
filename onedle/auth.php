<?php
if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}

$user_id = checkAuth($_REQUEST);

if (!$user_id) {
	die(json_encode(array('error' => 'Auth failed')));
}

$num = $db->query("SELECT count(*) as cnt FROM " . PREFIX . "_api_token", false);
$num = $db->get_row();
if (!$num) {
	require_once(ENGINE_DIR . "/onedle/sql.php");
} elseif ($num['cnt'] >= 100000) {
	$db->query('TRUNCATE TABLE ' . PREFIX . '_api_token');
}

$token = md5(uniqid(mt_rand(), true));
$sqlTokenInsert = "INSERT INTO " . PREFIX . "_api_token VALUES ('', '$token', $user_id, " . (time() + 3600 * 24 * 7 * 30) . ")";
$db->query($sqlTokenInsert);
die(json_encode(array('token' => $token)));