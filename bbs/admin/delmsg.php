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
const ERRMSG004 = 'ERROR004: 不正なパラメータを検出しました。';
const ERRMSG101 = 'ERROR101: 掲示板データの取得に失敗しました。';
const ERRMSG102 = 'ERROR102: スレッドの取得に失敗しました。';
const ERRMSG103 = 'ERROR103: ファイル削除時にエラーが発生しました。';

const PAGE_SIZE = 50;

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

// POST送信時
if($_SERVER["REQUEST_METHOD"] == "POST") {

	// 掲示板IDのチェック
	if(!isset($_POST["id"]) || !preg_match('/^[a-zA-Z0-9]{4,16}$/', $_POST["id"])) die(print_error(ERRMSG001));

	// 掲示板の読み込み
	$sql = "SELECT * FROM board WHERE name = '{$_POST["id"]}'";
	$result = $mysql->query($sql);
	if($result->num_rows != 1) die(print_error(ERRMSG101));
	$board = new Board($result->fetch_array());

	// スレッドIDのチェック
	if(!isset($_POST["tid"]) || !is_numeric($_POST["tid"]) || $_POST["tid"] < 1) die(print_error(ERRMSG002));

	// スレッド情報の読み込み
	$sql = "SELECT tid, subject FROM thread WHERE bid = {$board->bid} AND tid = {$_POST["tid"]}";
	$result = $mysql->query($sql);
	if($result->num_rows != 1) die(print_error(ERRMSG102));
	$thread = $result->fetch_object();

	// 選択無しの場合 一覧画面に戻りエラーメッセージを出力
	if(!isset($_POST["delmsg"])) {
		$target = "./read.php?id={$board->name}&tid={$thread->tid}";
		$postArray[] = array("name" => "msgId", "value" => "1");
		jsPostSend($target, $postArray);
		exit;
	}

	// 選択メッセージIDのチェック
	if(!is_array($_POST["delmsg"]) || count($_POST["delmsg"]) > PAGE_SIZE) die(print_error(ERRMSG003));

	// 選択IDリストを読み込み
	$tmidList = array();
	foreach($_POST["delmsg"] as $tmid) {
		if(!is_numeric($tmid)) die(print_error(ERRMSG004));
		$tmidList[] = $tmid;
	}
	$bid = $board->bid;
	$tmidListStr = implode($tmidList, ", ");
	$tid = $thread->tid;
	$sql = "SELECT * FROM message WHERE bid = $bid AND tid = $tid AND tmid IN($tmidListStr) ORDER BY tmid";
	$result = $mysql->query($sql);

	// 取得したメッセージ数が選択数に合わない場合 一覧画面に戻る
	if(count($_POST["delmsg"]) != $result->num_rows) {
		$target = "./read.php?id={$board->name}&tid={$thread->tid}";
		$postArray[] = array("name" => "msgId", "value" => "2");
		jsPostSend($target, $postArray);
		exit;
	}

	// 削除処理
	if(isset($_POST["delete"])) {

		// トランザクションの開始
		$mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

		// 削除ログへINSERT
		$sql = <<<EOT
INSERT INTO message_deleted
	SELECT
		bid,
		mid,
		tid,
		tmid,
		name,
		comment,
		image,
		post_ts,
		update_ts,
		update_cnt,
		ip,
		hostname,
		ua,
		uid,
		user_id
	FROM message
	WHERE
		bid = $bid
		AND tid = $tid
		AND tmid IN($tmidListStr)
EOT;
		$mysql->query($sql);
		if($mysql->error) {
			$mysql->rollback();
			$target = "./read.php?id={$board->name}&tid={$thread->tid}";
			$postArray[] = array("name" => "msgId", "value" => "3");
			jsPostSend($target, $postArray);
			exit;
		}

		// メッセージを削除
		$sql = "DELETE FROM message WHERE bid = $bid AND tid = $tid AND tmid IN($tmidListStr)";
		$mysql->query($sql);
		if($mysql->error) {
			$mysql->rollback();
			$target = "./read.php?id={$board->name}&tid={$thread->tid}";
			$postArray[] = array("name" => "msgId", "value" => "4");
			jsPostSend($target, $postArray);
			exit;
		}

		// 添付ファイルを削除
		try {
			while($message = $result->fetch_object()) {
				if($message->image != "") {
					$filename = "{$board->name}-$tid-$tmid-{$message->image}";
					if(file_exists("/var/www/img/bbs/$filename")) rename("/var/www/img/bbs/$filename", "/var/www/img/bbs/trash/$filename");
					if(file_exists("/var/www/img/bbs/$filename.png")) rename("/var/www/img/bbs/$filename.png", "/var/www/img/bbs/trash/$filename.png");
				}
			}
		} catch(Exception $e) {
			$mysql->rollback();
			die(print_error(ERRMSG901));
		}

		$mysql->commit();
		$target = "./read.php?id={$board->name}&tid={$thread->tid}";
		$postArray[] = array("name" => "msgId", "value" => "0");
		jsPostSend($target, $postArray);
		exit;
	}
} else {
	// POSTでない場合 管理メニューへ
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/admin/index.php");
	exit;
}
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<div>掲示板 : [<?=$board->name?>]<?=$board->title?></div>
<div>スレッド : [<?=$thread->tid?>]<?=$thread->subject?></div>
<div style="color:F00;">以下のメッセージを削除します。</div>
<hr />
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
<?php
while($array = $result->fetch_array()) {
	$message = new Message($array, $mysql, $board, $thread);
	$message->name = htmlspecialchars($message->name);
	$message->comment = nl2br(htmlspecialchars($message->comment));
	$message->ua = htmlspecialchars($message->ua);
?>
<div>
[<?=$message->tmid?>] By <?=$message->name?><br />
<?=$message->comment?><br />
<?=$message->post_ts?><br />
IP:<?=$message->ip?><br />
HOSTNAME:<?=$message->hostname?><br />
UA:<?=$message->ua?><br />
UID:<?=$message->uid?><br />
USER ID:<?=$message->user_id?>
<input type="hidden" name="delmsg[]" value="<?=$message->tmid?>" />
</div>
<hr />
<?php
}
?>
<input type="hidden" name="id" value="<?=$board->name?>" />
<input type="hidden" name="tid" value="<?=$thread->tid?>" />
<input type="submit" name="delete" value=" 削除 " />
</form>
<hr />
<ul style="list-style-type:none; text-align:right;">
<li><a href="./read.php?id=<?=$board->name?>&tid=<?=$thread->tid?>"><?=$thread->subject?></a></li>
<li><a href="./thlist.php?id=<?=$board->name?>"><?=$board->title?></a></li>
<li><a href="./">掲示板管理トップに戻る</a></li>
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
