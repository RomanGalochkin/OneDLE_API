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

include_once ENGINE_DIR . '/classes/parse.class.php';
$parse = new ParseFilter(Array(), Array(), 1, 1);

if ($config['max_moderation'] AND !$user_group[$member_id['user_group']]['moderation']) {

	$stats_approve = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_post WHERE approve != '1'");
	$stats_approve = $stats_approve['count'];

	if ($stats_approve >= $config['max_moderation'])
		die(json_encode(array('error' => 'Very big count of news for moderation.')));

}

if ($is_logged AND $config['news_restricted'] AND (($_TIME - $member_id['reg_date']) < ($config['news_restricted'] * 86400))) {
	die(json_encode(array('error' => 'Restricted.')));
}

if ($member_id['restricted'] and $member_id['restricted_days'] and $member_id['restricted_date'] < $_TIME) {

	$member_id['restricted'] = 0;
	$db->query("UPDATE LOW_PRIORITY " . USERPREFIX . "_users SET restricted='0', restricted_days='0', restricted_date='' WHERE user_id='{$member_id['user_id']}'");

}

if ($member_id['restricted'] == 1 or $member_id['restricted'] == 3) {

	if ($member_id['restricted_days']) {

		die(json_encode(array('error' => 'Restricted days.')));

	} else {

		die(json_encode(array('error' => 'Restricted days (2).')));

	}

}


if (!$user_group[$member_id['user_group']]['allow_adds'])
	die(json_encode(array('error' => 'Permissions to adding news deny.')));


$allow_comm = 1;

if ($user_group[$member_id['user_group']]['allow_main']) $allow_main = 1;
else $allow_main = 0;

$approve = 1;
$allow_rating = 1;

if ($user_group[$member_id['user_group']]['allow_fixed']) $news_fixed = 1;
else $news_fixed = 0;


$catlist[] = intval($_REQUEST['catlist']);

if ($catlist[0] == 0 || !isset($cat_info[$catlist[0]]))
	die(json_encode(array('error' => 'Category not selected.')));


$category_list = $db->safesql($catlist[0]);

$add_vote = 0;

if (!$user_group[$member_id['user_group']]['moderation']) {
	$approve = 0;
	$allow_comm = 1;
	$allow_main = 1;
	$allow_rating = 1;
	$news_fixed = 0;
}


if ($approve) $modResult = 'no';
else $modResult = 'yes';


$allow_list = explode(',', $user_group[$member_id['user_group']]['cat_add']);

if ($user_group[$member_id['user_group']]['moderation']) {
	foreach ($catlist as $selected) {
		if ($allow_list[0] != "all" and !in_array($selected, $allow_list) and $member_id['user_group'] != "1") {
			$approve = 0;
		}
	}
}


$allow_list = explode(',', $user_group[$member_id['user_group']]['cat_allow_addnews']);

if ($allow_list[0] != "all") {
	foreach ($catlist as $selected) {
		if (!in_array($selected, $allow_list) AND $member_id['user_group'] != "1") {
			die(json_encode(array('error' => 'This category news not allowed for you.')));
		}
	}
}


$_REQUEST['short_story'] = strip_tags($_REQUEST['short_story']);
$_REQUEST['full_story'] = strip_tags($_REQUEST['full_story']);

$full_story = $db->safesql($parse->BB_Parse($parse->process($_REQUEST['full_story']), false));
$short_story = $db->safesql($parse->BB_Parse($parse->process($_REQUEST['short_story']), false));


if ($short_story == '' || dle_strlen($short_story, $config['charset']) < 10)
	die(json_encode(array('error' => 'Short story is empty or is too small.')));


$allow_br = 1;


if ($parse->not_allowed_text) {
	die(json_encode(array('error' => 'Not allowed text.')));
}


$title = $db->safesql($parse->process(trim(strip_tags($_REQUEST['title']))));


$parse = new ParseFilter(Array(), Array(), 1, 1);


$alt_name = totranslit(stripslashes($title), true, false);


if ($title == "" or !$title) die(json_encode(array('error' => 'Title is empty.')));
if (dle_strlen($title, $config['charset']) > 200) die(json_encode(array('error' => 'Title is too large.')));

if ($config['create_catalog']) $catalog_url = $db->safesql(dle_substr(htmlspecialchars(strip_tags(stripslashes(trim($title))), ENT_QUOTES, $config['charset']), 0, 1, $config['charset'])); else $catalog_url = "";


