<?php
//=====================================
// 書き込みフォーム
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/bbs/class/guestUser.php");
require_once("/var/www/bbs/class/memberUser.php");
require_once("/var/www/bbs/class/anonymousId.php");
require_once("/var/www/functions/template.php");
session_start();
$MAX_FSIZE = 512000;

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
if(!isset($_GET["id"])) die("ERROR003:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR004:無効なIDです");

// スレッドID取得 返信/編集モードのみ
if($mode == 1 || $mode == 2) {
	if(!isset($_GET["tid"])) die("ERROR005:IDがありません");
	$tid = $_GET["tid"];
	if(!preg_match("/^[0-9]{1,9}$/", $tid)) die("ERROR006:無効なIDです");
}

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

// 送信先設定
$url = $_SERVER["PHP_SELF"]."?mode={$_GET["mode"]}&id=$id";
if($mode == 1 || $mode == 2) $url .= "&tid=$tid";
if($mode == 2) $url .= "&tmid=$tmid";
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

// ゲストログイン情報
$guest = new GuestUser($mysql);
if(device_info() != "mb" && $guest->id != null && (strtotime($guest->allow_post) > time())) $error_list[] = "ご利用のゲストIDは{$guest->allow_post}まで書き込みできません";

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
$title = $board->title;

// スレッド情報を取得 返信/編集モードのみ
if($mode == 1 || $mode == 2) {
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

	// 書込パスの確認
	if($thread->isset_writepass && !isset($_SESSION["write_auth"]["{$board->bid}"]["{$thread->tid}"])) {
		$http = "http";
		if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
		$tmid = ($mode == 2) ? "&tmid=$tmid" : "";
		$re = ($mode == 1 && $re != 0) ? "&re=$re" : "";
		header("HTTP/1.1 301 Moved Permanently");
		header("Pragma: no-cache");
		header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/writepass.php?mode={$_GET["mode"]}&id=$id&tid=$tid$tmid$re");
		exit;
	}

	if($thread->message_cnt > 999 && $mode == 1) die("ERROR104:スレッドの投稿数が上限に達しています");
	if($thread->locked) die("ERROR105:スレッドがロックされています");
}

