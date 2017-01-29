<?php
if (!defined('DATALIFEENGINE')) {
	die("Hacking attempt!");
}
$sqlTable = "CREATE TABLE IF NOT EXISTS `" . PREFIX . "_api_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expire` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

$db->query($sqlTable, false);