//if( $user_group[$member_id['user_group']]['flood_news'] ) {
if (flooder($member_id['name'], $user_group[$member_id['user_group']]['flood_news'])) {
	die(json_encode(array('error' => 'Flood.')));
}
//}

if ($user_group[$member_id['user_group']]['max_day_news']) {
	$row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_post WHERE date >= '" . date("Y-m-d", $_TIME) . "' AND date < '" . date("Y-m-d", $_TIME) . "' + INTERVAL 24 HOUR AND autor = '{$member_id['name']}'");
	if ($row['count'] >= $user_group[$member_id['user_group']]['max_day_news']) {
		die(json_encode(array('error' => 'Max day news.')));
	}
}


$id = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;
$found = false;

if ($id) {
	$row = $db->super_query("SELECT id, autor, tags FROM " . PREFIX . "_post where id = '$id'");
	if ($id == $row['id'] and ($member_id['name'] == $row['autor'] or $user_group[$member_id['user_group']]['allow_all_edit'])) {
		$found = true;
	} else
		die(json_encode(array('error' => 'You are not author or not allowed to edit this news.')));
}

if ($found) {

	$db->query("UPDATE " . PREFIX . "_post set title='$title', short_story='$short_story', full_story='$full_story', category='$category_list', alt_name='$alt_name', allow_comm='$allow_comm', approve='$approve', allow_main='$allow_main', fixed='$news_fixed', allow_br='$allow_br' WHERE id='$id'");
	$db->query("UPDATE " . PREFIX . "_post_extras SET allow_rate='{$allow_rating}', votes='{$add_vote}' WHERE news_id='{$id}'");

	$oneDleResult = json_encode(array('result' => 'updated'));

} else {

	$added_time = time();
	$thistime = date("Y-m-d H:i:s", $added_time);

	$db->query("INSERT INTO " . PREFIX . "_post (date, autor, short_story, full_story, xfields, title, keywords, category, alt_name, allow_comm, approve, allow_main, fixed, allow_br, symbol, tags) values ('$thistime', '{$member_id['name']}', '$short_story', '$full_story', '', '$title', '', '$category_list', '$alt_name', '$allow_comm', '$approve', '$allow_main', '$news_fixed', '$allow_br', '$catalog_url', '')");

	$row['id'] = $db->insert_id();
	$resultId = $row['id'];

	$db->query("INSERT INTO " . PREFIX . "_post_extras (news_id, allow_rate, votes, user_id) VALUES('{$row['id']}', '{$allow_rating}', '{$add_vote}','{$member_id['user_id']}')");

	$member_id['name'] = $db->safesql($member_id['name']);

	$db->query("UPDATE " . PREFIX . "_images set news_id='{$row['id']}' where author = '{$member_id['name']}' AND news_id = '0'");
	$db->query("UPDATE " . PREFIX . "_files set news_id='{$row['id']}' where author = '{$member_id['name']}' AND news_id = '0'");
	$db->query("UPDATE " . USERPREFIX . "_users set news_num=news_num+1 where user_id='{$member_id['user_id']}'");

	//if( $user_group[$member_id['user_group']]['flood_news'] ) {
	$db->query("INSERT INTO " . PREFIX . "_flood (id, ip, flag) values ('$_TIME', '{$member_id['name']}', '1')");
	//}


	if (!$approve and $config['mail_news']) {

		include_once ENGINE_DIR . '/classes/mail.class.php';

		$row = $db->super_query("SELECT * FROM " . PREFIX . "_email WHERE name='new_news' LIMIT 0,1");
		$mail = new dle_mail($config, $row['use_html']);

		$row['template'] = stripslashes($row['template']);
		$row['template'] = str_replace("{%username%}", $member_id['name'], $row['template']);
		$row['template'] = str_replace("{%date%}", langdate("j F Y H:i", $added_time, true), $row['template']);
		$row['template'] = str_replace("{%title%}", stripslashes(stripslashes($title)), $row['template']);

		$category_list = explode(",", $category_list);
		$my_cat = array();

		foreach ($category_list as $element) {

			$my_cat[] = $cat_info[$element]['name'];

		}

		$my_cat = stripslashes(implode(', ', $my_cat));

		$row['template'] = str_replace("{%category%}", $my_cat, $row['template']);

		$mail->send($config['admin_mail'], $lang['mail_news'], $row['template']);

	}

	$oneDleResult = json_encode(array('result' => 'ok', 'news_id' => $resultId, 'moderation' => $modResult));

}

if ($approve) {
	clear_cache(array('news_', 'related_', 'tagscloud_', 'archives_', 'calendar_', 'topnews_', 'rss'));
}
die($oneDleResult);