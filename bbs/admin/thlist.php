<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/bbs/class/board.php");

const ERRMSG001 = 'ERROR001: 不正なパラメータを検出しました。';
const ERRMSG101 = 'ERROR101: 掲示板データの取得に失敗しました。';

const PAGE_SIZE = 50;

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

// GET入力エラーチェック
if(!isset($_GET["id"]) || !preg_match('/^[a-zA-Z0-9]{4,16}$/', $_GET["id"])) die(print_error(ERRMSG001));
if(!isset($_GET["page"]) || !is_numeric($_GET["page"]) || $_GET["page"] < 1) {
	$page = 1;
} else {
	$page = $_GET["page"];
}

// 掲示板の読み込み
$sql = "SELECT * FROM board WHERE name = '{$_GET["id"]}'";
$result = $mysql->query($sql);
if($result->num_rows != 1) die(print_error(ERRMSG101));
$board = new Board($result->fetch_array());

// ページ設定用
$start = ($page - 1) * PAGE_SIZE;
$size = PAGE_SIZE;

// スレッド一覧の読み込み
$sql = "SELECT SQL_CALC_FOUND_ROWS tid, subject FROM thread WHERE bid = {$board->bid} ORDER BY top DESC, tindex DESC LIMIT $start, $size";
$result = $mysql->query($sql);
$sql = "SELECT FOUND_ROWS() count";
$threadCount = $mysql->query($sql)->fetch_object()->count;

// メッセージ数
$sql = "SELECT COUNT(*) count FROM message WHERE bid = {$board->bid}";
$messageCount = $mysql->query($sql)->fetch_object()->count;

// ページ遷移用リンク
$pageCount = ceil($threadCount / PAGE_SIZE);
$pageLinkList = array();
for($i = 1; $i <= $pageCount; $i++) {
	if($i == $page) {
		$pageLinkList[] = "$i";
	} else {
		$pageLinkList[] = "<a href=\"./thlist.php?id={$board->name}&page=$i\">$i</a>";
	}
}
$pageLink = implode(" | ", $pageLinkList);
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<div>メッセージ数 : <?=$messageCount?> / 50000</div>
<hr />
<div>ページ移動 <?=$pageLink?></div>
<hr />
<ul>
<?php
if($pageCount = 0) {
?>
<li>スレッドがありません</li>
<?php
} else {
	while($array = $result->fetch_array()) {
		$tid = $array["tid"];
		$subject = $array["subject"];
?>
<li><a href="./read.php?id=<?=$board->name?>&tid=<?=$tid?>"><?=$subject?>(<?=$tid?>)</a>[<a href="./thread.php?id=<?=$board->name?>&tid=<?$tid?>">編集</a>]</li>
<?php
	}
}
?>
</ul>
<hr />
<ul style="list-style-type:none; text-align:right;">
<li><a href="./">掲示板管理トップに戻る</a></li>
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
