<?php
//=====================================
// データベース トップページ
//=====================================
require_once("../class/mysql.php");
require_once("../class/guestdata.php");
require_once("../functions/template.php");
require_once("../functions/item.php");

$PAGE_ID = 12000;
$title = "データベース";
//$user_file = "../../../../etc/mysql-user/user5000.ini";
$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database, 0);
?>
<html>
<head>
<?pagehead($title)?>
</head>
<body>
<div id="all">
<h1>データベース</h1>
<hr class="normal">
<ul id="dblink">
<li>　</li>
<li><a href="./item/"<?=mbi_ack(2)?>><?=mbi("2.")?>アイテムデータ</a></li>
<li>　</li>
<li>　</li>
<li>　</li>
<li><a href="./class/"<?=mbi_ack(6)?>><?=mbi("6.")?>クラスデータ</a></li>
<li><a href="./skill/"<?=mbi_ack(7)?>><?=mbi("7.")?>スキルデータ</a></li>
<li>　</li>
<li>　</li>
</ul>
<hr class="normal">
<ul id="footlink">
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?
$data->select_id("accesscount", $PAGE_ID);
$c_data = $data->fetch();
pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
?>
</div>
</body>
</html>

