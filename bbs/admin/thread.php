<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/input_check.php");
require_once("/var/www/bbs/class/board.php");

const ERRMSG001 = 'ERROR001: 不正なパラメータを検出しました。';
const ERRMSG002 = 'ERROR002: 不正なパラメータを検出しました。';
const ERRMSG101 = 'ERROR101: 掲示板データの取得に失敗しました。';
const ERRMSG102 = 'ERROR102: スレッドの取得に失敗しました。';

session_start();

// ログイン情報の確認
if(!isset($_SESSION["admin_auth"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
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

// 入力エラーリスト
$error_list = array();

// GET入力エラーチェック
if(!isset($_GET["id"]) || !preg_match('/^[a-zA-Z0-9]{4,16}$/', $_GET["id"])) die(print_error(ERRMSG001));
if(!isset($_GET["tid"]) || !is_numeric($_GET["tid"]) || $_GET["tid"] < 1) die(print_error(ERRMSG002));

// 掲示板の読み込み
$sql = "SELECT * FROM board WHERE name = '{$_GET["id"]}'";
$result = $mysql->query($sql);
if($result->num_rows != 1) die(print_error(ERRMSG101));
$board = new Board($result->fetch_array());

// スレッド情報の読み込み
$sql = "SELECT tid, subject, locked, top FROM thread WHERE bid = {$board->bid} AND tid = {$_GET["tid"]}";
$result = $mysql->query($sql);
if($result->num_rows != 1) die(print_error(ERRMSG102));
$thread = $result->fetch_object();

// POST送信時
if($_SERVER["REQUEST_METHOD"] == "POST") {

	// 入室パス設定許可のチェック
	$thread->locked = isset($_POST["locked"]);

	// 書込パス設定許可のチェック
	$thread->top = isset($_POST["top"]);

	// 登録処理
	if(count($error_list) == 0) {
		$bid = $board->bid;
		$tid = $thread->tid;
		$locked = $thread->locked ? "TRUE" : "FALSE";
		$top = $thread->top ? "TRUE" : "FALSE";
		$sql = "UPDATE thread SET locked = $locked, top = $top WHERE bid = '$bid' AND tid = '$tid'";
		$mysql->query($sql);
		if($mysql->error) {
			$error_list[] = "データの更新に失敗しました。";
		} else {
			$http = "http";
			if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
			header("HTTP/1.1 301 Moved Permanently");
			header("Pragma: no-cache");
			header("Location:$http://".$_SERVER["HTTP_HOST"]."/bbs/admin/thread.php?id=".$board->name."&tid=".$thread->tid);
			exit;
		}
	}
}

// チェックボックスの設定
$lk_checked = $thread->locked ? "checked " : "";
$tp_checked = $thread->top ? "checked " : "";
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<h4>スレッド設定の編集</h4>
<div>[<?=$thread->tid?>]<?=htmlspecialchars($thread->subject)?></div>
<?php
foreach($error_list as $error) {
?>
<div style="color:#F00;"><?=$error?></div>
<?php
}
?>
<hr />
<form action="<?=$_SERVER["PHP_SELF"]."?id=".$_GET["id"]."&tid=".$_GET["tid"]?>" method="POST">
<label><input type="checkbox" name="locked" value="true" <?=$lk_checked?>/>スレッドをロックする</label><br />
<label><input type="checkbox" name="top" value="true" <?=$tp_checked?>/>スレッドを上部固定にする</label><br />
<br />
<input type="submit" value=" 編集 " />
</form>
<hr />
<ul style="list-style-type:none; text-align:right;">
<li><a href="./thlist.php?id=<?=$board->name?>"><?=$board->title?></a></li>
<li><a href="./">掲示板管理トップに戻る</a></li>
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
