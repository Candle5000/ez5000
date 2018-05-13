<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/bbs/class/board.php");

session_start();

// ログイン情報の確認
if(!isset($_SESSION["admin_auth"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	echo $http;
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/admin/login.php");
	exit;
}

// DB接続
$user_file = "/etc/mysql-user/userBBS.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$mysql = new MySQL($userName, $password, $database);
if($mysql->connect_error) die("データベースの接続に失敗しました");

// 掲示板一覧の読み込み
$sql = "SELECT * FROM board ORDER BY bid";
$result = $mysql->query($sql);
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<ul>
<?php
while($array = $result->fetch_array()) {
	$board = new Board($array);
?>
<li><a href="./thlist.php?id=<?=$board->name?>"><?=$board->title?>(<?=$board->name?>)</a>[<a href="./board.php?id=<?=$board->name?>">編集</a>]</li>
<?php
}
?>
</ul>
<hr />
<h3>* * 共通設定 * *</h3>
<ul>
<li><a href="./ban.php">書込規制設定(IP/UA指定)</a></li>
<li><a href="./report.php">通報一覧</a></li>
</ul>
<hr />
<ul style="list-style-type:none; text-align:right;">
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
