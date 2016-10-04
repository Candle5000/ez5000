<?php
//=====================================
// 閲覧パス入力フォーム
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/functions/template.php");
session_start();

// 掲示板ID取得
if(!isset($_GET["id"])) die("ERROR01:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR02:無効なIDです");

// スレッドID取得
if(!isset($_GET["tid"])) die("ERROR03:IDがありません");
$tid = $_GET["tid"];
if(!is_numeric($tid)) die("ERROR04:無効なIDです");

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
if(!$result->num_rows) die("ERROR06:存在しないIDです");
$board = new Board($result->fetch_array());
$title = htmlspecialchars($board->title);

// スレッド情報を取得
$sql = "SELECT T.tid,T.subject,T.tindex,";
$sql .= "IF(LENGTH(T.readpass) > 0,TRUE,FALSE) isset_readpass,IF(LENGTH(T.writepass) > 0,TRUE,FALSE) isset_writepass,";
$sql .= "T.access_cnt,COUNT(1) message_cnt,T.update_ts,T.locked,T.top,T.next_tmid";
$sql .= " FROM thread T JOIN message M ON T.bid = M.bid AND T.tid = M.tid";
$sql .= " WHERE T.bid = '{$board->bid}' AND T.tid = '$tid' GROUP BY T.tid";
$result = $mysql->query($sql);
if($mysql->error || !$result->num_rows) die("ERROR07:存在しないIDです");
$thread = new Thread($result->fetch_array());

// リダイレクトURLの設定
$http = "http";
if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
$url = $http."://".$_SERVER["HTTP_HOST"];

// 閲覧パス設定無しならリダイレクト
if(!$thread->isset_readpass) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$url/bbs/read.php?id=$id&tid=$tid");
	exit;
}

// POST遷移時 認証確認
if($_SERVER["REQUEST_METHOD"] == "POST") {
	$readpass = $_POST["readpass"];
	$sql_readpass = $mysql->real_escape_string($readpass);
	$sql = "SELECT readpass=PASSWORD('$sql_readpass') is_matched FROM thread";
	$sql .= " WHERE bid='{$board->bid}' AND tid='{$thread->tid}'";
	$result = $mysql->query($sql);
	if($mysql->error || !$result->num_rows) die("ERROR08:存在しないIDです");
	if($result->fetch_object()->is_matched) {
		$_SESSION["read_auth"]["{$board->bid}"]["{$thread->tid}"] = true;
	} else {
		$error_list[] = "パスワードが違います";
	}
}

// 認証済みならリダイレクト
if(isset($_SESSION["read_auth"]["{$board->bid}"]["{$thread->tid}"])) {
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$url/bbs/read.php?id=$id&tid=$tid");
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
このスレッドを読むにはパスワードが必要です<br />
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
<form action="<?=$_SERVER["PHP_SELF"]."?id=$id&tid=$tid"?>" method="post" enctype="multipart/form-data">
<input name="readpass" type="password" maxlength="32" value="" />
<input type="submit" value=" 送信 " />
</form>
<hr class="normal">
<ul id="footlink">
<li><a href="/bbs/?id=<?=$board->name?>"<?=mbi_ack(9)?>><?=mbi("9.")?><?=$board->title?></a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot($thread->access_cnt);
?>
</div>
</body>
</html>
