<?php
//=====================================
// 管理報告フォーム
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/bbs/class/guestUser.php");
require_once("/var/www/bbs/class/memberUser.php");
require_once("/var/www/functions/template.php");
session_start();

const COMMENT_MAX_LENGTH = 1000;

// 掲示板ID取得
if(!isset($_GET["id"])) die("ERROR001:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR002:無効なIDです");

// スレッドID取得
if(!isset($_GET["tid"])) die("ERROR003:IDがありません");
$tid = $_GET["tid"];
if(!preg_match("/^[0-9]{1,9}$/", $tid)) die("ERROR004:無効なIDです");

// レス番号取得
if(!isset($_GET["tmid"])) die("ERROR005:IDがありません");
$tmid = $_GET["tmid"];
if(!preg_match("/^[0-9]{1,9}$/", $tmid)) die("ERROR006:無効なIDです");

// 送信先設定
$url = $_SERVER["PHP_SELF"]."?id=$id&tid=$tid&tmid=$tmid";
if(device_info() == 'mb' && !is_au()) $url .= "&guid=ON";

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

// クッキー有効確認
setcookie("cookiecheck", true, time() + 864000);
if(!isset($_COOKIE["cookiecheck"])) {
	$error_list[] = "クッキーを有効にしてください";
}

// docomo用
$guid_on = (device_info() == 'mb' && !is_au()) ? "&guid=ON" : "";

// ゲストログイン情報
$guest = new GuestUser($mysql);

// ユーザー情報取得
$ip = $_SERVER["REMOTE_ADDR"];
$ua = $mysql->real_escape_string($_SERVER["HTTP_USER_AGENT"]);
$hostname = $mysql->real_escape_string(gethostbyaddr($_SERVER['REMOTE_ADDR']));
if(isset($_SERVER['HTTP_X_DCMGUID'])) $uid = $mysql->real_escape_string($_SERVER['HTTP_X_DCMGUID']); // docomo
if(isset($_SERVER['HTTP_X_UP_SUBNO'])) $uid = $mysql->real_escape_string($_SERVER['HTTP_X_UP_SUBNO']); // au
if(isset($_SERVER['HTTP_X_JPHONE_UID'])) $uid = $mysql->real_escape_string($_SERVER['HTTP_X_JPHONE_UID']); // sb
if(!isset($uid)) $uid = "";

// ガラケーのみ ユーザー情報
$member = null;
if(device_info() == "mb") {
	$member = new MemberUser($mysql, "", "", $uid);
}

// 書き込み規制チェック
$ip_a = explode('.', $ip);
$pattern_sql = "'^".$ip_a[0].'\.('.$ip_a[1].'\.('.$ip_a[2].'\.('.$ip_a[3].")?)?)?\$'";
$sql = "SELECT 1 FROM bbs_ban WHERE (ip REGEXP $pattern_sql AND (IFNULL(ua, '') = '' OR ua = '$ua')) OR (IFNULL(ip, '') = '' AND ua = '$ua')";
if($guest->banned || $mysql->query($sql)->num_rows > 0) $error_list = array("投稿規制されています");

// 掲示板情報を取得
$sql = "SELECT * FROM `board` WHERE `name`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR101:存在しないIDです");
$board = new Board($result->fetch_array());

// スレッド情報を取得
$sql = "SELECT T.tid,subject,tindex,";
$sql .= "IF(LENGTH(T.readpass) > 0,TRUE,FALSE) isset_readpass,IF(LENGTH(T.writepass) > 0,TRUE,FALSE) isset_writepass,";
$sql .= "access_cnt,COUNT(1) message_cnt,update_ts,locked,top,next_tmid";
$sql .= " FROM (SELECT * FROM thread WHERE bid='{$board->bid}' AND tid='$tid') T";
$sql .= " JOIN (SELECT tid FROM message WHERE bid='{$board->bid}' AND tid='$tid') M";
$sql .= " ON T.tid=M.tid GROUP BY tid";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR102:存在しないIDです");
if(!$result->num_rows) die("ERROR103:存在しないIDです");
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

// メッセージ情報を取得
$sql = "SELECT `mid`,`tmid`,`name`,`comment`,`image`,`post_ts`,`update_ts`,`update_cnt`";
$sql .= " FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid'";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR106:存在しないIDです");
if(!$result->num_rows) die("ERROR107:メッセージが見つかりません");
$message = new Message($result->fetch_array(), $mysql, $board, $thread);

