<?php
if (!defined('DATALIFEENGINE') OR !$config['allow_comments']) {
	die("Hacking attempt!");
}

$columns = !isset($_REQUEST['columns']) ? '*' : $db->safesql($_REQUEST['columns']);

$column_order = !isset($_REQUEST['column_order']) ? 'id' : $db->safesql($_REQUEST['column_order']);

$allowColumns = array('id',
	'autor', 'date', 'short_story', 'full_story', 'xfields',
	'title', 'descr', 'keywords', 'category', 'alt_name', 'comm_num',
	'allow_comm', 'allow_main', 'approve', 'fixed', 'allow_br', 'symbol',
	'tags', 'metatitle');

$allowExtraColumns = array('eid', 'news_id', 'news_read', 'allow_rate', 'rating',
	'vote_num', 'votes', 'view_edit', 'disable_index', 'related_ids', 'access',
	'editdate', 'editor', 'reason', 'user_id');

$allAllowColumns = array_merge($allowColumns, $allowExtraColumns);
$tempArray = explode(',', $columns);

if ($columns != "*") {
	foreach ($tempArray as $value) {
		if (!in_array($value, $allAllowColumns))
			die(json_encode(array('error' => 'Not allow column for news')));

	}
}

if (!in_array($column_order, $allAllowColumns))
	die(json_encode(array('error' => 'Not allow sort column for news')));

if (in_array('full_story', $tempArray) && !in_array('short_story', $tempArray)) {
	$columns .= ',short_story';
}

$order = isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('ASC', 'DESC', 'asc', 'desc')) ? $db->safesql($_REQUEST['order']) : 'DESC';
$start = intval($_REQUEST['start']);
$limit = intval($_REQUEST['limit']) > 10 ? 10 : intval($_REQUEST['limit']);
$limit = $limit <= 0 ? 1 : $limit;

$news = $one_api->getNews($columns, $start, $limit, $column_order, $order);

foreach ($news as $key => $value) {
	if (array_key_exists("short_story", $value))
		$news[$key]['short_story'] = strip_tags($value['short_story'], '<br><br/><a><img>');
	if (array_key_exists("full_story", $value)) {
		if ($news[$key]['full_story'] == '') {
			$news[$key]['full_story'] = $news[$key]['short_story'];
		} else {
			$news[$key]['full_story'] = strip_tags($value['full_story'], '<br><br/><a><img>');
		}
	}
}
echo json_encode($news);