// メッセージ情報を取得 編集モードのみ
if($mode == 2) {
	$sql = "SELECT `mid`,`tmid`,`name`,`comment`,`image`,`post_ts`,`update_ts`,`update_cnt`";
	$sql .= " FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid'";
	$result = $mysql->query($sql);
	if($mysql->error) die("ERROR106:存在しないIDです");
	if(!$result->num_rows) die("ERROR107:メッセージが見つかりません");
	$message = new Message($result->fetch_array(), $mysql, $board, $thread);
}

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

		$anonymous_id = new AnonymousId(false, $member->id, $mysql);
		$display_id = $anonymous_id->display_id;
	} else {
		$anonymous_id = new AnonymousId(true, $guest->id, $mysql);
		$display_id = $anonymous_id->display_id;
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
		if($board->default_name != "") {
			$name_a[0] = $board->default_name;
		} else {
			$error_list[] = "お名前が空です";
		}
	} else if(mb_strlen($name_a[0]) > $board->name_max) {
		$error_list[] = "お名前は{$board->name_max}文字以内にしてください";
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
	if(mb_strlen($title) > $board->subject_max) $error_list[] = "タイトルは{$board->subject_max}文字以内にしてください";

	// 本文取得
	$comment = isset($_POST["comment"]) ? $_POST["comment"] : "";
	if($comment == "") $error_list[] = "本文が空です";
	if(mb_strlen($comment) > $board->comment_max) $error_list[] = "本文は{$board->comment_max}文字以内にしてください";
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
	if(isset($_FILES["media"]) && is_uploaded_file($_FILES["media"]["tmp_name"])) {
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

	// 入室パスワード取得
	$readpass = ($mode == 0 && $board->allow_readpass && isset($_POST["readpass"])) ? $_POST["readpass"] : "";
	if($readpass != "" && !preg_match("/^[!-~]{4,64}$/", $readpass)) $error_list[] = "入室パスワードは半角英数字と記号のみで4～64文字にしてください";

	// 書込パスワード取得
	$writepass = ($mode == 0 && $board->allow_writepass && isset($_POST["writepass"])) ? $_POST["writepass"] : "";
	if($writepass != "" && !preg_match("/^[!-~]{4,64}$/", $writepass)) $error_list[] = "書込パスワードは半角英数字と記号のみで4～64文字にしてください";

	// 編集パスワード取得
	$pass = isset($_POST["pass"]) ? $_POST["pass"] : "";
	if($pass == "") $error_list[] = "パスワードが空です";
	if(!preg_match("/^[!-~]{4,64}$/", $pass)) $error_list[] = "パスワードは半角英数字と記号のみで4～64文字にしてください";

	// 書き込み削除取得 編集モードのみ
	$delmessage = (($mode == 2) && isset($_POST["delete"]) && ($_POST["delete"] == 1)) ? true : false;

	// 連投チェック
	if($mode == 0 && isset($_SESSION["thposttime"]) && ($_SESSION["thposttime"] > time())) $error_list[] = "{$board->thpost_limit}秒間は連続でスレッドを作成できません";
	if($mode == 1 && isset($_SESSION["reposttime"]) && ($_SESSION["reposttime"] > time())) $error_list[] = "{$board->repost_limit}秒間は連続で返信を投稿できません";

	// 画像認証
	$is_mb = (device_info() == 'mb' && $uid != "");
	if(!$is_mb && (!isset($_SESSION['ImageAuthentication']) || !isset($_POST["authcap"]) || ($_SESSION["ImageAuthentication"] != $_POST["authcap"]))) $error_list[] = "画像認証コードが一致しません";

	if(!isset($error_list)) {
		$sql_title = $mysql->real_escape_string($title);
		$sql_name = $mysql->real_escape_string($name_t);
		$sql_comment = $mysql->real_escape_string($comment);
		$sql_readpass = $mysql->real_escape_string($readpass);
		$sql_writepass = $mysql->real_escape_string($writepass);
		$sql_pass = $mysql->real_escape_string($pass);

		switch($mode) {

			case 0: // スレッド作成

				// トランザクション開始
				$mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				// 新規スレッドID、メッセージIDを取得
				$sql = "SELECT `next_tid`, `next_mid` FROM `board` WHERE `bid`='{$board->bid}'";
				$result_obj = $mysql->query($sql);
				if($mysql->error || !$result_obj->num_rows) {
					$mysql->rollback();
					die("ERROR201:クエリ処理に失敗しました");
				}
				$array = $result_obj->fetch_array();
				$next_tid = $array["next_tid"];
				$next_mid = $array["next_mid"];

				// 新規スレッドを登録
				$sql = "INSERT INTO thread (tid, bid, subject, tindex, readpass, writepass, update_ts)";
				$sql .= " VALUES ('$next_tid', '{$board->bid}', '$sql_title', '$next_mid', PASSWORD('$sql_readpass'), PASSWORD('$sql_writepass'), NOW())";
				$mysql->query($sql);
				if($mysql->error) {
					$mysql->rollback();
					die("ERROR202:クエリ処理に失敗しました");
				}

				// 新規メッセージを登録
				$sql = "INSERT INTO `message` (`mid`, `bid`, `tid`, `tmid`, `name`,";
				$sql .= " `comment`, `image`, `password`, `post_ts`, `ip`, `hostname`, `ua`, `uid`, `guest_id`, `display_id`)";
				$sql .= " VALUES ('$next_mid', '{$board->bid}', '$next_tid', '1', '$sql_name',";
				$sql .= " '$sql_comment', '$file_id', PASSWORD('$sql_pass'), NOW(),";
				$sql .= " '$ip', '$hostname', '$ua', '$uid', '{$guest->id}', '$display_id')";
				$mysql->query($sql);
				if($mysql->error) {
					$mysql->rollback();
					die("ERROR203:クエリ処理に失敗しました");
				}

				// 次のスレッドIDとメッセージIDを更新
				$sql = "UPDATE `board` SET `next_tid`=`next_tid`+1, `next_mid`=`next_mid`+1";
				$sql .= " WHERE `bid`='{$board->bid}'";
				$mysql->query($sql);
				if($mysql->error) {
					$mysql->rollback();
					die("ERROR204:クエリ処理に失敗しました");
				}

				// 過去ログ移動チェック
				$sql = "SELECT IF(";
				$sql .= "(SELECT COUNT(1) FROM thread T JOIN message M ON T.bid = M.bid AND T.tid = M.tid WHERE T.bid = '{$board->bid}' AND T.tindex = ";
				$sql .= "(SELECT MIN(tindex) FROM thread WHERE bid = '{$board->bid}')) <= ";
				$sql .= "(SELECT COUNT(1) - 50000 FROM message WHERE bid = '{$board->bid}'), ";
				$sql .= "(SELECT tid FROM thread WHERE bid = '{$board->bid}' AND tindex = ";
				$sql .= "(SELECT MIN(tindex) FROM thread WHERE bid = '{$board->bid}')), ";
				$sql .= "0) AS archive_tid";
				$result_obj = $mysql->query($sql);
				if($mysql->error || !$result_obj->num_rows) {
					$mysql->rollback();
					die("ERROR205:クエリ処理に失敗しました");
				}
				$array = $result_obj->fetch_array();
				$archive_tid = $array["archive_tid"];

				// 過去ログ移動処理
				if($archive_tid != "0") {
					$sql = "INSERT INTO `thread_archive`";
					$sql .= " SELECT `bid`, `tid`, `subject`, `tindex`, `readpass`, `writepass`, `access_cnt`, `next_tmid`, `update_ts`, `locked`, `top`";
					$sql .= " FROM `thread` WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR206:クエリ処理に失敗しました");
					}
					$sql = "INSERT INTO `message_archive`";
					$sql .= " SELECT `bid` ,`mid`, `tid`, `tmid`, `name`, `comment`, `image`, `post_ts`, `update_ts`, `update_cnt`, `ip`, `hostname`, `ua`, `uid`, `user_id`, `guest_id`, `display_id`";
					$sql .= " FROM message WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR207:クエリ処理に失敗しました");
					}
					$sql = "DELETE FROM `thread` WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR208:クエリ処理に失敗しました");
					}
					$sql = "DELETE FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR209:クエリ処理に失敗しました");
					}
				}

				// トランザクションのコミット
				$mysql->commit();

				if($name_a[0] != $board->default_name) setcookie("bbs_name", $name_a[0], time() + 604800);
				$_SESSION["thposttime"] = time() + $board->thpost_limit;
				$tid = $next_tid;
				$tmid = 1;
				break;

			case 1: // 返信投稿

				// トランザクションの開始
				$mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				// 次のメッセージIDを取得
				$sql = "SELECT `next_mid`, `next_tmid` FROM `thread` AS `T` JOIN `board` AS `B`";
				$sql .= " ON `T`.`bid`=`B`.`bid` WHERE `tid`='{$thread->tid}'";
				$result_obj = $mysql->query($sql);
				if($mysql->error || !$result_obj->num_rows) {
					$mysql->rollback();
					die("ERROR211:クエリ処理に失敗しました");
				}
				$array = $result_obj->fetch_array();
				$next_mid = $array["next_mid"];
				$next_tmid = $array["next_tmid"];

				// 新規メッセージを登録
				$sql = "INSERT INTO `message` (`mid`, `bid`, `tid`, `tmid`, `name`,";
				$sql .= " `comment`, `image`, `password`, `post_ts`, `ip`, `hostname`, `ua`, `uid`, `guest_id`, `display_id`)";
				$sql .= " VALUES ('$next_mid', '{$board->bid}', '{$thread->tid}', '$next_tmid',";
				$sql .= " '$sql_name', '$sql_comment', '$file_id', PASSWORD('$sql_pass'),";
				$sql .= " NOW(), '$ip', '$hostname', '$ua', '$uid', '{$guest->id}', '$display_id')";
				$mysql->query($sql);
				if($mysql->error) {
					$mysql->rollback();
					die("ERROR212:クエリ処理に失敗しました");
				}

				// スレッドを上げない/上げる
				if($sage) {
					$sql = "UPDATE `thread` SET `next_tmid`=`next_tmid`+1, `update_ts`=NOW() WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
				} else {
					$sql = "UPDATE `thread` SET `next_tmid`=`next_tmid`+1, `tindex`='$next_mid', `update_ts`=NOW() WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
				}
				$mysql->query($sql);
				if($mysql->error) {
					$mysql->rollback();
					die("ERROR213:クエリ処理に失敗しました");
				}

				// 次のメッセージIDを更新
				$sql = "UPDATE `board` SET `next_mid`=`next_mid`+1";
				$sql .= " WHERE `bid`='{$board->bid}'";
				$mysql->query($sql);
				if($mysql->error) {
					$mysql->rollback();
					die("ERROR214:クエリ処理に失敗しました");
				}

				// 過去ログ移動チェック
				$sql = "SELECT IF(";
				$sql .= "(SELECT COUNT(1) FROM thread T JOIN message M ON T.bid = M.bid AND T.tid = M.tid WHERE T.bid = '{$board->bid}' AND T.tindex = ";
				$sql .= "(SELECT MIN(tindex) FROM thread WHERE bid = '{$board->bid}')) <= ";
				$sql .= "(SELECT COUNT(1) - 50000 FROM message WHERE bid = '{$board->bid}'), ";
				$sql .= "(SELECT tid FROM thread WHERE bid = '{$board->bid}' AND tindex = ";
				$sql .= "(SELECT MIN(tindex) FROM thread WHERE bid = '{$board->bid}')), ";
				$sql .= "0) AS archive_tid";
				$result_obj = $mysql->query($sql);
				if($mysql->error || !$result_obj->num_rows) {
					$mysql->rollback();
					die("ERROR215:クエリ処理に失敗しました");
				}
				$array = $result_obj->fetch_array();
				$archive_tid = $array["archive_tid"];

				// 過去ログ移動処理
				if($archive_tid != "0") {
					$sql = "INSERT INTO `thread_archive`";
					$sql .= " SELECT `bid`, `tid`, `subject`, `tindex`, `readpass`, `writepass`, `access_cnt`, `next_tmid`, `update_ts`, `locked`, `top`";
					$sql .= " FROM `thread` WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR216:クエリ処理に失敗しました");
					}
					$sql = "INSERT INTO `message_archive`";
					$sql .= " SELECT `bid` ,`mid`, `tid`, `tmid`, `name`, `comment`, `image`, `post_ts`, `update_ts`, `update_cnt`, `ip`, `hostname`, `ua`, `uid`, `user_id`, `guest_id`, `display_id`";
					$sql .= " FROM message WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR217:クエリ処理に失敗しました");
					}
					$sql = "DELETE FROM `thread` WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR218:クエリ処理に失敗しました");
					}
					$sql = "DELETE FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$archive_tid'";
					$mysql->query($sql);
					if($mysql->error) {
						$mysql->rollback();
						die("ERROR219:クエリ処理に失敗しました");
					}
				}

				// トランザクションのコミット
				$mysql->commit();
				$tmid = $next_tmid;
				if($name_a[0] != $board->default_name) setcookie("bbs_name", $name_a[0], time() + 604800);
				$_SESSION["reposttime"] = time() + $board->repost_limit;
				break;

			case 2: // メッセージ編集
				$mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
				$sql = "SELECT `password`=PASSWORD('$sql_pass') AS `match` FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid' AND `mid`='{$message->mid}'";
				$result = $mysql->query($sql);
				if($mysql->error) {
					$mysql->rollback();
					die("ERROR221:クエリ処理に失敗しました");
				}
				if(!$result->num_rows) {
					$mysql->rollback();
					die("ERROR222:メッセージが見つかりません");
				}
				$array = $result->fetch_array();
				if($array["match"]) {
					// メッセージ編集
					if($delmessage) {
						if($tmid == 1) {
							// スレッド削除
							$sql = "INSERT INTO `thread_deleted`";
							$sql .= " SELECT `bid`, `tid`, `subject`, `tindex`, `readpass`, `writepass`, `access_cnt`, `next_tmid`, `update_ts`, `locked`, `top`";
							$sql .= " FROM `thread` WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								die("ERROR223:クエリ処理に失敗しました");
							}
							$sql = "INSERT INTO `message_deleted`";
							$sql .= " SELECT `bid` ,`mid`, `tid`, `tmid`, `name`, `comment`, `image`, `post_ts`, `update_ts`, ";
							$sql .= "`update_cnt`, `ip`, `hostname`, `ua`, `uid`, `user_id`, `guest_id`, `display_id`";
							$sql .= " FROM message WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								die("ERROR224:クエリ処理に失敗しました");
							}
							$sql = "DELETE FROM `thread` WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								die("ERROR225:クエリ処理に失敗しました");
							}
							$sql = "DELETE FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								die("ERROR226:クエリ処理に失敗しました");
							}
						} else {
							// メッセージ削除
							$sql = "INSERT INTO `message_deleted`";
							$sql .= " SELECT `bid`, `mid`, `tid`, `tmid`, `name`, `comment`, `image`, `post_ts`, `update_ts`, ";
							$sql .= "`update_cnt`, `ip`, `hostname`, `ua`, `uid`, `user_id`, `guest_id`, `display_id`";
							$sql .= " FROM message WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								die("ERROR227:クエリ処理に失敗しました");
							}
							$sql = "DELETE FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid'";
							$mysql->query($sql);
							$sql = "";
							$sql = "UPDATE `thread` AS `T`,";
							$sql .= " (SELECT MAX(`post_ts`) AS `pts`, MAX(`mid`) AS `tindex`";
							$sql .= " FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid') AS `M`";
							$sql .= " SET `T`.`update_ts`=(CASE WHEN `T`.`update_ts` > `M`.`pts` THEN `M`.`pts` ELSE `T`.`update_ts` END),";
							$sql .= " `T`.`tindex`=(CASE WHEN `T`.`tindex` > `M`.`tindex` THEN `M`.`tindex` ELSE `T`.`tindex` END)";
							$sql .= " WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								echo $sql;
								die("ERROR228:クエリ処理に失敗しました");
							}
						}
					} else {
						if($tmid == 1) {
							// スレッド編集
							$sql = "INSERT INTO `thread_history`";
							$sql .= " SELECT `T`.`bid`, `T`.`tid`, `M`.`update_cnt`, `T`.`subject`, NOW()";
							$sql .= " FROM `thread` AS `T` JOIN `message` AS `M`";
							$sql .= " ON `T`.`bid`=`M`.`bid` AND `T`.`tid`=`M`.`tid`";
							$sql .= " WHERE `T`.`bid`='{$board->bid}' AND `T`.`tid`='$tid' AND `M`.`tmid`='1'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								die("ERROR229:クエリ処理に失敗しました");
							}
							$sql = "UPDATE `thread` SET `subject`='$sql_title'";
							$sql .= " WHERE `bid`='{$board->bid}' AND `tid`='$tid'";
							$mysql->query($sql);
							if($mysql->error) {
								$mysql->rollback();
								die("ERROR230:クエリ処理に失敗しました");
							}
						}
						// メッセージ編集
						$sql = "INSERT INTO `message_history`";
						$sql .= " SELECT `bid`, `mid`, `update_cnt`, `tid`, `tmid`, `name`, `comment`, `image`, `ip`, `hostname`, `ua`, ";
						$sql .= "`uid`, `user_id`, `guest_id`, `display_id`, IFNULL(`update_ts`, `post_ts`)";
						$sql .= " FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid'";
						$mysql->query($sql);
						if($mysql->error) {
							$mysql->rollback();
							die("ERROR231:クエリ処理に失敗しました");
						}
						$sql = "UPDATE `message` SET `name`='$sql_name', `comment`='$sql_comment',";
						if($delmedia || $file_id != "") $sql .= " `image`='$file_id',";
						$sql .= " `update_ts`=NOW(), `update_cnt`=`update_cnt`+1,";
						$sql .= " `ip`='$ip', `hostname`='$hostname', `ua`='$ua', `uid`='$uid', `guest_id`='{$guest->id}', `display_id`='$display_id'";
						$sql .= " WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='$tmid'";
						$mysql->query($sql);
						if($mysql->error) {
							$mysql->rollback();
							die("ERROR231:クエリ処理に失敗しました");
						}
					}
					$mysql->commit();

					// 名前欄クッキー
					if(($name_a[0] != $board->default_name) && !$delmessage) setcookie("bbs_name", $name_a[0], time() + 604800);

					// 添付ファイル削除
					if(($message->image != "" && ($delmedia || $file_id != "")) || ($message->image != "" && $delmessage && $tmid != 1)) {
						$filename = "{$board->name}-$tid-$tmid-{$message->image}";
						if(file_exists("/var/www/img/bbs/$filename")) rename("/var/www/img/bbs/$filename", "/var/www/img/bbs/trash/$filename");
						if(file_exists("/var/www/img/bbs/$filename.png")) rename("/var/www/img/bbs/$filename.png", "/var/www/img/bbs/trash/$filename.png");
					} else if($delmessage && $tmid == 1) {
						$sql = "SELECT `tmid`,`image` FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `image`!=''";
						$result = $mysql->query($sql);
						while($array = $result->fetch_array()) {
							$filename = "{$board->name}-$tid-{$array["tmid"]}-{$array["image"]}";
							if(file_exists("/var/www/img/bbs/$filename")) rename("/var/www/img/bbs/$filename", "/var/www/img/bbs/trash/$filename");
							if(file_exists("/var/www/img/bbs/$filename.png")) rename("/var/www/img/bbs/$filename.png", "/var/www/img/bbs/trash/$filename.png");
						}
					}
				} else {
					$mysql->commit();
					$error_list[] = "パスワードが間違っています";
				}
				break;
		}

		if(!isset($error_list) && !$delmessage) {

			// 同一内容投稿防止
			if($mode == 0 || $mode == 1) $_SESSION["comment"] = $comment;

			// ファイルアップロード
			if(isset($_FILES["media"]) && is_uploaded_file($_FILES["media"]["tmp_name"])) {
				$file_path = "/var/www/img/bbs/{$board->name}-$tid-$tmid-$file_id";
				if(move_uploaded_file($_FILES["media"]["tmp_name"], $file_path)) {
					chmod($file_path, 0644);

					// サムネイル保存
					$file_path_t = "$file_path.png";
					$width = 120;
					$color_bit = 16;
					$imagick = new Imagick($file_path);
					$img_size = $imagick->getImageGeometry();
					if($img_size["width"] > $width || $img_size["height"] > $width) $imagick->thumbnailImage($width, $width, true);
					$imagick->posterizeImage(16, true);
					$imagick->writeImage($file_path_t);
					chmod($file_path_t, 0644);
				}
			}
		}

		// 投稿後に画像認証情報をリセット
		if(isset($_SESSION['ImageAuthentication'])) unset($_SESSION['ImageAuthentication']);
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
		$subject = $thread->subject;
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
			$title = "{$thread->subject}への返信";
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
<?=pagehead($board->title)?>
</head>
<body>
<div id="all">
<h1><?=$board->title?></h1>
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
<?php
	if($mode == 0 && $board->allow_readpass) {
?>
スレ入室パス<br />
<input type="password" name="readpass" maxlength="32" value=""><br />
<?php
	}
?>
<?php
	if($mode == 0 && $board->allow_writepass) {
?>
スレ書込パス<br />
<input type="password" name="writepass" maxlength="32" value=""><br />
<?php
	}
?>
画像ファイル<?=mbi("(対応機種のみ)")?>※512KBまで<br />
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
	} else {
?>
<img src="ImageAuthentication.php" /><br />
画像認証-上記文字を入力してください<br />
<input type="text" name="authcap">
<hr class="normal">
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
<li><a href="/bbs/read.php?id=<?=$board->name?>&tid=<?=$thread->tid?>"<?=mbi_ack(8)?>><?=mbi("8.")?>スレッドに戻る</a></li>
<?php
}
?>
<li><a href="/bbs/?id=<?=$board->name?>"<?=mbi_ack(9)?>><?=mbi("9.").$board->title?></a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
