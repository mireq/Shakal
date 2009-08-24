<?php

if (!defined('SITE_PATH')) {
	return;
}

require(SITE_PATH.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'ShakalPath.php');
require(ShakalPath::toPath(SITE_PATH.'system/ShakalExceptions.php'));
require(ShakalPath::toPath(SITE_PATH.'system/ShakalRouter.php'));
require(ShakalPath::toPath(SITE_PATH.'system/ShakalTable.php'));

require(ShakalPath::toPath(SITE_PATH.'system/ShakalSql.php'));

?>
