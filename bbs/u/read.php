<?php
//=====================================
// 書き込み閲覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/functions/template.php");
$LIMIT = 10;

// 掲示板ID取得
if(!isset($_GET["id"])) die("ERROR01:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR02:無効なIDです");

// スレッドID取得
if(!isset($_GET["tid"])) die("ERROR03:IDがありません");
$tid = $_GET["tid"];
if(!preg_match("/^[0-9]{1,9}$/", $tid)) die("ERROR04:無効なIDです");

// ページを取得
$page = (isset($_GET["page"]) && preg_match("/^[0-9]+$/", $_GET["page"])) ? $_GET["page"] : 0;

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
$sql = "SELECT * FROM `boad` WHERE `sname`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR05:存在しないIDです");
$boad = new Boad($result->fetch_array());
$title = htmlspecialchars($boad->name);

// レス数を取得
$sql = "SELECT COUNT(`mid`) AS `count` FROM `{$id}_m` WHERE `tid`='$tid'";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR06:存在しないIDです");
$array = $result->fetch_array();
$rows = $array["count"];

// スレッド情報を取得
$sql = "SELECT * FROM `{$id}_t` WHERE `tid`='$tid'";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR07:存在しないIDです");
if(!$result->num_rows) die("ERROR08:存在しないIDです");
$thread = new Thread($result->fetch_array());

// メッセージ情報(1)を取得 ページ0のときのみ
if($page == 0) {
	$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`='1'";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR09:存在しないIDです");
	if(!$result->num_rows) die("ERROR10:存在しないIDです");
	$fmessage = new Message($result->fetch_array());
}

// メッセージ情報を取得
if($page == 0) {
	$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`>'1' ORDER BY `tmid` DESC LIMIT 0,$LIMIT";
} else {
	$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' ORDER BY `tmid` DESC LIMIT ".($page * $LIMIT).",$LIMIT";
}
$result = $mysql->query($sql);
if($mysql->error) die("ERROR11:存在しないIDです");

// ページ切り替えリンク生成
if(($page > 0) && ($rows > 0)) {
	$pagelink = "<a href=\"./read.php?id=$id&tid=$tid&page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
} else {
	$pagelink = mbi("*.")."前のページ | ";
}
if((($page + 1) * $LIMIT) < $rows) {
	$pagelink .= "<a href=\"./read.php?id=$id&tid=$tid&page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
} else {
	$pagelink .= mbi("#.")."次のページ";
}
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1><?=htmlspecialchars($boad->name)?></h1>
<hr class="normal">
<h2>[<?=$thread->tid?>] <?=htmlspecialchars($thread->title)?></h2>
<hr class="normal">
<p>
[<a href="./form.php?mode=reform&id=<?=$boad->sname?>&tid=<?=$tid?>"<?=mbi_ack(2)?>><?=mbi("2.")?>返信</a>]
</p>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<?php
if($page == 0) {
?>
<hr class="normal">
<p>
[1] By <?=htmlspecialchars($fmessage->name)?><br />
<?=nl2br(htmlspecialchars($fmessage->comment))?><br />
<?=$fmessage->ts?><br />
[<a href="./form.php?mode=reform&id=<?=$boad->sname?>&tid=<?=$tid?>&re=1">返信</a>] [<a href="./form.php?mode=modify&id=<?=$boad->sname?>&tid=<?=$tid?>&tmid=1">編集</a>]
</p>
<?php
}

while($array = $result->fetch_array()) {
	$message = new Message($array);
?>
<hr class="normal">
<p>
[<?=$message->tmid?>] By <?=htmlspecialchars($message->name)?><br />
<?=nl2br(htmlspecialchars($message->comment))?><br />
<?=$message->ts?><br />
[<a href="./form.php?mode=reform&id=<?=$boad->sname?>&tid=<?=$tid?>&re=<?=$message->tmid?>">返信</a>] [<a href="./form.php?mode=modify&id=<?=$boad->sname?>&tid=<?=$tid?>&tmid=<?=$message->tmid?>">編集</a>]
</p>
<?php
}
?>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
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
