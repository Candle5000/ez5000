<?php
//=====================================
// 過去ログ書き込み閲覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/bbs/class/guestUser.php");
require_once("/var/www/bbs/class/memberUser.php");
require_once("/var/www/functions/template.php");
session_start();
$LIMIT = 10;

// クッキー設定
setcookie("cookiecheck", true, time() + 864000);

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

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$mysql = new MySQL($userName, $password, $database);
if($mysql->connect_error) die("データベースの接続に失敗しました");

// docomo用
$guid_on = (device_info() == 'mb' && !is_au()) ? "&guid=ON" : "";

// ゲストログイン情報
$guest = new GuestUser($mysql);

// ユーザー情報取得
if(isset($_SERVER['HTTP_X_DCMGUID'])) $uid = $mysql->real_escape_string($_SERVER['HTTP_X_DCMGUID']); // docomo
if(isset($_SERVER['HTTP_X_UP_SUBNO'])) $uid = $mysql->real_escape_string($_SERVER['HTTP_X_UP_SUBNO']); // au
if(isset($_SERVER['HTTP_X_JPHONE_UID'])) $uid = $mysql->real_escape_string($_SERVER['HTTP_X_JPHONE_UID']); // sb
if(!isset($uid)) $uid = "";

// ガラケーのみ ユーザー情報
$member = null;
if(device_info() == "mb") {
	$member = new MemberUser($mysql, "", "", $uid);
}

// 掲示板情報を取得
$sql = "SELECT * FROM `board` WHERE `name`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR06:存在しないIDです");
$board = new Board($result->fetch_array());
$title = htmlspecialchars($board->title);

// スレッド情報を取得
$sql = "SELECT T.tid,T.subject,T.tindex,";
$sql .= "IF(LENGTH(T.readpass) > 0,TRUE,FALSE) isset_readpass,IF(LENGTH(T.writepass) > 0,TRUE,FALSE) isset_writepass,";
$sql .= "T.access_cnt,COUNT(1) message_cnt,T.update_ts,T.locked,T.top,T.next_tmid";
$sql .= " FROM thread_archive T JOIN message_archive M ON T.bid=M.bid AND T.tid=M.tid";
$sql .= " WHERE T.bid='{$board->bid}' AND T.tid='$tid' GROUP BY tid";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR07:存在しないIDです");
if(!$result->num_rows) die("ERROR08:存在しないIDです");
$thread = new Thread($result->fetch_array());

// 閲覧パスの確認
if($thread->isset_readpass && !isset($_SESSION["read_auth"]["{$board->bid}"]["{$thread->tid}"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	echo $http;
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/archive/readpass.php?id=$id&tid=$tid");
	exit;
}

if(!isset($tmid)) {
	//------------------------------
	// メッセージ一覧表示
	//------------------------------

	// メッセージ情報(1)を取得 ページ0のときのみ
	if($page == 0 && !isset($_GET["view"])) {
		$sql = "SELECT `mid`,`tmid`,`name`,`comment`,`image`,`post_ts`,`update_ts`,`update_cnt`";
		$sql .= " FROM `message_archive` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='1'";
		$result = $mysql->query($sql);
		if($mysql->error) die("ERROR11:存在しないIDです");
		if(!$result->num_rows) die("ERROR12:存在しないIDです");
		$fmessage = new Message($result->fetch_array(), $mysql, $board, $thread);
	}

	// メッセージ情報を取得
	if($page == 0 && !isset($_GET["view"])) {
		$sql = "SELECT `mid`,`tmid`,`name`,`comment`,`image`,`post_ts`,`update_ts`,`update_cnt`";
		$sql .= " FROM `message_archive` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`>'1'";
		$sql .= " ORDER BY `tmid` DESC LIMIT 0,$LIMIT";
	} else {
		$order = (isset($_GET["view"]) && $_GET["view"] == "asc") ? "ASC" : "DESC";
		$sql = "SELECT `mid`,`tmid`,`name`,`comment`,`image`,`post_ts`,`update_ts`,`update_cnt`";
		$sql .= " FROM `message_archive` WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
		$sql .= " ORDER BY `tmid` $order LIMIT ".($page * $LIMIT).",$LIMIT";
	}
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR13:存在しないIDです");

	// ページ切り替えリンク生成
	$view = isset($_GET["view"]) ? ($_GET["view"] == "asc") ? "&view=asc" : "&view=desc" : "";
	if(($page > 0) && ($thread->message_cnt > 0)) {
		$pagelink = "<a href=\"./read.php?id=$id&tid=$tid$view&page=".($page - 1).$guid_on."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
	} else {
		$pagelink = mbi("*.")."前のページ | ";
	}
	if((($page + 1) * $LIMIT) < $thread->message_cnt) {
		$pagelink .= "<a href=\"./read.php?id=$id&tid=$tid$view&page=".($page + 1).$guid_on."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
	} else {
		$pagelink .= mbi("#.")."次のページ";
	}
} else {
	//------------------------------
	// 単一メッセージ表示
	//------------------------------

	// メッセージ情報を取得
	$sql = "SELECT `mid`,`tmid`,`name`,`comment`,`image`,`post_ts`,`update_ts`,`update_cnt`";
	$sql .= " FROM `message_archive` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid'";
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
<h1><?=htmlspecialchars($board->title)?></h1>
<hr class="normal">
<h2>[<?=$thread->tid?>] <?=htmlspecialchars($thread->subject)?></h2>
<hr class="normal">
<p>
<?php
$old = "[<a href=\"./read.php?id={$board->name}&tid=$tid&view=asc&page=0.$guid_on\"".mbi_ack(4).">".mbi("4.")."最古</a>]";
$new = "[<a href=\"./read.php?id={$board->name}&tid=$tid&view=desc&page=0.$guid_on\"".mbi_ack(6).">".mbi("6.")."最新</a>]";
?>
<?=$old?> <?=$new?>
</p>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<?php
if($page == 0 && !isset($_GET["view"]) && !isset($tmid)) {
	$fmessage->printArchiveMessage();
?>
<hr class="message">
<?php
}

$hrFlag = false;
while($array = $result->fetch_array()) {
	if($hrFlag) {
?>
<hr class="message">
<?php
	} else {
		$hrFlag = true;
	}
	$message = new Message($array, $mysql, $board, $thread);
	$message->printArchiveMessage();
}
?>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<?php
if(!isset($tmid)) {
?>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="get" enctype="multipart/form-data">
<input name="id" type="hidden" value="<?=$id?>">
<input name="tid" type="hidden" value="<?=$tid?>">
<?=(isset($_GET["view"]) ? "<input name=\"view\" type=\"hidden\" value=\"{$_GET["view"]}\">\n" : "")?>
<input name="page" type="text" maxlength="3" value="<?=$page?>" size="4">/<?=(ceil($thread->message_cnt / $LIMIT) - 1)?>
<input type="submit" value="ページへ移動">
</form>
<hr class="normal">
<?php
}
?>
<ul id="footlink">
<?php
if(isset($tmid)) {
?>
<li><a href="/bbs/archive/read.php?id=<?=$board->name?>&tid=<?=$thread->tid.$guid_on?>"<?=mbi_ack(8)?>><?=mbi("8.")?>スレッドに戻る</a></li>
<?php
}
?>
<li><a href="/bbs/archive/?id=<?=$board->name.$guid_on?>"<?=mbi_ack(9)?>><?=mbi("9.")?><?=$board->title?>(過去ログ)</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot($board->access_cnt_archive);
?>
</div>
</body>
</html>
