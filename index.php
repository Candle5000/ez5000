<?php
//=====================================
// トップページ
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/item.php");

$PAGE_ID = 10000;
$PAGESIZE = 10;
$title = "EZ5000";
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
if(mysqli_connect_error()) {
	die("データベースの接続に失敗しました");
}
$sql = "SELECT * FROM `board`";
$result = $data->query($sql);
$count = $data->top_count();
$rows = $data->select_all_l("*", "info", 0, $PAGESIZE, "id desc");

// docomo用
$guid_on = (device_info() == 'mb' && !is_au()) ? "&guid=ON" : "";
?>
<html>
<head>
<?=pagehead($title)?>
<meta name="robots" content="index" />
<meta name="Keywords" content="オンラインRPG,MMORPG,エターナルゾーン,攻略情報,データベース,蛭注意,EZ5000,5分戦闘,五千" />
<meta name="description" content="【オンラインRPG】エターナルゾーンの攻略・交流総合ファンサイト" />
<meta name="author" content="Candle" />
</head>
<body>
<div id="all">
<h1>EZ5000</h1>
<hr class="normal">
<ul class="linklist">
<li><a href="./about/"<?=mbi_ack(1)?>><?=mbi("1.")?>このサイトについて</a></li>
<li><a href="./db/"<?=mbi_ack(2)?>><?=mbi("2.")?>データベース</a></li>
<?php
$ak = 2;
while($array = $result->fetch_array()) {
	$board = new Board($array);
	$ak++;
	$accesskey = ($ak < 10) ? mbi_ack($ak) : "";
	$aklabel = ($ak < 10) ? mbi($ak.'.') : "";
?>
<li><a href="./bbs/?id=<?=$board->name.$guid_on?>"<?=$accesskey?>><?=$aklabel.$board->title?></a></li>
<?php
}
?>
<li><a href="http://mbbs.tv/u/?id=5483215jwg0">日記掲示板</a></li>
<li><a href="http://mbbs.tv/u/?id=nikki2">個人日記掲示板</a></li>
<li><a href="http://mbbs.tv/u/?id=bosyuuda">メンバー募集掲示板</a></li>
<li><a href="http://mbbs.tv/u/?id=temeda">ギルド交流掲示板</a></li>
<li><a href="http://mbbs.tv/u/?id=etazoguild">鍵付きギルド掲示板</a></li>
<li><a href="http://mbbs.tv/u/?id=5000search">探し人掲示板</a></li>
<li><a href="http://mbbs.tv/u/?id=5000pt">パーティ募集掲示板</a></li>
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
<ul class="linklist">
<li><a href="./info/">インフォメーション</a></li>
</ul>
<?php
if($rows > 0) {
	while($row = $data->fetch()) {
?>
<div class="infobox">
<div class="date"><?=preg_replace("/-/", "/", $row["id"])?></div>
<p>
<span class="boxtitle">■<?=$row["subject"]?></span><br />
<?=nl2br($row["info"])?>
</p>
</div>
<?php
	}
}
$s = (device_info() == 'mb') ? "" : "s";
?>
<ul id="footlink">
<li><a href="https://www.eternalzone.com/">エターナルゾーン公式サイト</a></li>
</ul>
<?php
$data->select_id("accesscount", $PAGE_ID);
$c_data = $data->fetch();
pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
?>
</div>
</body>
</html>
