<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/functions/template.php");

session_start();

// ログイン情報の確認
if(!isset($_SESSION["admin_auth"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	echo $http;
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/admin_login.php");
	exit;
}
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<ul>
<li>テスト</li>
</ul>
<hr>
<a href="/" target="_blank">トップページを開く</a>
<hr>
<hr>
</body>
</html>
