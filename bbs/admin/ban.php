<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/input_check.php");

const PAGE_SIZE = 100;

session_start();

// ログイン情報の確認
if(!isset($_SESSION["admin_auth"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
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

// 入力エラーリスト
$error_list = array();

// GET入力エラーチェック
if(!isset($_GET["page"]) || !is_numeric($_GET["page"]) || $_GET["page"] < 1) {
	$page = 1;
} else {
	$page = $_GET["page"];
}

// ページ設定用
$start = ($page - 1) * PAGE_SIZE;
$size = PAGE_SIZE;

// 規制リストの読み込み
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM bbs_ban ORDER BY registered_date DESC LIMIT $start, $size";
$result = $mysql->query($sql);
$sql = "SELECT FOUND_ROWS() count";
$listCount = $mysql->query($sql)->fetch_object()->count;

// POST送信時
if($_SERVER["REQUEST_METHOD"] == "POST") {

}

// ページ遷移用リンク
$pageCount = ceil($listCount / PAGE_SIZE);
$pageLinkList = array();
for($i = 1; $i <= $pageCount; $i++) {
	if($i == $page) {
		$pageLinkList[] = "$i";
	} else {
		$pageLinkList[] = "<a href=\"./ban.php?page=$i\">$i</a>";
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
<h4>書き込み規制設定の編集</h4>
<?php
foreach($error_list as $error) {
?>
<div style="color:#F00;"><?=$error?></div>
<?php
}
?>
<hr />
<div>新規追加</div>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
IP <input type="text" name="ip" size="15" /><br />
UA <input type="text" name="ua" size="60" /><br />
<textarea name="note" cols="80" rows="1" wrap="soft"></textarea><br />
<input type="submit" name="submit_add" value=" 追加 " />
</form>
<hr />
<div>ページ移動 <?=$pageLink?></div>
<hr />
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
<?php
if(!$listCount) {
?>
<div>規制情報が登録されていません。</div>
<hr />
<?php
}
while($array = $result->fetch_array()) {
	$ip = is_empty($array["ip"]) ? "未指定" : $array["ip"];
	$ua = is_empty($array["ua"]) ? "未指定" : $array["ua"];
?>
IP:<?=$ip?><br />
UA:<?=$ua?><br />
登録日時:<?=$array["registered_date"]?><br />
<textarea name="note[<?=$array["id"]?>]" cols="80" rows="1" wrap="soft"><?=$array["note"]?></textarea><br />
<input type="submit" name="submit_mod[<?=$array["id"]?>]" value=" 編集 " />
<input type="submit" name="submit_del[<?=$array["id"]?>]" value=" 削除 " />
<hr />
<?php
}
?>
</form>
<ul style="list-style-type:none; text-align:right;">
<li><a href="./">掲示板管理トップに戻る</a></li>
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
