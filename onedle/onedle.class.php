<?php
if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}

class ONE_API extends DLE_API
{
	public function getNews($fields = "*", $start = 0, $limit = 10, $sort = 'id', $sort_order = 'desc')
	{
		$condition = 'approve=1';
		return $this->load_table(PREFIX . "_post k LEFT JOIN " . PREFIX . "_post_extras j ON k.id = j.news_id", $fields, $condition, $multirow = true, $start, $limit, $sort, $sort_order);
	}

	public function getComments($fields = "*", $start = 0, $limit = 10, $sort = 'id', $sort_order = 'desc', $news_id)
	{
		$condition = "post_id = $news_id";
		return $this->load_table(PREFIX . "_comments", $fields, $condition, $multirow = true, $start, $limit, $sort, $sort_order);
	}

	public function getOneNews($fields = "*", $start = 0, $limit = 0, $sort = 'id', $sort_order = 'desc', $news_id)
	{
		$condition = "approve=1 AND id = $news_id";
		return $this->load_table(PREFIX . "_post k LEFT JOIN " . PREFIX . "_post_extras j ON k.id = j.news_id", $fields, $condition, $multirow = true, $start, $limit, $sort, $sort_order);
	}
}