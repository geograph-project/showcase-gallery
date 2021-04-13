<?php

//the config file, is a nice central place to do this. But needs to only do for http requests.
if (!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
	if (empty($_SERVER['HTTPS']) && (empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] != 'https')) {
		$status = 301;
		header("Location: https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}", true, $status);
		exit;
	}
}


//die("This site is offline for maintainance. Please check back after 7pm");

//these are using enviroment variables, set in docker, you can put hardcoded values here
$db_host = $_SERVER['CONF_DB_CONNECT'];
$db_user = $_SERVER['SHOWCASE_USER'];
$db_passwd = $_SERVER['SHOWCASE_PASSWORD'];
$db_name = $_SERVER['SHOWCASE_USER'];

$db = mysql_connect($db_host, $db_user, $db_passwd) or die("Sorry the site is offline right now.");
mysql_select_db($db_name, $db);

$CONF = array();

$CONF['cdn_url'] = $_SERVER['SHOWCASE_CDNURL'];

$CONF['cron_password'] = $_SERVER['SHOWCASE_CRONPWD'];

$CONF['maps_api_key'] = $_SERVER['SHOWCASE_MAPSKEY'];
