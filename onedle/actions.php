<?php

if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}

switch ($_REQUEST['action']) {
	case 'addnews':
	case 'editnews':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/addnews.php';
		break;

	case 'addcomment':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/addcomments.php';
		break;

	case 'delnews':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/deletenews.php';
		break;

	case 'addcat':
	case 'editcat':
	case 'delcat':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/categories.php';
		break;

	case 'delcomment':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/deletecomments.php';
		break;

	case 'editcomment':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/editcomments.php';
		break;

	case 'getuser':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/getuser.php';
		break;

	case 'getnews':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/getnews.php';
		break;

	case 'getonenews':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/getonenews.php';
		break;

	case 'getcomments':
		require_once ENGINE_DIR . '/onedle/' . $verDir . '/getcomments.php';
		break;

	case 'getlang':

		if (!$handle = opendir(ROOT_DIR . "/language")) {
			die(json_encode(array('error' => 'Folder ./language not readable or not exists')));
		}
		while (false !== ($file = readdir($handle))) {
			if (is_dir(ROOT_DIR . "/language/$file") and ($file != "." and $file != "..")) {
				$sys_con_langs_arr[$file] = $file;
			}
		}

		echo(json_encode($sys_con_langs_arr));

		closedir($handle);

		break;

	case 'getconfig':
		$user = $one_api->take_user_by_id($check['user_id']);
		if ($user['user_group'] == "1") {
			echo json_encode($config);
		} else {
			$allowKeys = array('version_id', 'home_title', 'http_home_url', 'charset', 'langs', 'allow_comments', 'flood_time');
			$tempArray = array();
			foreach ($config as $key => $value) {
				if (in_array($key, $allowKeys)) {
					$tempArray[$key] = $value;
				}
			}
			echo json_encode($tempArray);
		}
		break;

	case 'getcats':

		$cat_info = array();

		$db->query("SELECT * FROM " . PREFIX . "_category ORDER BY posi ASC");
		while ($row = $db->get_row()) {

			foreach ($row as $key => $value) {
				$row[$key] = stripslashes($value);
			}

			$cat_info[] = $row;


		}

		$db->free();

		echo json_encode($cat_info);
		break;

	case 'getgroups':

		$user_group = array();

		$db->query("SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC");

		while ($row = $db->get_row()) {

			foreach ($row as $key => $value) {
				$row[$key] = stripslashes($value);
			}

			$user_group[] = $row;

		}

		$db->free();

		echo json_encode($user_group);
		break;
}