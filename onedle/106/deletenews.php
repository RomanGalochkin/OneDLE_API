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


if ($is_logged AND $user_group[$member_id['user_group']]['allow_all_edit']) {

	$id = intval($_REQUEST['id']);

	if ($id > 0) {

		$row = $db->super_query("SELECT id, autor, title, category FROM " . PREFIX . "_post WHERE id = '$id'");

		if ($row['id']) {

			$allow_list = explode(',', $user_group[$member_id['user_group']]['cat_add']);
			$category = explode(',', $row['category']);

			foreach ($category as $selected) {

				if ($allow_list[0] != "all" AND !in_array($selected, $allow_list) AND $member_id['user_group'] != 1) {
					die(json_encode(array('error' => 'Warning, you are not allowed to work with this category.')));
					die();
				}

			}

			$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql($member_id['name']) . "', '{$_TIME}', '{$_IP}', '26', '" . $db->safesql($row['title']) . "')");

			$db->query("DELETE FROM " . PREFIX . "_post WHERE id='{$row['id']}'");
			$db->query("DELETE FROM " . PREFIX . "_post_extras WHERE news_id='{$row['id']}'");
			$db->query("DELETE FROM " . PREFIX . "_comments WHERE post_id='{$row['id']}'");
			$db->query("DELETE FROM " . PREFIX . "_poll WHERE news_id='{$row['id']}'");
			$db->query("DELETE FROM " . PREFIX . "_poll_log WHERE news_id='{$row['id']}'");
			$db->query("DELETE FROM " . PREFIX . "_tags WHERE news_id = '{$row['id']}'");
			$db->query("DELETE FROM " . PREFIX . "_logs WHERE news_id = '{$row['id']}'");

			$row['autor'] = $db->safesql($row['autor']);
			$db->query("UPDATE " . USERPREFIX . "_users SET news_num=news_num-1 where name='{$row['autor']}'");


			$row_images = $db->super_query("SELECT images  FROM " . PREFIX . "_images WHERE news_id = '{$row['id']}'");

			$listimages = explode("|||", $row_images['images']);

			if ($row_images['images'] != "")
				foreach ($listimages as $dataimages) {

					$url_image = explode("/", $dataimages);

					if (count($url_image) == 2) {

						$folder_prefix = $url_image[0] . "/";
						$dataimages = $url_image[1];

					} else {

						$folder_prefix = "";
						$dataimages = $url_image[0];

					}

					@unlink(ROOT_DIR . "/uploads/posts/" . $folder_prefix . $dataimages);
					@unlink(ROOT_DIR . "/uploads/posts/" . $folder_prefix . "thumbs/" . $dataimages);
					@unlink(ROOT_DIR . "/uploads/posts/" . $folder_prefix . "medium/" . $dataimages);
				}

			$db->query("DELETE FROM " . PREFIX . "_images WHERE news_id = '{$row['id']}'");

			$db->query("SELECT id, onserver FROM " . PREFIX . "_files WHERE news_id = '{$row['id']}'");

			while ($row_files = $db->get_row()) {

				$url = explode("/", $row_files['onserver']);

				if (count($url) == 2) {

					$folder_prefix = $url[0] . "/";
					$file = $url[1];

				} else {

					$folder_prefix = "";
					$file = $url[0];

				}
				$file = totranslit($file, false);

				if (trim($file) == ".htaccess") continue;

				@unlink(ROOT_DIR . "/uploads/files/" . $folder_prefix . $file);

			}

			$db->query("DELETE FROM " . PREFIX . "_files WHERE news_id = '{$row['id']}'");

			clear_cache();

		} else {

			die(json_encode(array('error' => 'News not found.')));

		}

	} else {

		die(json_encode(array('error' => 'News id is wrong.')));
	}

	die(json_encode(array('result' => 'ok')));

} else {
	die(json_encode(array('error' => 'You are not allowed to edit news.')));
}