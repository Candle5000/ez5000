<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/message.php");

const ERRMSG001 = 'ERROR001: 不正なパラメータを検出しました。';
const ERRMSG002 = 'ERROR002: 不正なパラメータを検出しました。';
const ERRMSG003 = 'ERROR003: 不正なパラメータを検出しました。';
const ERRMSG101 = 'ERROR101: 掲示板データの取得に失敗しました。';
const ERRMSG102 = 'ERROR102: スレッドの取得に失敗しました。';
const ERRMSG103 = 'ERROR103: メッセージの取得に失敗しました。';

const PAGE_SIZE = 50;

session_start();

// ログイン情報の確認
if(!isset($_SESSION["admin_auth"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	echo $http;
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/admin/login.php");
	exit;
}

// DB接続
$user_file = "/etc/mysql-user/userBBS.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$mysql = new MySQL($userName, $password, $database);
if($mysql->connect_error) die("データベースの接続に失敗しました");

// GET入力エラーチェック
if(!isset($_GET["id"]) || !preg_match('/^[a-zA-Z0-9]{4,16}$/', $_GET["id"])) die(print_error(ERRMSG001));
if(!isset($_GET["tid"]) || !is_numeric($_GET["tid"]) || $_GET["tid"] < 1) die(print_error(ERRMSG002));
if(!isset($_GET["page"]) || !is_numeric($_GET["page"]) || $_GET["page"] < 1) {
	$page = 1;
} else {
	$page = $_GET["page"];
}

// 掲示板の読み込み
$sql = "SELECT * FROM board WHERE name = '{$_GET["id"]}'";
$result = $mysql->query($sql);
if($result->num_rows != 1) die(print_error(ERRMSG101));
$board = new Board($result->fetch_array());

// スレッド情報の読み込み
$sql = "SELECT tid, subject FROM thread WHERE bid = {$board->bid} AND tid = {$_GET["tid"]}";
$result = $mysql->query($sql);
if($result->num_rows != 1) die(print_error(ERRMSG102));
$thread = $result->fetch_object();

// ページ設定用
$start = ($page - 1) * PAGE_SIZE;
$size = PAGE_SIZE;

// メッセージ一覧の読み込み
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM message WHERE bid = {$board->bid} AND tid = {$thread->tid} ORDER BY tmid DESC LIMIT $start, $size";
$result = $mysql->query($sql);
if($result->num_rows == 0) die(print_error(ERRMSG103));
$sql = "SELECT FOUND_ROWS() count";
$messageCount = $mysql->query($sql)->fetch_object()->count;

// 操作メッセージの取得
$actInfo = "";
if(isset($_POST["msgId"])) {
	switch($_POST["msgId"]) {
		case "0":
			$actInfo = "<div style=\"color:#00F;\">メッセージを削除しました。</div>";
			break;
		case "1":
			$actInfo = "<div style=\"color:#F00;\">メッセージが選択されていません。</div>";
			break;
		case "2":
			$actInfo = "<div style=\"color:#F00;\">選択されたメッセージが存在していません。</div>";
			break;
		case "3":
			$actInfo = "<div style=\"color:#F00;\">削除ログの更新に失敗しました。</div>";
			break;
		case "4":
			$actInfo = "<div style=\"color:#F00;\">メッセージの削除に失敗しました。</div>";
			break;
	}
}

// ページ遷移用リンク
$pageCount = ceil($messageCount / PAGE_SIZE);
$pageLinkList = array();
for($i = 1; $i <= $pageCount; $i++) {
	if($i == $page) {
		$pageLinkList[] = "$i";
	} else {
		$pageLinkList[] = "<a href=\"./read.php?id={$board->name}&tid={$thread->tid}&page=$i\">$i</a>";
	}
}
$pageLink = implode(" | ", $pageLinkList);
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<div>掲示板 : [<?=$board->name?>]<?=$board->title?></div>
<div>スレッド : [<?=$thread->tid?>]<?=$thread->subject?></div>
<?=$actInfo?>
<hr />
<div>ページ移動 <?=$pageLink?></div>
<hr />
<form action="./delmsg.php" method="POST">
<?php
while($array = $result->fetch_array()) {
	$message = new Message($array, $mysql, $board, $thread);
	$message->name = htmlspecialchars($message->name);
	$message->comment = nl2br(htmlspecialchars($message->comment));
	$message->ua = htmlspecialchars($message->ua);
	if($message->image != "") {
		$file_id = "{$board->name}-{$thread->tid}-{$message->tmid}-{$message->image}";
		if(file_exists("/var/www/img/bbs/$file_id")) {
			$imageinfo = @getimagesize("/var/www/img/bbs/$file_id");
			$imagesize = ceil(filesize("/var/www/img/bbs/$file_id") / 1024);
			if(!$imageinfo || !$imageinfo[0]) {
				$img = "<br />\n[画像の読み込みに失敗しました]\n";
			} else if(file_exists("/var/www/img/bbs/$file_id.png")) {
				$img = "<br />\n<a href=\"/img/bbs/$file_id\"><img src=\"/img/bbs/$file_id.png\" />[$imagesize KB]</a>\n";
			} else {
				$img = "<br />\n<a href=\"/img/bbs/$file_id\">[サムネイルがありません]<br />\n[$imagesize KB]</a>\n";
			}
		} else {
			$img = "<br />\n[画像が存在しません]\n";
		}
	} else {
		$img = "";
	}
?>
<div style="word-wrap:break-word; white-space:pre-wrap;">
[<?=$message->tmid?>] By <?=$message->name?><br />
<?=$message->comment?><br />
<?=$message->post_ts?><br />
IP:<?=$message->ip?><br />
HOSTNAME:<?=$message->hostname?><br />
UA:<?=$message->ua?><br />
UID:<?=$message->uid?><br />
USER ID:<?=$message->user_id?>
<?=$img?>
<?php
	if($message->tmid != 1) {
?>
<br />
<label><input type="checkbox" name="delmsg[]" value="<?=$message->tmid?>" />削除する</label>
<?php
	}
?>
</div>
<hr />
<?php
}
if($result->num_rows > 1) {
?>
<input type="hidden" name="id" value="<?=$board->name?>" />
<input type="hidden" name="tid" value="<?=$thread->tid?>" />
<input type="submit" value=" 削除 " />
<?php
}
?>
</form>
<ul style="list-style-type:none; text-align:right;">
<li><a href="./thlist.php?id=<?=$board->name?>"><?=$board->title?></a></li>
<li><a href="./">掲示板管理トップに戻る</a></li>
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
