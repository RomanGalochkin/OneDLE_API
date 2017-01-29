<?php
if (!defined('DATALIFEENGINE') OR !$config['allow_comments']) {
	die("Hacking attempt!");
}

$columns = !isset($_REQUEST['columns']) ? '*' : $db->safesql($_REQUEST['columns']);
$start = !$_REQUEST['start'] ? 0 : $_REQUEST['start'];
$limit = intval($_REQUEST['limit']) > 10 ? 10 : intval($_REQUEST['limit']);
$limit = $limit <= 0 ? 1 : $limit;
$order = isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('ASC', 'DESC', 'asc', 'desc')) ? $db->safesql($_REQUEST['order']) : 'DESC';
$column_order = !isset($_REQUEST['column_order']) ? 'id' : $db->safesql($_REQUEST['column_order']);
$news_id = $_REQUEST['news_id'];
if (!$news_id || $news_id == 0 || $news_id === false)
	die(json_encode(array('error' => 'Key news_id not valid')));


$allowColumns = array('id', 'text', 'autor', 'date');

$tempArray = explode(',', $columns);

foreach ($tempArray as $value) {
	if (!in_array($value, $allowColumns))
		die(json_encode(array('error' => 'Column not allowed')));
}
if (!in_array($column_order, $allowColumns))
	die(json_encode(array('error' => 'Not allow sort column for comments')));

$comments = $one_api->getComments($columns, $start, $limit, $column_order, $order, $news_id);
foreach ($comments as $key => $value) {
	if (array_key_exists("text", $value))
		$comments[$key]['text'] = strip_tags($value['text'], '<br><br/>');

}
echo json_encode($comments);