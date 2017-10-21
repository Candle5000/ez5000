<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/bbs/class/board.php");

const ERRMSG001 = 'ERROR001: 不正なパラメータを検出しました。';
const ERRMSG002 = 'ERROR002: 不正なパラメータを検出しました。';
const ERRMSG003 = 'ERROR003: 不正なパラメータを検出しました。';
const ERRMSG101 = 'ERROR101: 掲示板データの取得に失敗しました。';
const ERRMSG102 = 'ERROR102: スレッドの取得に失敗しました。';
const ERRMSG901 = 'ERROR901: ファイル削除時にエラーが発生しました。';

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

	// 選択スレッドIDのチェック
	if(!is_array($_POST["delth"]) || count($_POST["delth"]) > PAGE_SIZE) die(print_error(ERRMSG002));

	// 選択無しの場合 一覧画面に戻りエラーメッセージを出力
	if(!isset($_POST["delth"])) {
		$target = "./thlist.php?id={$board->name}";
		$postArray[] = array("name" => "msgId", "value" => "1");
		jsPostSend($target, $postArray);
		exit;
	}

	// 選択IDリストを読み込み
	$tidList = array();
	foreach($_POST["delth"] as $tid) {
		if(!is_numeric($tid)) die(print_error(ERRMSG003));
		$tidList[] = $tid;
	}

	// スレッド情報の読み込み
	$bid = $board->bid;
	$tidListStr = implode($tidList, ", ");
	$sql = "SELECT T.tid, T.subject, M.name, M.comment, M.image, M.post_ts, M.update_ts, M.update_cnt, M.ip, M.hostname, M.ua, M.uid, M.user_id"
			." FROM thread T JOIN message M"
			." ON T.bid = M.bid AND T.tid = M.tid AND M.tmid = 1"
			." WHERE T.bid = $bid AND T.tid IN($tidListStr) ORDER BY T.tid";
	$result = $mysql->query($sql);

	// 取得したスレッド数が選択数に合わない場合 一覧画面に戻る
	if(count($_POST["delth"]) != $result->num_rows) {
		$target = "./thlist.php?id={$board->name}";
		$postArray[] = array("name" => "msgId", "value" => "2");
		jsPostSend($target, $postArray);
		exit;
	}

	// 削除処理
	if(isset($_POST["delete"])) {

		// トランザクションの開始
		$mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

		// メッセージ削除ログへINSERT
		$sql = <<<EOT
INSERT INTO message_deleted (
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
	user_id,
	guest_id,
	display_id)
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
		user_id,
		guest_id,
		display_id
	FROM message
	WHERE
		bid = $bid
		AND tid IN($tidListStr)
EOT;
		$mysql->query($sql);
		if($mysql->error) {
			$error = $mysql->error;
			$mysql->rollback();
			$target = "./thlist.php?id={$board->name}";
			$postArray[] = array("name" => "msgId", "value" => "3");
			jsPostSend($target, $postArray);
			exit;
		}

		// スレッド削除ログへINSERT
		$sql = <<<EOT
INSERT INTO thread_deleted (
	bid,
	tid,
	subject,
	tindex,
	readpass,
	writepass,
	access_cnt,
	update_ts,
	locked,
	top,
	next_tmid)
	SELECT
		bid,
		tid,
		subject,
		tindex,
		readpass,
		writepass,
		access_cnt,
		update_ts,
		locked,
		top,
		next_tmid
	FROM thread
	WHERE
		bid = $bid
		AND tid IN($tidListStr)
EOT;
		$mysql->query($sql);
		if($mysql->error) {
			$mysql->rollback();
			$target = "./thlist.php?id={$board->name}";
			$postArray[] = array("name" => "msgId", "value" => "4");
			jsPostSend($target, $postArray);
			exit;
		}

		// 添付ファイル削除用にメッセージを取得
		$sql = "SELECT * FROM message WHERE bid = $bid AND tid IN($tidListStr) AND image != ''";
		$msgResult = $mysql->query($sql);

		// メッセージを削除
		$sql = "DELETE FROM message WHERE bid = $bid AND tid IN($tidListStr)";
		$mysql->query($sql);
		if($mysql->error) {
			$mysql->rollback();
			$target = "./thlist.php?id={$board->name}";
			$postArray[] = array("name" => "msgId", "value" => "5");
			jsPostSend($target, $postArray);
			exit;
		}

		// スレッドを削除
		$sql = "DELETE FROM thread WHERE bid = $bid AND tid IN($tidListStr)";
		$mysql->query($sql);
		if($mysql->error) {
			$mysql->rollback();
			$target = "./thlist.php?id={$board->name}";
			$postArray[] = array("name" => "msgId", "value" => "6");
			jsPostSend($target, $postArray);
			exit;
		}

		// 添付ファイルを削除
		try {
			while($message = $msgResult->fetch_object()) {
				if($message->image != "") {
					$filename = "{$board->name}-{$message->tid}-{$message->tmid}-{$message->image}";
					if(file_exists("/var/www/img/bbs/$filename") && file_exists("/var/www/img/bbs/$filename.png")) {
						rename("/var/www/img/bbs/$filename", "/var/www/img/bbs/trash/$filename");
						rename("/var/www/img/bbs/$filename.png", "/var/www/img/bbs/trash/$filename.png");
					} else {
						throw new Exception("File Not Found : $filename");
					}
				}
			}
		} catch(Exception $e) {
			$mysql->rollback();
			die(print_error(ERRMSG901));
		}

		$mysql->commit();
		$target = "./thlist.php?id={$board->name}";
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
<div style="color:F00;">以下のスレッドを削除します。</div>
<hr />
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
<?php
while($thread = $result->fetch_object()) {
	$thread->subject = htmlspecialchars($thread->subject);
	$thread->name = htmlspecialchars($thread->name);
	$thread->comment = nl2br(htmlspecialchars($thread->comment));
	$thread->ua = htmlspecialchars($thread->ua);
	$img = ($thread->image == "") ? "" : "<br />\n画像添付あり";
?>
<div>
[<?=$thread->subject?>]<br />
By <?=$thread->name?><br />
<?=$thread->comment?><br />
<?=$thread->post_ts?><br />
IP:<?=$thread->ip?><br />
HOSTNAME:<?=$thread->hostname?><br />
UA:<?=$thread->ua?><br />
UID:<?=$thread->uid?><br />
USER ID:<?=$thread->user_id?>
<?=$img?>
<input type="hidden" name="delth[]" value="<?=$thread->tid?>" />
</div>
<hr />
<?php
}
?>
<input type="hidden" name="id" value="<?=$board->name?>" />
<input type="submit" name="delete" value=" 削除 " />
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
