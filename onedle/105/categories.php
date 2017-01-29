<?PHP
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


$catid = intval($_REQUEST['catid']);

if (!$user_group[$member_id['user_group']]['admin_categories']) {
	die(json_encode(array('error' => 'You are not alllowed to edit categories.')));
}

function get_sub_cats2($id, $subcategory = false)
{

	global $cat_info;
	$subfound = array();

	if (!$subcategory) {
		$subcategory = array();
		$subcategory[] = $id;
	}

	foreach ($cat_info as $cats) {
		if ($cats['parentid'] == $id) {
			$subfound[] = $cats['id'];
		}
	}

	foreach ($subfound as $parentid) {
		$subcategory[] = $parentid;
		$subcategory = get_sub_cats($parentid, $subcategory);
	}

	return $subcategory;

}


if ($_REQUEST['action'] == "addcat") {


	$cat_name = $db->safesql(htmlspecialchars(strip_tags(stripslashes($_REQUEST['cat_name'])), ENT_QUOTES, $config['charset']));
	$skin_name = "";
	$cat_icon = '';
	$show_sub = 1;
	$allow_rss = 1;

	if (!$cat_name) {
		die(json_encode(array('error' => 'Cat name is empty.')));
	}

	if (trim($_REQUEST['alt_cat_name'])) {

		$alt_cat_name = totranslit(stripslashes($_REQUEST['alt_cat_name']), true, false);

	} else {

		$alt_cat_name = totranslit(stripslashes($cat_name), true, false);

	}

	if (!$alt_cat_name) {
		die(json_encode(array('error' => 'Cat name is empty.')));
	}

	$news_sort = "";
	$news_msort = "";
	$news_number = 0;
	$category = intval($_REQUEST['parentid']);

	$reserved_name = array('tags', 'xfsearch', 'user', 'lastnews', 'catalog', 'newposts', 'favorites');

	if (in_array($alt_cat_name, $reserved_name) AND !$category) {
		die(json_encode(array('error' => 'Cat name consist of reserved name.')));
	}

	$short_tpl = "";
	$full_tpl = "";

	$meta_title = '';
	$description = '';
	$keywords = '';

	$row = $db->super_query("SELECT alt_name FROM " . PREFIX . "_category WHERE alt_name ='{$alt_cat_name}'");

	if ($row['alt_name']) {
		die(json_encode(array('error' => 'Cat name is exists.')));
	}

	$db->query("INSERT INTO " . PREFIX . "_category (parentid, name, alt_name, show_sub, allow_rss) values ('$category', '$cat_name', '$alt_cat_name', '$show_sub', '$allow_rss')");

	$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql($member_id['name']) . "', '{$_TIME}', '{$_IP}', '12', '{$cat_name}')");


	@unlink(ENGINE_DIR . '/cache/system/category.php');
	clear_cache();

	die(json_encode(array('result' => 'ok')));

} elseif ($_REQUEST['action'] == "delcat") {


	function DeleteSubcategories($parentid)
	{
		global $db;

		$subcategories = $db->query("SELECT id FROM " . PREFIX . "_category WHERE parentid = '$parentid'");

		while ($subcategory = $db->get_row($subcategories)) {
			DeleteSubcategories($subcategory['id']);

			$db->query("DELETE FROM " . PREFIX . "_category WHERE id = '" . $subcategory['id'] . "'");
		}
	}

	if (!$catid) {
		die(json_encode(array('error' => 'Cat id is empty.')));
	}

	$row = $db->super_query("SELECT count(*) as count FROM " . PREFIX . "_post WHERE category regexp '[[:<:]]($catid)[[:>:]]'");

	if ($row['count']) {

		/*if( is_array( $_REQUEST['new_category'] ) ) {
			if( ! in_array( $catid, $_REQUEST['new_category'] ) ) {
				
				$category_list = $db->safesql( htmlspecialchars( strip_tags( stripslashes( implode( ',', $_REQUEST['new_category']))), ENT_QUOTES, $config['charset'] ) );
				
				$db->query( "UPDATE " . PREFIX . "_post set category='$category_list' WHERE category regexp '[[:<:]]($catid)[[:>:]]'" );
				
				$db->query( "DELETE FROM " . PREFIX . "_category WHERE id='$catid'" );
				
				DeleteSubcategories( $catid );
				
				@unlink( ENGINE_DIR . '/cache/system/category.php' );
				
				clear_cache();

				$db->query( "INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('".$db->safesql($member_id['name'])."', '{$_TIME}', '{$_IP}', '13', '{$catid}')" );

				
				die(json_encode(array('result'=>'ok')));
			}
		}*/

		die(json_encode(array('error' => 'Category is not empty.')));

	} else {

		$db->query("DELETE FROM " . PREFIX . "_category WHERE id='$catid'");

		DeleteSubcategories($catid);

		@unlink(ENGINE_DIR . '/cache/system/category.php');

		$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql($member_id['name']) . "', '{$_TIME}', '{$_IP}', '13', '{$catid}')");

		clear_cache();

		die(json_encode(array('result' => 'ok')));;
	}

} elseif ($_REQUEST['action'] == "editcat") {

	$cat_name = $db->safesql(htmlspecialchars(strip_tags(stripslashes($_REQUEST['cat_name'])), ENT_QUOTES, $config['charset']));
	$skin_name = '';
	$cat_icon = '';

	if (trim($_REQUEST['alt_cat_name'])) {

		$alt_cat_name = totranslit(stripslashes($_REQUEST['alt_cat_name']), true, false);

	} else {

		$alt_cat_name = totranslit(stripslashes($cat_name), true, false);

	}

	$show_sub = 1;
	$allow_rss = 1;

	$catid = intval($_REQUEST['catid']);
	$parentid = intval($_REQUEST['parentid']);

	$meta_title = '';
	$description = '';
	$keywords = '';

	$reserved_name = array('tags', 'xfsearch', 'user', 'lastnews', 'catalog', 'newposts', 'favorites');

	if (in_array($alt_cat_name, $reserved_name) AND !$parentid) {
		die(json_encode(array('error' => 'Cat name consist of reserved name.')));
	}

	$short_tpl = "";
	$full_tpl = "";
	$news_sort = "";
	$news_msort = "";
	$news_number = 0;

	if (!$catid) {
		die(json_encode(array('error' => 'Cat id is empty.')));
	}
	if ($cat_name == "") {
		die(json_encode(array('error' => 'Cat name is empty.')));
	}

	$row = $db->super_query("SELECT id, alt_name FROM " . PREFIX . "_category WHERE alt_name = '$alt_cat_name'");

	if ($row['id'] and $row['id'] != $catid) {
		die(json_encode(array('error' => 'Cat id can not be with this name.')));
	}

	if (in_array($parentid, get_sub_cats($catid))) {
		die(json_encode(array('error' => 'Wrong category parent.')));
	}

	$db->query("UPDATE " . PREFIX . "_category SET parentid='$parentid', name='$cat_name', alt_name='$alt_cat_name' WHERE id='{$catid}'");
	$db->query("INSERT INTO " . USERPREFIX . "_admin_logs (name, date, ip, action, extras) values ('" . $db->safesql($member_id['name']) . "', '{$_TIME}', '{$_IP}', '14', '{$cat_name}')");

	@unlink(ENGINE_DIR . '/cache/system/category.php');
	clear_cache();

	die(json_encode(array('result' => 'ok')));
}