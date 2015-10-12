<?php
//=====================================
// 書き込みフォーム
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/functions/template.php");
session_start();

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

// 送信先設定
$url = $_SERVER["PHP_SELF"]."?mode={$_GET["mode"]}&id=$id";
if($mode == 1 || $mode == 2) $url .= "&tid=$tid";
if($mode == 2) $url .= "&tmid=$tmid";

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
	$sql = "SELECT * FROM `{$id}_t` WHERE `tid`='$tid'";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR12:存在しないIDです");
	if(!$result->num_rows) die("ERROR13:存在しないIDです");
	$thread = new Thread($result->fetch_array());
	if($thread->mcount > 999) die("ERROR14:スレッドの投稿数が上限に達しています");
}

// メッセージ情報を取得 編集モードのみ
if($mode == 2) {
	$sql = "SELECT * FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`='$tmid'";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR15:存在しないIDです");
	if(!$result->num_rows) die("ERROR16:メッセージが見つかりません");
	$message = new Message($result->fetch_array());
}

if($_SERVER["REQUEST_METHOD"] == "POST") {

	// 文字コード確認 フィーチャーフォンのみ
	if(device_info() == "mb") {
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

	// 名前取得
	$name = isset($_POST["name"]) ? $_POST["name"] : "";
	if($name == "") {
		if($boad->default_name != "") {
			$name = $boad->default_name;
		} else {
			$error_list[] = "お名前が空です";
		}
	} else {
		if(device_info() == "mb") {
			if($enc_mode == 1) {
				$name = mb_convert_encoding($name, "UTF-8", "SJIS-WIN");
			} else if($enc_mode == 2) {
				$name = urldecode($name);
			} else if($enc_mode == 3) {
				$name = mb_convert_encoding(urldecode($name), "UTF-8", "SJIS-WIN");
			}
		}
		if(mb_strlen($name) > 30) $error_list[] = "お名前は30文字以内にしてください";
	}

	// タイトル取得 スレッド作成/編集のみ
	$title = (($mode == 0 || ($mode == 2 && $tmid == 1)) && $_POST["sbj"]) ? $_POST["sbj"] : "";
	if(($mode == 0 || ($mode == 2 && $tmid == 1)) && $title == "") $error_list[] = "タイトルが空です";
	if(device_info() == "mb") {
		if($enc_mode == 1) {
			$title = mb_convert_encoding($title, "UTF-8", "SJIS-WIN");
		} else if($enc_mode == 2) {
			$title = urldecode($title);
		} else if($enc_mode == 3) {
			$title = mb_convert_encoding(urldecode($title), "UTF-8", "SJIS-WIN");
		}
	}
	if(mb_strlen($title) > 40) $error_list[] = "タイトルは40文字以内にしてください";

	// 本文取得
	$comment = isset($_POST["comment"]) ? $_POST["comment"] : "";
	if($comment == "") $error_list[] = "本文が空です";
	if(mb_strlen($comment) > 4096) $error_list[] = "本文は4096文字以内にしてください";
	if(device_info() == "mb") {
		if($enc_mode == 1) {
			$comment = mb_convert_encoding($comment, "UTF-8", "SJIS-WIN");
		} else if($enc_mode == 2) {
			$comment = urldecode($comment);
		} else if($enc_mode == 3) {
			$comment = mb_convert_encoding(urldecode($comment), "UTF-8", "SJIS-WIN");
		}
	}
	if(($mode == 0 || $mode == 1) && isset($_SESSION["comment"]) && $_SESSION["comment"] == $comment) $error_list[] = "同一内容の投稿は禁止されています";

	// sage取得 返信モードのみ
	$sage = ($mode == 1 && isset($_POST["sage"]) && $_POST["sage"] == "sage");

	// 編集パスワード取得
	$pass = isset($_POST["pass"]) ? $_POST["pass"] : "";
	if($pass == "") $error_list[] = "パスワードが空です";
	if(!preg_match("/^[!-~]{4,64}$/", $pass)) $error_list[] = "パスワードは半角英数字と記号のみで4～64文字にしてください";

	// 連投チェック
	if($mode == 0 && isset($_SESSION["thposttime"]) && ($_SESSION["thposttime"] > time())) $error_list[] = "300秒間は連続でスレッドを作成できません";
	if($mode == 1 && isset($_SESSION["reposttime"]) && ($_SESSION["reposttime"] > time())) $error_list[] = "60秒間は連続で返信を投稿できません";

	// ユーザー情報取得
	$ip = $_SERVER["REMOTE_ADDR"];
	$ua = $_SERVER["HTTP_USER_AGENT"];
	if(isset($_SERVER['HTTP_X_DCMGUID'])) $uid = $_SERVER['HTTP_X_DCMGUID']; // docomo
	if(isset($_SERVER['HTTP_X_UP_SUBNO'])) $uid = $_SERVER['HTTP_X_UP_SUBNO']; // au
	if(isset($_SERVER['HTTP_X_JPHONE_UID'])) $uid = $_SERVER['HTTP_X_JPHONE_UID']; // sb
	if(!isset($uid)) $uid = "";

	if(!isset($error_list)) {
		if($mode == 0 || $mode == 1) $_SESSION["comment"] = $comment;
		$sql_title = $mysql->real_escape_string($title);
		$sql_name = $mysql->real_escape_string($name);
		$sql_comment = $mysql->real_escape_string($comment);
		$sql_pass = $mysql->real_escape_string($pass);

		switch($mode) {

			case 0: // スレッド作成
				$sql = "INSERT INTO `{$id}_t` (`title`, `tindex`, `mcount`, `updated`) SELECT '{$sql_title}' AS `title`, MAX(`tindex`)+1 AS `tindex`, '1' AS `mcount`, NOW() AS `updated` FROM `{$id}_t`";
				$mysql->query($sql);
				if($mysql->error) die("ERROR21:クエリ処理に失敗しました");
				$sql = "INSERT INTO `{$id}_m` (`tid`, `tmid`, `name`, `comment`, `password`, `ts`, `ip`, `ua`, `uid`) VALUES (LAST_INSERT_ID(), '1', '$sql_name', '$sql_comment', PASSWORD('$sql_pass'), NOW(), '$ip', '$ua', '$uid')";
				$mysql->query($sql);
				if($mysql->error) die("ERROR22:クエリ処理に失敗しました");
				if($name != $boad->default_name) setcookie("bbs_name", $name, time() + 604800);
				$_SESSION["thposttime"] = time() + 300;
				break;

			case 1: // 返信投稿
				$sql = "INSERT INTO `{$id}_m` (`tid`, `tmid`, `name`, `comment`, `password`, `ts`, `ip`, `ua`, `uid`) SELECT '$tid' AS `tid`, `mcount`+1 AS `tmid`, '$sql_name' AS `name`, '$sql_comment' AS `comment`, PASSWORD('$sql_pass') AS `password`, NOW() AS `ts`, '$ip' AS `ip`, '$ua' AS `ua`, '$uid' AS `uid` FROM `{$id}_t` WHERE `tid`='$tid'";
				$mysql->query($sql);
				if($mysql->error) die("ERROR23:クエリ処理に失敗しました");
				if($sage) {
					$sql = "UPDATE `{$id}_t` SET `mcount`=`mcount`+1, `updated`=NOW() WHERE `tid`='$tid'";
				} else {
					$sql = "UPDATE `{$id}_t`, (SELECT MAX(`tindex`)+1 AS `tindex_max` FROM `{$id}_t`) AS `thread` SET `tindex`=`thread`.`tindex_max`, `mcount`=`mcount`+1, `updated`=NOW() WHERE `tid`='$tid'";
				}
				$mysql->query($sql);
				if($mysql->error) die("ERROR24:クエリ処理に失敗しました");
				if($name != $boad->default_name) setcookie("bbs_name", $name, time() + 604800);
				$_SESSION["reposttime"] = time() + 60;
				break;

			case 2: // メッセージ編集
				$sql = "SELECT `password`=PASSWORD('$sql_pass') AS `match` FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`='$tmid' AND `mid`='{$message->mid}'";
				$result = $mysql->query($sql);
				if($mysql->error) die("ERROR25:クエリ処理に失敗しました");
				if(!$result->num_rows) die("ERROR26:メッセージが見つかりません");
				$array = $result->fetch_array();
				if($array["match"]) {
					$sql = "UPDATE `{$id}_m` SET `name`='$sql_name', `comment`='$sql_comment', `ip`='$ip', `ua`='$ua', `uid`='$uid' WHERE `tid`='$tid' AND `tmid`='$tmid' AND `mid`='{$message->mid}' AND `password`=PASSWORD('$pass')";
					$mysql->query($sql);
					if($mysql->error) die("ERROR27:クエリ処理に失敗しました");
					if($tmid == 1) {
						$sql = "UPDATE `{$id}_t` SET `title`='$sql_title' WHERE `tid`='$tid'";
						$mysql->query($sql);
						if($mysql->error) die("ERROR28:クエリ処理に失敗しました");
					}
					if($name != $boad->default_name) setcookie("bbs_name", $name, time() + 604800);
				} else {
					$error_list[] = "パスワードが間違っています";
				}
				break;
		}
	}
}

// フォーム内容
if(!($_SERVER["REQUEST_METHOD"] == "POST")) {
	if($mode != 2) {
		$name = isset($_COOKIE["bbs_name"]) ? $_COOKIE["bbs_name"] : "";
		$subject = "";
		$comment = (isset($re) && $re != 0) ? ">>$re" : "";
	} else {
		$name = $message->name;
		$subject = $thread->title;
		$comment = $message->comment;
	}
} else if(isset($error_list)) {
	$subject = $title;
}

// h2設定
if(!($_SERVER["REQUEST_METHOD"] == "POST") || isset($error_list)) {
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
} else {
	$title = "送信完了";
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
<?php
if(!($_SERVER["REQUEST_METHOD"] == "POST") || isset($error_list)) {
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
<form action="<?=$url?>" method="post" enctype="multipart/form-data">
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
<textarea id="comment" name="comment" wrap="virtual"><?=$comment?></textarea><br />
<?php
	if($mode == 1) {
?>
<select name="sage">
<option value="age">スレッドを上げる</option>
<option value="sage">スレッドを上げない</option>
</select><br />
<?php
	}
?>
編集パス<br />
<input type="password" name="pass" maxlength="32" value=""><br />
<hr class="normal">
<?php
	if(device_info() == "mb") {
?>
<input type="hidden" name="enc" value="あ">
<?php
	}
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
<?php
} else {
	switch($mode) {
		case 0:
			echo "スレッドを作成しました\n";
			break;
		case 1:
			echo "返信を投稿しました\n";
			break;
		case 2:
			echo "メッセージを編集しました\n";
			break;
	}
}
?>
<hr class="normal">
<ul id="footlink">
<?php
if($mode == 1 || $mode == 2) {
?>
<li><a href="/bbs/u/read.php?id=<?=$boad->sname?>&tid=<?=$thread->tid?>"<?=mbi_ack(7)?>><?=mbi("7.")?>スレッドに戻る</a></li>
<?php
}
?>
<li><a href="/bbs/u/?id=<?=$boad->sname?>"<?=mbi_ack(8)?>><?=mbi("8.").$boad->name?></a></li>
<li><a href="/bbs/"<?=mbi_ack(9)?>><?=mbi("9.")?>掲示板一覧</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
