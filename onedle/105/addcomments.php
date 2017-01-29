<?php
if (!defined('DATALIFEENGINE') OR !$config['allow_comments']) {
	die("Hacking attempt!");
}

$member_id = $one_api->take_user_by_id($check['user_id']);

#--------------

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

#--------------

require_once ENGINE_DIR . '/classes/parse.class.php';


$parse = new ParseFilter();
$parse->safe_mode = true;
$parse->allow_url = 0;
$parse->allow_image = 0;

$_TIME = time();
$_IP = get_ip();


$post_id = intval($_REQUEST['post_id']);
$stop = array();

$name = $db->safesql($member_id['name']);
$mail = $db->safesql($member_id['email']);


if ($user_group[$member_id['user_group']]['spamfilter']) {

	$row = $db->super_query("SELECT * FROM " . PREFIX . "_spam_log WHERE ip = '{$_IP}'");

	if (!$row['id'] OR !$row['email']) {

		include_once ENGINE_DIR . '/classes/stopspam.class.php';
		$sfs = new StopSpam($config['spam_api_key'], $user_group[$member_id['user_group']]['spamfilter']);
		$args = array('ip' => $_IP, 'email' => $mail);

		if ($sfs->is_spammer($args)) {

			if (!$row['id']) {
				$db->query("INSERT INTO " . PREFIX . "_spam_log (ip, is_spammer, email, date) VALUES ('{$_IP}','1', '{$mail}', '{$_TIME}')");
			} else {
				$db->query("UPDATE " . PREFIX . "_spam_log SET is_spammer='1', email='{$mail}' WHERE id='{$row['id']}'");
			}

			die(json_encode(array('error' => 'Spamer 1')));

		} else {
			if (!$row['id']) {
				$db->query("INSERT INTO " . PREFIX . "_spam_log (ip, is_spammer, email, date) VALUES ('{$_IP}','0', '{$mail}', '{$_TIME}')");
			} else {
				$db->query("UPDATE " . PREFIX . "_spam_log SET email='{$mail}' WHERE id='{$row['id']}'");
			}
		}

	} else {

		if ($row['is_spammer']) {

			die(json_encode(array('error' => 'Spamer 2')));

		}

	}

}

if ($is_logged AND $config['comments_restricted'] AND (($_TIME - $member_id['reg_date']) < ($config['comments_restricted'] * 86400))) {
	die(json_encode(array('error' => 'Comment restricted')));
}


$comments = $parse->BB_Parse($parse->process(trim($_REQUEST['comments'])), false);


if (intval($config['comments_minlen']) AND dle_strlen(str_replace(" ", "", strip_tags(trim($comments))), $config['charset']) < $config['comments_minlen']) {

	die(json_encode(array('error' => 'Comment is too small.')));

}


if ($user_group[$member_id['user_group']]['max_comment_day']) {

	$this_time = $_TIME - 86400;
	$db->query("DELETE FROM " . PREFIX . "_sendlog WHERE date < '$this_time' AND flag='3'");

	if (!$is_logged) $check_user = $_IP; else $check_user = $db->safesql($member_id['name']);

	$row = $db->super_query("SELECT COUNT(*) as count FROM " . PREFIX . "_sendlog WHERE user = '{$check_user}' AND flag='3'");

	if ($row['count'] >= $user_group[$member_id['user_group']]['max_comment_day']) {

		die(json_encode(array('error' => 'Max commant in a day')));;

	}

}


if ($is_logged and ($member_id['restricted'] == 2 or $member_id['restricted'] == 3)) {

	die(json_encode(array('error' => 'Comment restricted 2')));

}


if (!$post_id) {
	die(json_encode(array('error' => 'Post_id not found.')));
}
if (dle_strlen($comments, $config['charset']) > $config['comments_maxlen']) {
	die(json_encode(array('error' => 'Comment is too large.')));
}


if ($comments == '') {
	die(json_encode(array('error' => 'Comment is empty.')));
}

if ($parse->not_allowed_tags) {
	die(json_encode(array('error' => 'Comment consists tags.')));
}

if ($parse->not_allowed_text) {
	die(json_encode(array('error' => 'Comment consists not allowed text.')));
}

// �������� ������ �� �����
if ($member_id['user_group'] > 2 and intval($config['flood_time']) and !$CN_HALT) {
	if (flooder($_IP) == TRUE) {
		die(json_encode(array('error' => 'Comment flood.')));
		$CN_HALT = TRUE;
	}
}

// �������� �� ������������ �������
$row = $db->super_query("SELECT id, date, allow_comm, approve, access from " . PREFIX . "_post LEFT JOIN " . PREFIX . "_post_extras ON (" . PREFIX . "_post.id=" . PREFIX . "_post_extras.news_id) WHERE id='$post_id'");
$options = news_permission($row['access']);

