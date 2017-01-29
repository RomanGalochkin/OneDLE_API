<?php
if (!defined('DATALIFEENGINE') OR !$config['allow_comments']) {
	die("Hacking attempt!");
}

$columns = !isset($_REQUEST['columns']) ? '*' : $db->safesql($_REQUEST['columns']);
$news_id = intval($_REQUEST['news_id']);
if (!$news_id || $news_id == 0 || $news_id === false)
	die(json_encode(array('error' => 'Key news_id not valid')));

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

if (in_array('full_story', $tempArray) && !in_array('short_story', $tempArray)) {
	$columns .= ',short_story';
}


$limit = 1;
$start = 0;

$news = $one_api->getOneNews($columns, $start, $limit, $column_order, $order, $news_id);

if (!$news) {
	die(json_encode(array('error' => 'News not found or not availible.')));
}

if (isset($news[0]['short_story'])) {
	$news[0]['short_story'] = strip_tags($news[0]['short_story'], '<br><br/><a><img>');
}
if (isset($news[0]['full_story'])) {
	if ($news[0]['full_story'] == '') {
		$news[0]['full_story'] = $news[0]['short_story'];
	} else {
		$news[0]['full_story'] = strip_tags($news[0]['full_story'], '<br><br/><a><img>');
	}
}

echo json_encode($news);