<?php
if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}

$member_id = $one_api->take_user_by_id($check['user_id']);

#--------------

$cat_info = get_vars("category");

if (!is_array($cat_info)) {
	$cat_info = array();

	$db->query("SELECT * FROM " . PREFIX . "_category ORDER BY posi ASC");
	while ($row = $db->get_row()) {

		$cat_info[$row['id']] = array();

		foreach ($row as $key => $value) {
			$cat_info[$row['id']][$key] = stripslashes($value);
		}

	}
	set_vars("category", $cat_info);
	$db->free();
}

$user_group = get_vars("usergroup");

if (!is_array($user_group)) {
	$user_group = array();

	$db->query("SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC");

	while ($row = $db->get_row()) {

		$user_group[$row['id']] = array();

		foreach ($row as $key => $value) {
			$user_group[$row['id']][$key] = stripslashes($value);
		}

	}
	set_vars("usergroup", $user_group);
	$db->free();
}

$is_logged = 1;

$_TIME = time();
$_IP = get_ip();


#--------------


$id = intval($_REQUEST['id']);

if (!$id)
	die(json_encode(array('error' => 'ID comment is wrong.')));

$row = $db->super_query("SELECT * FROM " . PREFIX . "_comments where id = '$id'");

$author = $row['autor'];
$is_reg = $row['is_register'];
$post_id = $row['post_id'];
$approve = $row['approve'];

if ($row['id']) {

	$have_perm = false;
	$row['date'] = strtotime($row['date']);

	#!
	if ((
			$member_id['user_id'] == $row['user_id']
			AND $row['is_register']
			AND $user_group[$member_id['user_group']]['allow_delc']
		)
		OR $member_id['user_group'] == '1'
		OR $user_group[$member_id['user_group']]['del_allc']
	) $have_perm = true;

	if ($user_group[$member_id['user_group']]['edit_limit'] AND (($row['date'] + ($user_group[$member_id['user_group']]['edit_limit'] * 60)) < $_TIME)) {
		$have_perm = false;
	}

	if ($have_perm) {
		$db->query("DELETE FROM " . PREFIX . "_comments WHERE id = '$id'");
		$db->query("DELETE FROM " . PREFIX . "_comment_rating_log WHERE c_id = '$id'");

		// обновление количества комментариев у юзера 
		if ($is_reg) {
			$author = $db->safesql($author);
			$db->query("UPDATE " . USERPREFIX . "_users set comm_num=comm_num-1 WHERE name ='$author'");
		}

		// обновление количества комментариев в новостях 
		if ($approve) $db->query("UPDATE " . PREFIX . "_post SET comm_num=comm_num-1 WHERE id='{$post_id}'");

		if ($config['allow_alt_url'] AND !$config['seo_type']) $cprefix = "full_"; else $cprefix = "full_" . $post_id;

		clear_cache(array('news_', 'rss', 'comm_' . $post_id, $cprefix));

		die(json_encode(array('result' => 'ok', 'comment_id' => $row['id'])));

	} else die(json_encode(array('error' => 'You do not have access to edit this comment.')));

} else die(json_encode(array('error' => 'Comment not found.')));