if ((!$user_group[$member_id['user_group']]['allow_addc'] and $options[$member_id['user_group']] != 2) or $options[$member_id['user_group']] == 1) die("Hacking attempt!");

if (!$row['id'] or !$row['allow_comm'] or !$row['approve']) {
	die(json_encode(array('error' => 'News not found.')));;
}

if ($config['max_comments_days']) {
	$row['date'] = strtotime($row['date']);

	if ($row['date'] < ($_TIME - ($config['max_comments_days'] * 3600 * 24))) {
		die(json_encode(array('error' => 'Error with max comments in a day.')));;
	}
}


//* ����������� ������� ����
if (intval($config['auto_wrap'])) {

	if ($config['charset'] == "utf-8") $utf_pref = "u"; else $utf_pref = "";

	$comments = preg_split('((>)|(<))', $comments, -1, PREG_SPLIT_DELIM_CAPTURE);
	$n = count($comments);

	for ($i = 0; $i < $n; $i++) {
		if ($comments[$i] == "<") {
			$i++;
			continue;
		}

		if (preg_match("#([^\s\n\r]{" . intval($config['auto_wrap']) . "})#{$utf_pref}i", $comments[$i])) {

			$comments[$i] = preg_replace("#([^\s\n\r]{" . intval($config['auto_wrap'] - 1) . "})#{$utf_pref}i", "\\1<br />", $comments[$i]);

		}

	}

	$comments = join("", $comments);

}

$time = date("Y-m-d H:i:s", $_TIME);
$where_approve = 1;


