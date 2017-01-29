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


require_once ENGINE_DIR . '/classes/parse.class.php';

$parse = new ParseFilter();
$parse->safe_mode = true;

$id = intval($_REQUEST['id']);

if (!$id)
	die(json_encode(array('error' => 'ID comment is wrong.')));


$parse->allow_url = $user_group[$member_id['user_group']]['allow_url'];
$parse->allow_image = $user_group[$member_id['user_group']]['allow_image'];


$row = $db->super_query("SELECT id, post_id, date, autor, text, is_register, approve FROM " . PREFIX . "_comments where id = '$id'");

if ($id != $row['id'])
	die(json_encode(array('error' => 'ID comment is wrong.')));

$have_perm = 0;
$row['date'] = strtotime($row['date']);

if ($is_logged AND (($member_id['name'] == $row['autor'] AND $row['is_register'] AND $user_group[$member_id['user_group']]['allow_editc']) OR $user_group[$member_id['user_group']]['edit_allc'] OR $user_group[$member_id['user_group']]['admin_comments'])) {
	$have_perm = 1;
}

if ($user_group[$member_id['user_group']]['edit_limit'] AND (($row['date'] + ($user_group[$member_id['user_group']]['edit_limit'] * 60)) < $_TIME)) {
	$have_perm = 0;
}

if (!$have_perm)
	die(json_encode(array('error' => 'You do not have access to edit this comment.')));

$use_html = false;

//$comm_txt = trim( $parse->BB_Parse( $parse->process( convert_unicode( $_REQUEST['comm_txt'], $config['charset'] ) ), $use_html ) );

$comm_txt = trim($parse->BB_Parse($parse->process($_REQUEST['comm_txt']), $use_html));

if ($parse->not_allowed_tags) {
	die(json_encode(array('error' => 'Comment consist of not allowed tags.')));
}

if ($parse->not_allowed_text) {
	die(json_encode(array('error' => 'Comment consist of not allowed text.')));
}

if (dle_strlen($comm_txt, $config['charset']) > $config['comments_maxlen']) {

	die(json_encode(array('error' => 'Comment is too length.')));

}

if ($comm_txt == "") {

	die(json_encode(array('error' => 'Comment is empty.')));

}

if (intval($config['comments_minlen']) AND dle_strlen($comm_txt, $config['charset']) < $config['comments_minlen']) {

	die(json_encode(array('error' => 'Comment is too small.')));

}

if (intval($config['auto_wrap'])) {

	$comm_txt = preg_split('((>)|(<))', $comm_txt, -1, PREG_SPLIT_DELIM_CAPTURE);
	$n = count($comm_txt);

	for ($i = 0; $i < $n; $i++) {
		if ($comm_txt[$i] == "<") {
			$i++;
			continue;
		}

		if (preg_match("#([^\s\n\r]{" . intval($config['auto_wrap']) . "})#{$utf_pref}i", $comm_txt[$i])) {

			$comm_txt[$i] = preg_replace("#([^\s\n\r]{" . intval($config['auto_wrap'] - 1) . "})#{$utf_pref}i", "\\1<br />", $comm_txt[$i]);

		}

	}

	$comm_txt = join("", $comm_txt);

}

$comm_update = $db->safesql($comm_txt);

$db->query("UPDATE " . PREFIX . "_comments SET text='$comm_update', approve='1' WHERE id = '$id'");

if (!$row['approve']) $db->query("UPDATE " . PREFIX . "_post SET comm_num=comm_num+1 WHERE id='{$row['post_id']}'");

$comm_txt = str_replace("[hide]", "", str_replace("[/hide]", "", $comm_txt));
$buffer = stripslashes($comm_txt);

if (!$row['approve']) {
	if ($config['allow_alt_url'] AND !$config['seo_type']) clear_cache('full_'); else clear_cache('full_' . $row['post_id']);
}

clear_cache('comm_' . $row['post_id']);

$db->close();

die(json_encode(array('result' => 'ok', 'comment' => $buffer)));