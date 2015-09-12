<?php
//=====================================
// 掲示板一覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/functions/template.php");

$title = "掲示板";
$sql = "SELECT * FROM boad";
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
while($array = $result->fetch_array()) {
	$boad = new Boad($array);
?>
<li><a href="./u/?id=<?=$boad->sname?>"><?=$boad->name?></a></li>
<?php
}
?>
</ul>
<hr class="normal">
<ul id="footlink">
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
