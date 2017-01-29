<?php
if (!defined('DATALIFEENGINE') OR !$config['allow_comments']) {
	die("Hacking attempt!");
}


$id = $_REQUEST['user_id'] == 'me' ? $check['user_id'] : intval($_REQUEST['user_id']);
$apiUser = $one_api->take_user_by_id($check['user_id']);

if (!$id || $id == "")
	die(json_encode(array('error' => 'Request has no key ID')));

$allowColumns = array('email', 'password', 'name', 'user_id', 'news_num',
	'comm_num', 'user_group', 'lastdate', 'reg_date', 'banned',
	'allow_mail', 'info', 'signature', 'foto', 'fullname', 'land',
	'country', 'city', 'icq', 'favorites', 'pm_all', 'pm_unread',
	'time_limit', 'xfields', 'allowed_ip', 'hash', 'useragent',
	'logged_ip', 'logged_proxy', 'restricted');


if ($apiUser['user_group'] != "1")
	$allowColumns = array('email', 'name', 'user_id', 'user_group', 'lastdate',
		'reg_date', 'info', 'signature', 'foto',
		'fullname', 'land', 'country', 'city', 'icq');


$columns = $_REQUEST['columns'];
$ex_columns = explode(',', $columns);

foreach ($ex_columns as $value) {
	if (!in_array($value, $allowColumns))
		die(json_encode(array('error' => 'Columns not allowed')));
}

if ($columns)
	$user = $one_api->take_user_by_id($id, $columns);
else
	$user = $one_api->take_user_by_id($id);

echo json_encode($user);