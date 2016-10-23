<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/input_check.php");
require_once("/var/www/bbs/class/board.php");

const ERRMSG001 = 'ERROR001: 不正なパラメータを検出しました。';
const ERRMSG101 = 'ERROR101: 掲示板データの取得に失敗しました。';

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

// 入力エラーリスト
$error_list = array();

// GET入力エラーチェック
if(!isset($_GET["id"]) || !preg_match('/^[a-zA-Z0-9]{4,16}$/', $_GET["id"])) die(print_error(ERRMSG001));

// 掲示板の読み込み
$sql = "SELECT * FROM board WHERE name = '{$_GET["id"]}'";
$result = $mysql->query($sql);
if($result->num_rows != 1) die(print_error(ERRMSG101));
$board = new Board($result->fetch_array());

// POST送信時
if($_SERVER["REQUEST_METHOD"] == "POST") {

	// 掲示板IDのチェック
	if(is_empty($_POST["name"])) {
		$error_list[] = "掲示板IDが入力されていません。";
		$board->name = "";
	} else if(!preg_match('/^[a-zA-Z0-9]{4,16}$/', trim($_POST["name"]))) {
		$error_list[] = "掲示板IDは半角英数字で4～16文字を入力してください。";
		$board->name = trim($_POST["name"]);
	}

	// 掲示板タイトルのチェック
	if(is_empty($_POST["title"])) {
		$error_list[] = "掲示板タイトルが入力されていません。";
		$board->title = "";
	} else if(mb_strlen(trim_all($_POST["title"])) < 4 || mb_strlen(trim_all($_POST["title"])) > 32) {
		$error_list[] = "掲示板タイトルは4～32文字を入力してください。";
		$board->title = trim($_POST["title"]);
	}

	// 入室パス設定許可のチェック
	$board->allow_readpass = isset($_POST["allow_readpass"]);

	// 書込パス設定許可のチェック
	$board->allow_writepass = isset($_POST["allow_writepass"]);

	// 投稿者名の文字数制限
	$name_max = 30; // 名無し設定用
	if(is_empty($_POST["name_max"])) {
		$error_list[] = "投稿者名の文字数制限が入力されていません。";
		$board->name_max = "";
	} else if(!is_numeric($_POST["name_max"]) || $_POST["name_max"] < 8 || $_POST["name_max"] > 30) {
		$error_list[] = "投稿者名の文字数制限は8～30の数値を入力してください。";
		$board->name_max = trim($_POST["name_max"]);
	} else {
		$name_max = $_POST["name_max"];
	}

	// スレッドタイトルの文字数制限
	if(is_empty($_POST["subject_max"])) {
		$error_list[] = "スレッドタイトルの文字数制限が入力されていません。";
		$board->subject_max = "";
	} else if(!is_numeric($_POST["subject_max"]) || $_POST["subject_max"] < 8 || $_POST["subject_max"] > 40) {
		$error_list[] = "スレッドタイトルの文字数制限は8～40の数値を入力してください。";
		$board->subject_max = trim($_POST["subject_max"]);
	}

	// メッセージ本文の文字数制限
	if(is_empty($_POST["comment_max"])) {
		$error_list[] = "メッセージ本文の文字数制限が入力されていません。";
		$board->comment_max = "";
	} else if(!is_numeric($_POST["comment_max"]) || $_POST["comment_max"] < 128 || $_POST["comment_max"] > 4096) {
		$error_list[] = "メッセージ本文の文字数制限は128～4096の数値を入力してください。";
		$board->comment_max = trim($_POST["comment_max"]);
	}

	// 名無し設定
	if(is_empty($_POST["default_name"])) {
		$board->default_name = "";
	} else if(mb_strlen(trim_all($_POST["default_name"])) > $name_max) {
		$error_list[] = "デフォルト投稿者名が文字数制限".$name_max."を満たしていません。";
		$board->default_name = trim($_POST["default_name"]);
	}

	// スレッド連続作成の制限
	if(is_empty($_POST["thpost_limit"])) {
		$error_list[] = "スレッド連続作成の制限が入力されていません。";
		$board->thpost_limit = "";
	} else if(!is_numeric($_POST["thpost_limit"]) || $_POST["thpost_limit"] < 0 || $_POST["thpost_limit"] > 600) {
		$error_list[] = "スレッド連続作成の制限は0～600の数値を入力してください。";
		$board->thpost_limit = trim($_POST["thpost_limit"]);
	}

	// 連続投稿の制限
	if(is_empty($_POST["repost_limit"])) {
		$error_list[] = "連続投稿の制限が入力されていません。";
		$board->repost_limit = "";
	} else if(!is_numeric($_POST["repost_limit"]) || $_POST["repost_limit"] < 0 || $_POST["repost_limit"] > 600) {
		$error_list[] = "連続投稿の制限は0～600の数値を入力してください。";
		$board->repost_limit = trim($_POST["repost_limit"]);
	}
}

// チェックボックスの設定
$rp_checked = $board->allow_readpass ? "checked " : "";
$wp_checked = $board->allow_writepass ? "checked " : "";
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<h4>掲示板設定の編集</h4>
<?php
foreach($error_list as $error) {
?>
<div style="color:#F00;"><?=$error?></div>
<?php
}
?>
<hr />
<form action="<?=$_SERVER["PHP_SELF"]."?id=".$_GET["id"]?>" method="POST">
掲示板ID [半角英数字4-16文字]<br />
<input type="text" name="name" value="<?=$board->name?>" maxlength="16" size="20" /><br />
<br />
掲示板タイトル [4-32文字]<br />
<input type="text" name="title" value="<?=$board->title?>" maxlength="32" size="72" /><br />
<br />
<label><input type="checkbox" name="allow_readpass" value="true" <?=$rp_checked?>/>入室パス設定を許可する</label><br />
<label><input type="checkbox" name="allow_writepass" value="true" <?=$wp_checked?>/>書込パス設定を許可する</label><br />
<br />
投稿者名の文字数制限 [8-30]<br />
<input type="number" name="name_max" value="<?=$board->name_max?>" min="8" max="30" /><br />
<br />
スレッドタイトルの文字数制限 [8-40]<br />
<input type="number" name="subject_max" value="<?=$board->subject_max?>" min="8" max="40" /><br />
<br />
メッセージ本文の文字数制限 [128-4096]<br />
<input type="number" name="comment_max" value="<?=$board->comment_max?>" min="128" max="4096" /><br />
<br />
デフォルト投稿者名 [投稿者名の文字数制限以内/空にすると名前を必須にする]<br />
<input type="text" name="default_name" value="<?=$board->default_name?>" maxlength="30" size="64" /><br />
<br />
スレッド連続作成の制限 [0-600(秒)]<br />
<input type="number" name="thpost_limit" value="<?=$board->thpost_limit?>" min="0" max="600" />秒<br />
<br />
連続投稿の制限 [0-600(秒)]<br />
<input type="number" name="repost_limit" value="<?=$board->repost_limit?>" min="0" max="600" />秒<br />
<br />
<input type="submit" value=" 編集 " />
</form>
<hr />
<ul style="list-style-type:none; text-align:right;">
<li><a href="./">掲示板管理トップに戻る</a></li>
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
