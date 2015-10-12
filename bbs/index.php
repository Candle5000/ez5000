<?php
//=====================================
// 掲示板一覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/functions/template.php");

$title = "掲示板";
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
$sql = "UPDATE `accesscount` SET `count`=`count`+1 WHERE `id`='10001'";
$mysql->query($sql);
$sql = "SELECT `count` FROM `accesscount` WHERE `id`='10001'";
$count = $mysql->query($sql)->fetch_object()->count;
$sql = "SELECT * FROM `boad`";
$result = $mysql->query($sql);
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>掲示板一覧</h1>
<hr class="normal">
<ul id="linklist">
<?php
if($result->num_rows) {
	while($array = $result->fetch_array()) {
		$boad = new Boad($array);
?>
<li><a href="./u/?id=<?=$boad->sname?>"><?=$boad->name?></a></li>
<?php
	}
} else {
?>
<li>掲示板がありません</li>
<?php
}
?>
</ul>
<hr class="normal">
<ul id="footlink">
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot($count);
?>
</div>
</body>
</html>
