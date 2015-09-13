<?php
//=====================================
// 書き込み閲覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/functions/template.php");

// 掲示板ID取得
if(!isset($_GET["id"])) die("ERROR01:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR02:無効なIDです");

// スレッドID取得
if(!isset($_GET["tid"])) die("ERROR03:IDがありません");
$tid = $_GET["tid"];
if(!preg_match("/^[0-9]{1,9}$/", $tid)) die("ERROR04:無効なIDです");

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
if(!$result->num_rows) die("ERROR05:存在しないIDです");
$boad = new Boad($result->fetch_array());
$title = $boad->name;

// スレッド情報を取得
$sql = "SELECT * FROM ".$id."_t WHERE tid=$tid";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR06:存在しないIDです");
if(!$result->num_rows) die("ERROR07:存在しないIDです");
$thread = new Thread($result->fetch_array());

// メッセージ情報(1)を取得
$sql = "SELECT * FROM ".$id."_m WHERE tid=$tid AND tmid=1";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR08:存在しないIDです");
if(!$result->num_rows) die("ERROR09:存在しないIDです");
$fmessage = new Message($result->fetch_array());

// メッセージ情報(2～)を取得
$sql = "SELECT * FROM ".$id."_m WHERE tid=$tid AND tmid > 1 ORDER BY tmid DESC";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR10:存在しないIDです");
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1><?=$boad->name?></h1>
<hr class="normal">
<h2>[<?=$thread->tid?>] <?=$thread->title?></h2>
<hr class="normal">
<p>
[1] By <?=$fmessage->name?><br />
<?=$fmessage->comment?><br />
<?=$fmessage->ts?>
</p>
<?php
while($array = $result->fetch_array()) {
	$message = new Message($array);
?>
<hr class="normal">
<p>
[<?=$message->tmid?>] By <?=$message->name?><br />
<?=$message->comment?><br />
<?=$message->ts?>
</p>
<?php
}
?>
<hr class="normal">
<ul id="footlink">
<li><a href="/bbs/u/?id=<?=$boad->sname?>"<?=mbi_ack(8)?>><?=mbi("8.")?><?=$boad->name?></a></li>
<li><a href="/bbs/"<?=mbi_ack(9)?>><?=mbi("9.")?>掲示板一覧</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
