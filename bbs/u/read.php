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
if(!is_numeric($tid)) die("ERROR04:無効なIDです");

// レス番号を取得
if(isset($_GET["tmid"])) {
	$tmid = $_GET["tmid"];
	if(!is_numeric($tmid)) die("ERROR05:無効なIDです");
}

// ページを取得
$page = (isset($_GET["page"]) && is_numeric($_GET["page"])) ? $_GET["page"] : 0;

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
if(!$result->num_rows) die("ERROR06:存在しないIDです");
$boad = new Boad($result->fetch_array());
$title = htmlspecialchars($boad->name);

// スレッド情報を取得
if(!isset($_GET["tmid"]) && !isset($_GET["view"]) && !isset($_GET["page"])) {
	$sql = "UPDATE `{$id}_t` SET `acount`=`acount`+1 WHERE `tid`='$tid'";
	$mysql->query($sql);
}
$sql = "SELECT * FROM `{$id}_t` WHERE `tid`='$tid'";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR07:存在しないIDです");
if(!$result->num_rows) die("ERROR08:存在しないIDです");
$thread = new Thread($result->fetch_array());

if(!isset($tmid)) {
	//------------------------------
	// メッセージ一覧表示
	//------------------------------

	// メッセージ情報(1)を取得 ページ0のときのみ
	if($page == 0 && !isset($_GET["view"])) {
		$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`='1'";
		$result = $mysql->query($sql);
		if($mysql->error) die("ERROR11:存在しないIDです");
		if(!$result->num_rows) die("ERROR12:存在しないIDです");
		$fmessage = new Message($result->fetch_array(), $mysql, $boad, $thread);
	}

	// メッセージ情報を取得
	if($page == 0 && !isset($_GET["view"])) {
		$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`>'1' ORDER BY `tmid` DESC LIMIT 0,$LIMIT";
	} else {
		$order = (isset($_GET["view"]) && $_GET["view"] == "asc") ? "ASC" : "DESC";
		$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' ORDER BY `tmid` $order LIMIT ".($page * $LIMIT).",$LIMIT";
	}
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR13:存在しないIDです");

	// ページ切り替えリンク生成
	$view = isset($_GET["view"]) ? ($_GET["view"] == "asc") ? "&view=asc" : "&view=desc" : "";
	if(($page > 0) && ($thread->mcount > 0)) {
		$pagelink = "<a href=\"./read.php?id=$id&tid=$tid$view&page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
	} else {
		$pagelink = mbi("*.")."前のページ | ";
	}
	if((($page + 1) * $LIMIT) < $thread->mcount) {
		$pagelink .= "<a href=\"./read.php?id=$id&tid=$tid$view&page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
	} else {
		$pagelink .= mbi("#.")."次のページ";
	}
} else {
	//------------------------------
	// 単一メッセージ表示
	//------------------------------

	// メッセージ情報を取得
	$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`='$tmid'";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR21:存在しないIDです");
	if(!$result->num_rows) die("ERROR22:メッセージが存在しません");

	$pagelink = mbi("*.")."前のページ | ".mbi("#.")."次のページ";
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
<?php
$reply = mbi("2.")."返信";
$reply = ($thread->mcount > 999 || $thread->locked) ? "[$reply]" : "[<a href=\"./form.php?mode=reform&id=".$boad->sname."&tid=$tid\"".mbi_ack(2).">$reply</a>]";
$old = "[<a href=\"./read.php?id={$boad->sname}&tid=$tid&view=asc&page=0\"".mbi_ack(4).">".mbi("4.")."最古</a>]";
$new = "[<a href=\"./read.php?id={$boad->sname}&tid=$tid&view=desc&page=0\"".mbi_ack(6).">".mbi("6.")."最新</a>]";
?>
<?=$reply?> <?=$old?> <?=$new?>
</p>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<?php
if($page == 0 && !isset($_GET["view"]) && !isset($tmid)) {
	$fmessage->printMessage($mysql, $boad, $thread);
}

while($array = $result->fetch_array()) {
	$message = new Message($array, $mysql, $boad, $thread);
	$message->printMessage($mysql, $boad, $thread);
}
?>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<?php
$url = "./read.php?id=$id&tid=$tid$view";
?>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="get" enctype="multipart/form-data">
<input name="id" type="hidden" value="<?=$id?>">
<input name="tid" type="hidden" value="<?=$tid?>">
<?=(isset($_GET["view"]) ? "<input name=\"view\" type=\"hidden\" value=\"{$_GET["view"]}\">\n" : "")?>
<input name="page" type="text" maxlength="3" value="<?=$page?>" size="4">/<?=(ceil($thread->mcount / $LIMIT) - 1)?>
<input type="submit" value="ページへ移動">
</form>
<hr class="normal">
<ul id="footlink">
<?php
if(isset($tmid)) {
?>
<li><a href="/bbs/u/read.php?id=<?=$boad->sname?>&tid=<?=$thread->tid?>"<?=mbi_ack(7)?>><?=mbi("7.")?>スレッドに戻る</a></li>
<?php
}
?>
<li><a href="/bbs/u/?id=<?=$boad->sname?>"<?=mbi_ack(8)?>><?=mbi("8.")?><?=$boad->name?></a></li>
<li><a href="/bbs/"<?=mbi_ack(9)?>><?=mbi("9.")?>掲示板一覧</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot($thread->acount);
?>
</div>
</body>
</html>
