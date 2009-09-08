<?php

$headers = getallheaders();

if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
	$shakal_protocol = 'https://';
else
	$shakal_protocol = 'http://';

$shakal_host        = $headers['Host'];
$shakal_rewrite     = false;
$shakal_site_path   = 'shakal/';
$shakal_base_script = 'index.php';
$shakal_base_path   = $shakal_protocol . $shakal_host . '/' . $shakal_site_path;

unset($headers);

?>
