<?php
//=====================================
// トップページ
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/item.php");

$PAGE_ID = 10000;
$PAGESIZE = 10;
$title = "EZ5000テストサイト";
//$user_file = "../../../../etc/mysql-user/user5000.ini";
$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database);
$count = $data->top_count();
$rows = $data->select_all_l("*", "info", 0, $PAGESIZE, "id", "desc");
?>
<html>
<head>
<?=pagehead($title)?>
<meta name="robots" content="index" />
<meta name="Keywords" content="オンラインRPG,MMORPG,エターナルゾーン,攻略情報,データベース,蛭注意,EZ5000,5分戦闘,五千" />
<meta name="description" content="【オンラインRPG】エターナルゾーンの攻略サイト開発スペース。テスト公開中。" />
<meta name="author" content="Candle" />
</head>
<body>
<div id="all">
<h1>EZ5000テストサイト</h1>
<hr class="normal">
<ul id="linklist">
<li><a href="./about/"<?=mbi_ack(1)?>><?=mbi("1.")?>このサイトについて</a></li>
<li><a href="./db/"<?=mbi_ack(2)?>><?=mbi("2.")?>データベース</a></li>
</ul>
<hr class="normal">
<div class="cnt">
<table id="topcount">
<tr><td class="lft" width="60%">今日の冒険者数</td><td class="rgt" width="40%"><?=$count['t']?> 人</td></tr>
<tr><td class="lft">昨日の冒険者数</td><td class="rgt"><?=$count['y']?> 人</td></tr>
<tr><td class="lft">今月の冒険者数</td><td class="rgt"><?=$count['m']?> 人</td></tr>
</table>
</div>
<hr class="normal">
<ul id="linklist">
<li><a href="./info/">インフォメーション</a></li>
</ul>
<?php
if($rows > 0) {
	while($row = $data->fetch()) {
?>
<div id="infobox">
<div id="date"><?=preg_replace("/-/", "/", $row["id"])?></div>
<p>
<span id="boxtitle">■<?=$row["subject"]?></span><br />
<?=nl2br($row["info"])?>
</p>
</div>
<?php
	}
}
?>
<ul id="footlink">
<li><a href="http://5000.sameha.org/">本家5000に帰る</a></li>
</ul>
<?php
$data->select_id("accesscount", $PAGE_ID);
$c_data = $data->fetch();
pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
?>
</div>
</body>
</html>
