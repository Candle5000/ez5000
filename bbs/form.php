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
$MAX_FSIZE = 512000;

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
$sql = "SELECT * FROM `boad` WHERE `sname`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR11:存在しないIDです");
$boad = new Boad($result->fetch_array());
$title = $boad->name;

// スレッド情報を取得 返信/編集モードのみ
if($mode == 1 || $mode == 2) {
	$sql = "SELECT `thread`.`tid`,`title`,`tindex`,`acount`,COUNT(1) AS `mcount`,`updated`,`locked`,`top`,`pastlog` FROM `thread` NATURAL JOIN `message` WHERE `bid`='{$boad->bid}' AND `tid`='$tid' AND `pastlog`=FALSE AND (SELECT '1' FROM `message` WHERE `bid`='{$boad->bid}' AND `tid`='$tid' AND `tmid`='1' AND `deleted`=FALSE)='1' GROUP BY `tid`";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR12:存在しないIDです");
	if(!$result->num_rows) die("ERROR13:存在しないIDです");
	$thread = new Thread($result->fetch_array());
	if($thread->mcount > 999) die("ERROR14:スレッドの投稿数が上限に達しています");
	if($thread->locked) die("ERROR15:スレッドがロックされています");
}

// メッセージ情報を取得 編集モードのみ
if($mode == 2) {
	$sql = "SELECT * FROM `message` WHERE `bid`='{$boad->bid}' AND `tid`='$tid' AND `tmid`='$tmid' AND `deleted`=FALSE";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR16:存在しないIDです");
	if(!$result->num_rows) die("ERROR17:メッセージが見つかりません");
	$message = new Message($result->fetch_array(), $mysql, $boad, $thread);
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
	if(device_info() == "mb") {
		if($enc_mode == 1) {
			$name = mb_convert_encoding($name, "UTF-8", "SJIS-WIN");
		} else if($enc_mode == 2) {
			$name = urldecode($name);
		} else if($enc_mode == 3) {
			$name = mb_convert_encoding(urldecode($name), "UTF-8", "SJIS-WIN");
		}
	}
	$name_a = Message::tripConvert($name);
	$name = $name_a[0];
	if($name_a[0] == "") {
		if($boad->default_name != "") {
			$name_a[0] = $boad->default_name;
		} else {
			$error_list[] = "お名前が空です";
		}
	} else if(mb_strlen($name_a[0]) > 30) {
		$error_list[] = "お名前は30文字以内にしてください";
	}
	$name_t = isset($name_a[1]) ? $name_a[0].'/'.$name_a[1] : $name_a[0];

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

	// ファイルアップロード
	$file_id = "";
	if(is_uploaded_file($_FILES["media"]["tmp_name"])) {
		$extension = pathinfo($_FILES["media"]["name"], PATHINFO_EXTENSION);
		if(preg_match("/(png|jpe?g|gif)/i", $extension)) {
			try {
				$imageinfo = @getimagesize($_FILES["media"]["tmp_name"]);
				if(filesize($_FILES["media"]["tmp_name"]) < $MAX_FSIZE) {
					$file_id = uniqid().".$extension";
				} else {
					$error_list[] = "ファイルサイズが大きすぎます".filesize($_FILES["media"]["tmp_name"]);
				}
			} catch(RuntimeException $e) {
				$error_list[] = "ファイルの読み込みに失敗しました";
			}
		} else {
			$error_list[] = "ファイル拡張子が非対応です";
		}
	}

	// 添付ファイル削除 編集モードのみ
	$delmedia = (($mode == 2) && ($file_id == "") && isset($_POST["delmedia"]) && $_POST["delmedia"]) ? true : false;

	// 編集パスワード取得
	$pass = isset($_POST["pass"]) ? $_POST["pass"] : "";
	if($pass == "") $error_list[] = "パスワードが空です";
	if(!preg_match("/^[!-~]{4,64}$/", $pass)) $error_list[] = "パスワードは半角英数字と記号のみで4～64文字にしてください";

	// 書き込み削除取得 編集モードのみ
	$delmessage = (($mode == 2) && isset($_POST["delete"]) && ($_POST["delete"] == 1)) ? true : false;

	// クッキー有効確認
	if(!isset($_COOKIE["cookiecheck"])) {
		$error_list[] = "クッキーを有効にしてください";
		setcookie("cookiecheck", true, time() + 864000);
	}

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
		$sql_title = $mysql->real_escape_string($title);
		$sql_name = $mysql->real_escape_string($name_t);
		$sql_comment = $mysql->real_escape_string($comment);
		$sql_pass = $mysql->real_escape_string($pass);

		switch($mode) {

			case 0: // スレッド作成
				$sql = "SELECT MAX(`tid`)+1 AS `next_tid`, MAX(`tindex`)+1 AS `next_tindex` FROM `thread` WHERE `bid`='{$boad->bid}'";
				$result_obj = $mysql->query($sql)->fetch_object();
				if($mysql->error) die("ERROR20:クエリ処理に失敗しました");
				$next_tid = $result_obj->next_tid;
				$next_tindex = $result_obj->next_tindex;
				$sql = "INSERT INTO `thread` (`tid`, `bid`, `title`, `tindex`, `updated`) VALUES($next_tid, '{$boad->bid}', '{$sql_title}', '$next_tindex', NOW())";
				$mysql->query($sql);
				if($mysql->error) die("ERROR21:クエリ処理に失敗しました");
				$sql_sub = "SELECT MAX(`mid`)+1 AS `mid`, '{$boad->bid}' AS `bid`, $next_tid AS `tid`, '1' AS `tmid`, '$sql_name' AS `name`, '$sql_comment' AS `comment`, '$file_id' AS `image`, PASSWORD('$sql_pass') AS `password`, NOW() AS `ts`, '$ip' AS `ip`, '$ua' AS `ua`, '$uid' AS `uid` FROM `message` WHERE `bid`='{$boad->bid}'";
				$sql = "INSERT INTO `message` (`mid`, `bid`, `tid`, `tmid`, `name`, `comment`, `image`, `password`, `ts`, `ip`, `ua`, `uid`) $sql_sub";
				$mysql->query($sql);
				if($mysql->error) die("ERROR22:クエリ処理に失敗しました");
				if($name_a[0] != $boad->default_name) setcookie("bbs_name", $name_a[0], time() + 604800);
				$_SESSION["thposttime"] = time() + 300;
				$tid = $next_tid;
				$tmid = 1;
				break;

			case 1: // 返信投稿
				$sql_max_mid = "SELECT MAX(`mid`)+1 FROM `message` WHERE `bid`='{$boad->bid}'";
				$sql_sub = "SELECT ($sql_max_mid) AS `mid`, '{$boad->bid}' AS `bid`, '$tid' AS `tid`, MAX(`tmid`)+1 AS `tmid`, '$sql_name' AS `name`, '$sql_comment' AS `comment`, '$file_id' AS `image`, PASSWORD('$sql_pass') AS `password`, NOW() AS `ts`, '$ip' AS `ip`, '$ua' AS `ua`, '$uid' AS `uid` FROM `message` WHERE `bid`='{$boad->bid}' AND `tid`='$tid'";
				$sql = "INSERT INTO `message` (`mid`, `bid`, `tid`, `tmid`, `name`, `comment`, `image`, `password`, `ts`, `ip`, `ua`, `uid`) $sql_sub";
				$mysql->query($sql);
				if($mysql->error) die("ERROR23:クエリ処理に失敗しました");
				$sql = "SELECT MAX(`tmid`) AS `max_tmid` FROM `message` WHERE `bid`='{$boad->bid}' AND `tid`='$tid'";
				$tmid = $mysql->query($sql)->fetch_object()->max_tmid;
				if($sage) {
					$sql = "UPDATE `thread` SET `updated`=NOW() WHERE `bid`='{$boad->bid}' AND `tid`='$tid'";
				} else {
					$sql_sub = "SELECT MAX(`tindex`)+1 AS `tindex_max` FROM `thread` WHERE `bid`='{$boad->bid}'";
					$sql = "UPDATE `thread`, ($sql_sub) AS `thread` SET `tindex`=`thread`.`tindex_max`, `updated`=NOW() WHERE `bid`='{$boad->bid}' AND `tid`='$tid'";
				}
				$mysql->query($sql);
				if($mysql->error) die("ERROR24:クエリ処理に失敗しました");
				if($name_a[0] != $boad->default_name) setcookie("bbs_name", $name_a[0], time() + 604800);
				$_SESSION["reposttime"] = time() + 60;
				break;

			case 2: // メッセージ編集
				$sql = "SELECT `password`=PASSWORD('$sql_pass') AS `match` FROM `message` WHERE `bid`='{$boad->bid}' AND `tid`='$tid' AND `tmid`='$tmid' AND `mid`='{$message->mid}' AND `deleted`=FALSE AND (SELECT '1' FROM `thread` WHERE `bid`='{$boad->bid}' AND `tid`='$tid' AND `pastlog`=FALSE)='1'";
				$result = $mysql->query($sql);
				if($mysql->error) die("ERROR25:クエリ処理に失敗しました");
				if(!$result->num_rows) die("ERROR26:メッセージが見つかりません");
				$array = $result->fetch_array();
				if($array["match"]) {
					// メッセージ編集
					$sql_img = (!$delmedia && $file_id == "") ? "" : " `image`='$file_id',";
					$sql_set = (!$delmessage) ? "`name`='$sql_name', `comment`='$sql_comment',$sql_img `ip`='$ip', `ua`='$ua', `uid`='$uid'" : "`deleted`=TRUE";
					$sql_tmid = ($delmessage && $tmid == 1) ? "" : " AND `tmid`='$tmid'";
					$sql = "UPDATE `message` SET $sql_set WHERE `bid`='{$boad->bid}' AND `tid`='$tid'$sql_tmid";
					$mysql->query($sql);
					if($mysql->error) die("ERROR27:クエリ処理に失敗しました");

					// スレッド編集
					if($tmid == 1 && !$delmessage) {
						$sql = "UPDATE `thread` SET `title`='$sql_title' WHERE `bid`='{$boad->bid}' AND `tid`='$tid'";
						$mysql->query($sql);
						if($mysql->error) die("ERROR28:クエリ処理に失敗しました");
					}

					// 名前欄クッキー
					if(($name_a[0] != $boad->default_name) && !$delmessage) setcookie("bbs_name", $name_a[0], time() + 604800);

					// 添付ファイル削除
					if(($message->image != "" && ($delmedia || $file_id != "")) || ($message->image != "" && $delmessage && $tmid != 1)) {
						$filename = "{$boad->sname}-$tid-$tmid-{$message->image}";
						rename("/var/www/img/bbs/$filename", "/var/www/img/bbs/trash/$filename");
					} else if($delmessage && $tmid == 1) {
						$sql = "SELECT `tmid`,`image` FROM `message` WHERE `bid`='{$boad->bid}' AND `tid`='$tid' AND `image`!=''";
						$result = $mysql->query($sql);
						while($array = $result->fetch_array()) {
							$filename = "{$boad->sname}-$tid-{$array["tmid"]}-{$array["image"]}";
							if(file_exists("/var/www/img/bbs/$filename")) rename("/var/www/img/bbs/$filename", "/var/www/img/bbs/trash/$filename");
						}
					}
				} else {
					$error_list[] = "パスワードが間違っています";
				}
				break;
		}

		if(!isset($error_list) && !$delmessage) {

			// 同一内容投稿防止
			if($mode == 0 || $mode == 1) $_SESSION["comment"] = $comment;

			// ファイルアップロード
			if(is_uploaded_file($_FILES["media"]["tmp_name"])) {
				$file_path = "/var/www/img/bbs/{$boad->sname}-$tid-$tmid-$file_id";
				if(move_uploaded_file($_FILES["media"]["tmp_name"], $file_path)) {
					chmod($file_path, 0644);
				}
			}
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
		$name_a = explode('/', $message->name, 2);
		$name = $name_a[0];
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
画像ファイル<?=mbi("(対応機種のみ)")?><br />
<input type="hidden" name="MAX_FILE_SIZE" value="<?=$MAX_FSIZE?>" />
<input name="media" type="file" value="1"><br />
<?php
	if($mode == 2 && $message->image != "") {
?>
<input type="checkbox" name="delmedia">添付ファイル削除<br />
<?php
	}
?>
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
<select name="delete">
<option value="0">編集する</option>
<option value="1">削除する</option>
</select><br />
<input type="submit" value=" 送信 ">
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
			if(!$delmessage) {
				echo "メッセージを編集しました\n";
			} else {
				echo "メッセージを削除しました\n";
			}
			break;
	}
}
?>
<hr class="normal">
<ul id="footlink">
<?php
if(($mode == 1 || $mode == 2) && !(isset($delmessage) && $delmessage && $tmid == 1)) {
?>
<li><a href="/bbs/read.php?id=<?=$boad->sname?>&tid=<?=$thread->tid?>"<?=mbi_ack(7)?>><?=mbi("7.")?>スレッドに戻る</a></li>
<?php
}
?>
<li><a href="/bbs/?id=<?=$boad->sname?>"<?=mbi_ack(8)?>><?=mbi("8.").$boad->name?></a></li>
<li><a href="/bbs/"<?=mbi_ack(9)?>><?=mbi("9.")?>掲示板一覧</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
