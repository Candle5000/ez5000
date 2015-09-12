<?php
//=====================================
// スレッド一覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/functions/template.php");

if(!isset($_GET["id"])) die("ERROR01:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR02:無効なIDです");
$user_file = "/etc/mysql-user/userbbs.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$mysql = new MySQL($userName, $password, $database);
if($mysql->connect_error) die("データベースの接続に失敗しました");

// 掲示板情報を取得
$sql = "SELECT * FROM boad WHERE sname='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR03:存在しないIDです");
$boad = new Boad($result->fetch_array());
$title = $boad->name;

// スレッド一覧を取得
$sql = "SELECT * FROM ".$id."_t ORDER BY tindex DESC";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR04:存在しないIDです");
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1><?=$boad->name?></h1>
<hr class="normal">
<ul id="linklist">
<?php
if($result->num_rows) {
	while($array = $result->fetch_array()) {
		$thread = new Thread($array);
?>
<li><a href="./u/read.php?id=<?=$boad->sname?>&tid=<?=$thread->tid?>"><?=$thread->title?></a></li>
<?php
	}
} else {
?>
<li>スレッドがありません</li>
<?php
}
?>
</ul>
<hr class="normal">
<ul id="footlink">
<li><a href="/bbs/"<?=mbi_ack(0)?>><?=mbi("9.")?>掲示板一覧</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