if($_SERVER["REQUEST_METHOD"] == "POST") {

	if(device_info() == "mb") {
		// 文字コード確認 フィーチャーフォンのみ
		if(isset($_POST["enc"])) {
			if($_POST["enc"] == "あ") {
				$enc_mode = 0;
			} else if(mb_convert_encoding($_POST["enc"], "UTF-8", "SJIS-WIN") == "あ") {
				$enc_mode = 1;
			} else if(urldecode($_POST["enc"]) == "あ") {
				$enc_mode = 2;
			} else if(mb_convert_encoding(urldecode($_POST["enc"]), "UTF-8", "SJIS-WIN") == "あ") {
				$enc_mode = 3;
			} else {
				$enc_mode = 0;
			}
		} else {
			$error_list[] = "文字コードの検出に失敗しました";
		}
	}

	// 報告内容取得
	$comment = isset($_POST["comment"]) ? $_POST["comment"] : "";
	if(mb_strlen($comment) > COMMENT_MAX_LENGTH) $error_list[] = "報告内容は".COMMENT_MAX_LENGTH."文字以内にしてください";
	if(device_info() == "mb") {
		if($enc_mode == 1) {
			$comment = mb_convert_encoding($comment, "UTF-8", "SJIS-WIN");
		} else if($enc_mode == 2) {
			$comment = urldecode($comment);
		} else if($enc_mode == 3) {
			$comment = mb_convert_encoding(urldecode($comment), "UTF-8", "SJIS-WIN");
		}
	}
	if($comment != "" && isset($_SESSION["report_comment"]) && $_SESSION["report_comment"] == $comment) $error_list[] = "同一内容の投稿は禁止されています";

	// 画像認証
	$is_mb = (device_info() == 'mb' && $uid != "");
	if(!$is_mb && (!isset($_SESSION['ImageAuthentication']) || !isset($_POST["authcap"]) || ($_SESSION["ImageAuthentication"] != $_POST["authcap"]))) $error_list[] = "画像認証コードが一致しません";

	// エラーがない場合、登録処理を開始
	if(!isset($error_list)) {
		$sql_comment = $mysql->real_escape_string($comment);

		// トランザクションの開始
		$mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

		$sql = "INSERT INTO report (bid, mid, comment, ts, ip, hostname, ua, uid, guest_id)";
		$sql .= " VALUES ('{$board->bid}', '{$message->mid}', '$sql_comment', NOW(), '$ip', '$hostname', '$ua', '$uid', '{$guest->id}')";
		$mysql->query($sql);
		if($error = $mysql->error) {
			$mysql->rollback();
			die("ERROR201:クエリ処理に失敗しました".$error);
		}
		$mysql->commit();

		// 同一内容投稿防止
		if($mode == 0 || $mode == 1) $_SESSION["report_comment"] = $comment;

		// 投稿後に画像認証情報をリセット
		if(isset($_SESSION['ImageAuthentication'])) unset($_SESSION['ImageAuthentication']);
	}
} else {
	// 初期表示時の設定
	$comment = "";
}

// h2設定
if($_SERVER["REQUEST_METHOD"] != "POST" || isset($error_list)) {
	$title = "管理に報告";
} else {
	$title = "送信完了";
}
?>
<html>
<head>
<?=pagehead($board->title)?>
</head>
<body>
<div id="all">
<h1><?=$board->title?></h1>
<hr class="normal">
<h2><?=$title?></h2>
<?php
if($_SERVER["REQUEST_METHOD"] != "POST" || isset($error_list)) {
	// 入力エラーリスト表示
	if(isset($error_list)) {
?>
<div class="nc6">
<?=implode("<br />\n", $error_list)?>
</div>
<hr class="normal">
<?php
	}
?>
以下のメッセージを管理に報告します。
<div class="nc6">
送信内容は送信者情報と共に記録されます。<br />
不適切な報告が繰り返し送信された場合には規制対象となる場合があるのでご注意ください。
</div>
<hr class="normal">
<p>スレッド:<?=htmlspecialchars($thread->subject)?></p>
<hr class="message">
<?php
$message->printArchiveMessage();
?>
<hr class="normal">
<form action="<?=$url?>" method="post" enctype="multipart/form-data">
報告内容<br />
<textarea id="comment" name="comment" wrap="virtual"><?=$comment?></textarea><br />
<hr class="normal">
<?php
	if(device_info() == "mb") {
?>
<input type="hidden" name="enc" value="あ">
<?php
	} else {
?>
<img src="ImageAuthentication.php" /><br />
画像認証-上記文字を入力してください<br />
<input type="text" name="authcap">
<hr class="normal">
<?php
	}
?>
<br />
<input type="submit" value=" 送信 ">
</form>
<?php
} else {
	if($comment == "") $comment = "コメントなし";
?>
以下の内容で送信しました。
<hr class="normal">
<p><?=nl2br(htmlspecialchars($comment))?></p>
<hr class="normal">
<p>スレッド:<?=htmlspecialchars($thread->subject)?></p>
<hr class="message">
<?php
$message->printArchiveMessage();
?>
<?php
}
?>
<hr class="normal">
<ul id="footlink">
<li><a href="/bbs/read.php?id=<?=$board->name?>&tid=<?=$thread->tid.$guid_on?>"<?=mbi_ack(8)?>><?=mbi("8.")?>スレッドに戻る</a></li>
<li><a href="/bbs/?id=<?=$board->name.$guid_on?>"<?=mbi_ack(9)?>><?=mbi("9.").$board->title?></a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
