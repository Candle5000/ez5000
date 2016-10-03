<?php
//=====================================
// 書込パス入力フォーム
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/functions/template.php");
session_start();

// モードを取得
if(!isset($_GET["mode"])) die("ERROR001:モードが設定されていません");
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
		die("ERROR002:無効なモードです");
		break;
}

// 掲示板ID取得
if(!isset($_GET["id"])) die("ERROR03:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR04:無効なIDです");

// スレッドID取得
if(!isset($_GET["tid"])) die("ERROR05:IDがありません");
$tid = $_GET["tid"];
if(!is_numeric($tid)) die("ERROR06:無効なIDです");

// レス番号取得 編集モードのみ
if($mode == 2) {
	if(!isset($_GET["tmid"])) die("ERROR007:IDがありません");
	$tmid = $_GET["tmid"];
	if(!preg_match("/^[0-9]{1,9}$/", $tmid)) die("ERROR008:無効なIDです");
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

// 掲示板情報を取得
$sql = "SELECT * FROM `board` WHERE `name`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR11:存在しないIDです");
$board = new Board($result->fetch_array());
$title = htmlspecialchars($board->title);

// スレッド情報を取得
$sql = "SELECT T.tid,T.subject,T.tindex,";
$sql .= "IF(LENGTH(T.readpass) > 0,TRUE,FALSE) isset_readpass,IF(LENGTH(T.writepass) > 0,TRUE,FALSE) isset_writepass,";
$sql .= "T.access_cnt,COUNT(1) message_cnt,T.update_ts,T.locked,T.top,T.next_tmid";
$sql .= " FROM thread T JOIN message M ON T.bid = M.bid AND T.tid = M.tid";
$sql .= " WHERE T.bid = '{$board->bid}' AND T.tid = '$tid' GROUP BY T.tid";
$result = $mysql->query($sql);
if($mysql->error || !$result->num_rows) die("ERROR12:存在しないIDです");
$thread = new Thread($result->fetch_array());

// 閲覧パスの確認
if($thread->isset_readpass && !isset($_SESSION["read_auth"]["{$board->bid}"]["{$thread->tid}"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/readpass.php?id=$id&tid=$tid");
	exit;
}

// リダイレクトURLの設定
$http = "http";
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
$url = $http."://".$_SERVER["HTTP_HOST"];
$tmid = ($mode == 2) ? "&tmid=$tmid" : "";
$re = ($mode == 1 && $re != 0) ? "&re=$re" : "";

// 書込パス設定無しならリダイレクト
if(!$thread->isset_writepass) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$url/bbs/form.php?mode={$_GET["mode"]}&id=$id&tid=$tid$tmid$re");
	exit;
}

// POST遷移時 認証確認
if($_SERVER["REQUEST_METHOD"] == "POST") {
	$writepass = $_POST["writepass"];
	$sql_writepass = $mysql->real_escape_string($writepass);
	$sql = "SELECT writepass=PASSWORD('$sql_writepass') is_matched FROM thread";
	$sql .= " WHERE bid='{$board->bid}' AND tid='{$thread->tid}'";
	$result = $mysql->query($sql);
	if($mysql->error || !$result->num_rows) die("ERROR13:存在しないIDです");
	if($result->fetch_object()->is_matched) {
		$_SESSION["write_auth"]["{$board->bid}"]["{$thread->tid}"] = true;
	} else {
		$error_list[] = "パスワードが違います";
	}
}

// 認証済みならリダイレクト
if(isset($_SESSION["write_auth"]["{$board->bid}"]["{$thread->tid}"])) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$url/bbs/form.php?mode={$_GET["mode"]}&id=$id&tid=$tid$tmid$re");
	exit;
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
このスレッドに書き込むにはパスワードが必要です<br />
<?php
// 入力エラーリスト表示
if(isset($error_list)) {
?>
<div class="nc6">
<?=implode("<br />\n", $error_list)?>
</div>
<?php
}
?>
<form action="<?=$_SERVER["PHP_SELF"]."?mode={$_GET["mode"]}&id=$id&tid=$tid$tmid$re"?>" method="post" enctype="multipart/form-data">
<input name="writepass" type="password" maxlength="32" value="" />
</form>
<hr class="normal">
<ul id="footlink">
<li><a href="/bbs/read.php?id=<?=$board->name?>&tid=<?=$thread->tid?>"<?=mbi_ack(8)?>><?=mbi("8.")?>スレッドに戻る</a></li>
<li><a href="/bbs/?id=<?=$board->name?>"<?=mbi_ack(9)?>><?=mbi("9.")?><?=$board->title?></a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot($thread->access_cnt);
?>
</div>
</body>
</html>
