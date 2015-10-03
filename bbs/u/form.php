<?php
//=====================================
// 書き込みフォーム
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/functions/template.php");

// モードを取得
if(!isset($_GET["mode"])) die("ERROR01:モードが設定されていません");
switch($mode = $_GET["mode"]) {
	case "thform":
		$mode = 0;
		break;
	case "reform":
		$mode = 1;
		break;
	case "modify":
		$mode = 2;
		break;
	default:
		die("ERROR02:無効なモードです");
		break;
}

// 掲示板ID取得
if(!isset($_GET["id"])) die("ERROR03:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR04:無効なIDです");

// スレッドID取得 返信/編集モードのみ
if($mode == 1 || $mode == 2) {
	if(!isset($_GET["tid"])) die("ERROR05:IDがありません");
	$tid = $_GET["tid"];
	if(!preg_match("/^[0-9]{1,9}$/", $tid)) die("ERROR06:無効なIDです");
}

// レス番号取得 編集モードのみ
if($mode == 2) {
	if(!isset($_GET["tmid"])) die("ERROR07:IDがありません");
	$tmid = $_GET["tmid"];
	if(!preg_match("/^[0-9]{1,9}$/", $tmid)) die("ERROR08:無効なIDです");
}

// 返信先レス番号取得 返信モードのみ
if($mode == 1) {
	if(isset($_GET["re"])) {
		$re = $_GET["re"];
		if(!preg_match("/^[0-9]{1,9}$/", $re)) $re = 0;
	} else {
		$re = 0;
	}
}

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

// 掲示板情報を取得
$sql = "SELECT * FROM `boad` WHERE `sname`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR11:存在しないIDです");
$boad = new Boad($result->fetch_array());
$title = $boad->name;

// スレッド情報を取得 返信/編集モードのみ
if($mode == 1 || $mode == 2) {
	$sql = "SELECT * FROM `".$id."_t` WHERE `tid`='$tid'";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR12:存在しないIDです");
	if(!$result->num_rows) die("ERROR13:存在しないIDです");
	$thread = new Thread($result->fetch_array());
}

// メッセージ情報を取得 編集モードのみ
if($mode == 2) {
	$sql = "SELECT * FROM `".$id."_m` WHERE `tid`='$tid' AND `tmid`='$tmid'";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR14:存在しないIDです");
	if(!$result->num_rows) die("ERROR15:メッセージが見つかりません");
	$message = new Message($result->fetch_array());
}

// ページタイトル設定
switch($mode) {
	case 0:
		$title = "新規スレッド作成";
		break;
	case 1:
		$title = "{$thread->title}への返信";
		break;
	case 2:
		$title = "メッセージ編集";
		break;
	default:
		die("ERROR21:不正な操作です");
		break;
}

// コメントフォームのサイズ
switch(device_info()) {
	case "sp":
		$comment_w = 40;
		$comment_h = 6;
		break;
	case "mb":
		$comment_w = 40;
		$commnet_h = 4;
		break;
	case "pc":
		$comment_w = 80;
		$comment_h = 12;
		break;
	default:
		$comment_w = 40;
		$comment_h = 4;
		break;
}

// フォーム内容
if($mode != 2) {
	$name = "";
	$subject = "";
	$comment = (isset($re) && $re != 0) ? ">>$re" : "";
} else {
	$name = $message->name;
	$subject = $thread->title;
	$comment = $message->comment;
}
?>
<html>
<head>
<?=pagehead($boad->name)?>
</head>
<body>
<div id="all">
<h1><?=$boad->name?></h1>
<hr class="normal">
<h2><?=$title?></h2>
<form action="post.php" method="post" enctype="multipart/form-data">
お名前<br />
<input name="name" type="text" value="<?=$name?>" maxlength="30"><br />
<?php
// タイトル入力 スレッド作成/編集のみ
if($mode == 0 || ($mode == 2 && $message->tmid == 1)) {
?>
タイトル<br />
<input name="sbj" type="text" maxlength="40" value="<?=$subject?>"><br />
<?php
}
?>
本文<br />
<textarea name="comment" cols="<?=$comment_w?>" rows="<?=$comment_h?>" wrap="virtual"><?=$comment?></textarea><br />
編集/削除パス<br />
<input type="password" name="pass" maxlength="32" value=""><br />
<hr class="normal">
<input type="hidden" name="id" value="<?=$boad->id?>">
<?php
if($mode == 1 || $mode == 2) {
?>
<input type="hidden" name="tid" value="<?=$thread->tid?>">
<?php
}
?>
<?php
if($mode == 2) {
?>
<input type="hidden" name="tmid" value="<?=$message->tmid?>">
<input type="hidden" name="mid" value="<?=$message->mid?>">
<?php
}
?>
<input type="hidden" name="act" value="<?=$_GET["mode"]?>">
<?php
if($mode == 2) {
?>
<input type="submit" value=" 編集 ">
<?php
} else {
?>
<input type="submit" value=" 投稿 ">
<?php
}
?>
</form>
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