// ���������� �����������
if ($CN_HALT) {

	die(json_encode(array('error' => 'Error!')));

} else {

	$update_comments = false;

	if ($config['allow_combine']) {

		$row = $db->super_query("SELECT id, post_id, user_id, date, text, ip, is_register, approve FROM " . PREFIX . "_comments WHERE post_id = '$post_id' ORDER BY id DESC LIMIT 0,1");

		if ($row['id']) {

			if ($row['user_id'] == $member_id['user_id'] and $row['is_register']) $update_comments = true;
			elseif ($row['ip'] == $_IP and !$row['is_register'] and !$is_logged) $update_comments = true;

			$row['date'] = strtotime($row['date']);

			if (date("Y-m-d", $row['date']) != date("Y-m-d", $_TIME)) $update_comments = false;

			if ($user_group[$member_id['user_group']]['edit_limit'] AND (($row['date'] + ($user_group[$member_id['user_group']]['edit_limit'] * 60)) < $_TIME)) $update_comments = false;

			if (((dle_strlen($row['text'], $config['charset']) + dle_strlen($comments, $config['charset'])) > $config['comments_maxlen']) and $update_comments) {
				$update_comments = false;
				die(json_encode(array('error' => 'Max comment length is too large.')));
			}
		}
	}


	if (!$CN_HALT) {

		if ($config['allow_cmod'] and $user_group[$member_id['user_group']]['allow_modc']) {

			if ($update_comments) {
				if ($row['approve']) $update_comments = false;
			}

			$where_approve = 0;
			die(json_encode(array('error' => 'You can not edit comments.')));

		}

		if ($update_comments) {

			$comments = $db->safesql($row['text']) . "<br /><br />" . $db->safesql($comments);
			$db->query("UPDATE " . PREFIX . "_comments set date='$time', text='{$comments}', approve='{$where_approve}' WHERE id='{$row['id']}'");

		} else {

			$comments = $db->safesql($comments);

			if ($is_logged) $db->query("INSERT INTO " . PREFIX . "_comments (post_id, user_id, date, autor, email, text, ip, is_register, approve) values ('$post_id', '$member_id[user_id]', '$time', '$name', '$mail', '$comments', '$_IP', '1', '$where_approve')");
			else $db->query("INSERT INTO " . PREFIX . "_comments (post_id, date, autor, email, text, ip, is_register, approve) values ('$post_id', '$time', '$name', '$mail', '$comments', '$_IP', '0', '$where_approve')");

			// ���������� ���������� ������������ � �������� 
			if ($where_approve) $db->query("UPDATE " . PREFIX . "_post SET comm_num=comm_num+1 WHERE id='{$post_id}'");

			// ���������� ���������� ������������ � ����� 
			if ($is_logged) {
				$db->query("UPDATE " . USERPREFIX . "_users SET comm_num=comm_num+1 WHERE user_id ='$member_id[user_id]'");
			}
		}

		// ������ �� �����
		if ($config['flood_time']) {
			$db->query("INSERT INTO " . PREFIX . "_flood (id, ip) values ('$_TIME', '$_IP')");
		}

		if ($user_group[$member_id['user_group']]['max_comment_day']) {
			$db->query("INSERT INTO " . PREFIX . "_sendlog (user, date, flag) values ('{$check_user}', '{$_TIME}', '3')");
		}

		if ($config['mail_comments'] OR $config['allow_subscribe']) {

			include_once ENGINE_DIR . '/classes/mail.class.php';

			$row = $db->super_query("SELECT id, short_story, title, date, alt_name, category FROM " . PREFIX . "_post WHERE id = '{$post_id}'");

			$row['date'] = strtotime($row['date']);
			$row['category'] = intval($row['category']);

			if ($config['allow_alt_url']) {

				if ($config['seo_type'] == 1 OR $config['seo_type'] == 2) {

					if ($row['category'] and $config['seo_type'] == 2) {

						$full_link = $config['http_home_url'] . get_url($row['category']) . "/" . $row['id'] . "-" . $row['alt_name'] . ".html";

					} else {

						$full_link = $config['http_home_url'] . $row['id'] . "-" . $row['alt_name'] . ".html";

					}

				} else {

					$full_link = $config['http_home_url'] . date('Y/m/d/', $row['date']) . $row['alt_name'] . ".html";
				}

			} else {

				$full_link = $config['http_home_url'] . "index.php?newsid=" . $row['id'];

			}

			$title = stripslashes($row['title']);

			$row = $db->super_query("SELECT * FROM " . PREFIX . "_email WHERE name='comments' LIMIT 0,1");
			$mail = new dle_mail($config, $row['use_html']);

			$row['template'] = stripslashes($row['template']);
			$row['template'] = str_replace("{%username%}", $name, $row['template']);
			$row['template'] = str_replace("{%date%}", langdate("j F Y H:i", $_TIME, true), $row['template']);
			$row['template'] = str_replace("{%link%}", $full_link, $row['template']);
			$row['template'] = str_replace("{%title%}", $title, $row['template']);

			$body = str_replace('\n', "", $comments);
			$body = str_replace('\r', "", $body);

			$body = stripslashes(stripslashes($body));
			$body = str_replace("<br />", "\n", $body);
			$body = strip_tags($body);

			if ($row['use_html']) {
				$body = str_replace("\n", "<br />", $body);
			}

			$row['template'] = str_replace("{%text%}", $body, $row['template']);

		}

		if ($config['mail_comments']) {

			$body = str_replace("{%ip%}", $_IP, $row['template']);
			$body = str_replace("{%username_to%}", $lang['admin'], $body);
			$body = str_replace("{%unsubscribe%}", "--", $body);
			$mail->send($config['admin_mail'], $lang['mail_comments'], $body);

		}


		if ($config['allow_subscribe'] AND $where_approve) {

			$row['template'] = str_replace("{%ip%}", "--", $row['template']);
			$found_subscribe = false;

			$db->query("SELECT user_id, name, email, hash FROM " . PREFIX . "_subscribe WHERE news_id='{$post_id}'");

			while ($rec = $db->get_row()) {
				if ($rec['user_id'] != $member_id['user_id']) {

					$body = str_replace("{%username_to%}", $rec['name'], $row['template']);
					$body = str_replace("{%unsubscribe%}", $config['http_home_url'] . "index.php?do=unsubscribe&post_id=" . $post_id . "&user_id=" . $rec['user_id'] . "&hash=" . $rec['hash'], $body);
					$mail->send($rec['email'], $lang['mail_comments'], $body);

				} else {

					$found_subscribe = true;

				}

			}

			$db->free();

			if ($_POST['allow_subscribe'] AND $user_group[$member_id['user_group']]['allow_subscribe'] AND !$found_subscribe) {

				if (function_exists('openssl_random_pseudo_bytes')) {

					$stronghash = md5(openssl_random_pseudo_bytes(15));

				} else $stronghash = md5(uniqid(mt_rand(), TRUE));

				$salt = str_shuffle($stronghash);
				$s_hash = "";

				for ($i = 0; $i < 10; $i++) {
					$s_hash .= $salt{mt_rand(0, 31)};
				}

				$s_hash = md5($s_hash);

				$db->query("INSERT INTO " . PREFIX . "_subscribe (user_id, name, email, news_id, hash) values ('{$member_id['user_id']}', '{$member_id['name']}', '{$member_id['email']}', '{$post_id}', '{$s_hash}')");

			}

		}

		if ($config['allow_alt_url'] AND !$config['seo_type']) $cprefix = "full_"; else $cprefix = "full_" . $post_id;

		clear_cache(array('news_', 'rss', 'comm_' . $post_id, $cprefix));

		if (!$CN_HALT) {
			die(json_encode(array('result' => 'ok')));
		}

	} else {
		die(json_encode(array('result' => 'ok')));
	}

	die(json_encode(array('error' => 'Some error!')));
}