<?php
namespace Shakal;

define (__NAMESPACE__ . '\DS', DIRECTORY_SEPARATOR);
define (__NAMESPACE__ . '\SITE_PATH', realpath(dirname(__FILE__)) . DS);

require(SITE_PATH.'system'.DS.'init.php');

?>
