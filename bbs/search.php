<?php
//=====================================
// 書き込み検索フォーム
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/message.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
session_start();

// 掲示板ID取得
if(!isset($_GET["id"])) die("ERROR01:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR02:無効なIDです");

// スレッドID取得
$tid = isset($_GET["tid"]) ? $_GET["tid"] : 0;
if(!preg_match("/^[0-9]{1,9}$/", $tid)) die("ERROR03:無効なIDです");

// 送信先設定
$url = $_SERVER["PHP_SELF"];
if($tid > 0) $url .= "?tid=$tid";

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
$sql = "SELECT * FROM `board` WHERE `sname`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR11:存在しないIDです");
$board = new Board($result->fetch_array());
$title = $board->name;

// スレッド情報を取得
if($tid > 0) {
	$sql = "SELECT `thread`.`tid`,`title`,`tindex`,`acount`,COUNT(1) AS `mcount`,`updated`,`locked`,`top`,`pastlog` FROM `thread` NATURAL JOIN `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `pastlog`=FALSE AND `deleted`=FALSE AND (SELECT '1' FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='$tid' AND `tmid`='1' AND `deleted`=FALSE)='1' GROUP BY `tid`";
	$result = $mysql->query($sql);
	if(!$result->num_rows) die("ERROR12:存在しないIDです");
	$thread = new Thread($result->fetch_array());
}

// 検索ワード
if(isset($_GET["words"])) {
	$words = $_GET["words"];
} else if(isset($_POST["words"])) {
	$words = $_POST["words"];
}

// 検索モード
if(isset($_GET["mode"])) {
	$mode = $_GET["mode"];
} else if(isset($_POST["mode"])) {
	$mode = $_POST["mode"];
} else {
	$mode = 'and';
}
$mode = ($mode == 'OR') ? 'OR' : 'AND';

// 検索対象
if(isset($_GET["target"])) {
	$target = $_GET["target"];
} else if(isset($_POST["target"])) {
	$target = $_POST["target"];
} else {
	$target = 'all';
}
$target = ($target == 'comment' || $target == 'title' || $target == 'name') ? $target : 'all';

// ページ
if(isset($_GET["page"]) && is_numeric($_GET["page"]) && $_GET["page"] > 0) {
	$page = $_GET["page"];
} else if(isset($_POST["page"]) && is_numeric($_POST["page"]) && $_POST["page"] > 0) {
	$page = $_POST["page"];
} else {
	$page = 0;
}

// 文字コード確認 フィーチャーフォンのみ
if(device_info() == "mb" && isset($words) && isset($_POST["enc"])) {
	switch("あ") {
		case mb_convert_encoding($_POST["enc"], "UTF-8", "SJIS-WIN"):
			$words = mb_convert_encoding($words, "UTF-8", "SJIS-WIN");
			break;
		case urldecode($_POST["enc"]):
			$words = urldecode($words);
			break;
		case mb_convert_encoding(urldecode($_POST["enc"]), "UTF-8", "SJIS-WIN"):
			$words = mb_convert_encoding(urldecode($words), "UTF-8", "SJIS-WIN");
			break;
	}
}

// 検索
if(isset($words)) {
	$table = ($tid > 0) ? "`message`" : "`message` NATURAL JOIN `thread`";
	switch($target) {
		case 'comment':
			$target = "`comment`";
			break;
		case 'title':
			$target = "`title`";
			break;
		case 'name':
			$target = "`name`";
			break;
		default:
			$target = "CONCAT(`comment`,' ',`title`,' ',`name`)";
			break;
	}
	$input = preg_replace("/~/", "～", mb_convert_kana($words,"asKV"));
	$input = mb_ereg_replace("_", "\\\\_", mb_ereg_replace("%", "\\\\%", $mysql->real_escape_string($input)));
	$keywords = preg_split("/[\s]+/", $input);
	$like_list = array();
	foreach($keywords as $kw) {
		if($kw != "") $like_list[] = "$target COLLATE `utf8mb4_unicode_ci` LIKE '%$kw%'";
	}
	$start = $page * 10;
	$message_list = array();
	if(count($like_list) > 0) {
		$column = "";
		$where = implode(" $mode ", $like_list);
		$where_add = ($tid > 0) ? "`bid`='{$board->bid}' AND `tid`='$tid'" : "`bid`='{$board->bid}'";
		$where_add .= " AND `pastlog`=FALSE AND `deleted`=FALSE";
		$sql = "SELECT COUNT(*) AS `count` FROM $table WHERE ($where) AND $where_add";
		$count = $mysql->query($sql)->fetch_object()->count;
		$sql = "SELECT * FROM $table WHERE ($where) AND $where_add ORDER BY `mid` DESC LIMIT $start,10";
		$result = $mysql->query($sql);
		echo $sql;
		while($array = $result->fetch_array()) {
			if($tid == 0) {
				$sql = "SELECT `thread`.`tid`,`title`,`tindex`,`acount`,COUNT(1) AS `mcount`,`updated`,`locked`,`top`,`pastlog` FROM `thread` NATURAL JOIN `message` WHERE `bid`='{$board->bid}' AND `tid`='{$array["tid"]}' AND `pastlog`=FALSE AND `deleted`=FALSE AND (SELECT '1' FROM `message` WHERE `bid`='{$board->bid}' AND `tid`='{$array["tid"]}' AND `tmid`='1' AND `deleted`=FALSE)='1' GROUP BY `tid`";
				$thread = new Thread($mysql->query($sql)->fetch_array());
			}
			$message_list[] = new Message($array, $mysql, $board, $thread);
		}
	} else {
		$count = 0;
	}

	// ページリンク
	$link = "./search.php?id=$id";
	if($tid > 0) $link .= "&tid=$tid";
	$link .= "&words=".urlencode($words)."&mode=$mode&target=$target";
	if(($page > 0) && ($count > 0)) {
		$pagelink = "<a href=\"$link&page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
	} else {
		$pagelink = mbi("*.")."前のページ | ";
	}
	if((($page + 1) * 10) < $count) {
		$pagelink .= "<a href=\"$link&page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
	} else {
		$pagelink .= mbi("#.")."次のページ";
	}
}
?>
<html>
<head>
<?=pagehead($board->name)?>
</head>
<body>
<div id="all">
<h1><?=$board->name?></h1>
<hr class="normal">
<h2>メッセージ検索</h2>
<hr class="normal">
<?php
if(device_info() == 'mb') {
	$action = $_SERVER["PHP_SELF"]."?id=$id";
	if($tid > 0) $action .= "&tid=$tid";
	$method = "POST";
} else {
	$action = $_SERVER["PHP_SELF"];
	$method = "GET";
}
$text = isset($words) ? htmlentities($words, ENT_QUOTES, "utf-8") : "";
if($tid > 0) {
?>
スレッド:<?=$thread->title?><br />
<?php
}
?>
<form action="<?=$action?>" method="<?=$method?>" enctype="multipart/form-data">
<input type="text" name="words" value="<?=$text?>"><br />
<label><input type="radio" name="mode" <?=form_radio_checked("AND", $mode)?>>AND</label> 
<label><input type="radio" name="mode" <?=form_radio_checked("OR", $mode)?>>OR</label>
<label>対象:<select name="target">
<option value="all">すべて</option>
<option value="comment">本文</option>
<?php
if($tid > 0) {
?>
<option value="title">タイトル</option>
<?php
}
?>
<option value="name">投稿者</option>
</select></label>
<input type="hidden" name="page" value="0">
<?php
if(device_info() == 'mb') {
?>
<input type="hidden" name="enc" value="あ">
<?php
} else {
?>
<input type="hidden" name="id" value="<?=$id?>">
<?php
	if($tid > 0) {
?>
<input type="hidden" name="tid" value="<?=$tid?>">
<?php
	}
}
?>
<input type="submit" value="検索">
</form>
<?php
if(isset($words)) {
?>
<hr class="normal">
[<?=htmlentities($words, ENT_QUOTES, "utf-8")?>]の検索結果<br />
<?=$count?> 件中 <?=(($page * 10) + 1)?> - <?=(($page + 1) * 10)?> 件
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<?php
	foreach($message_list as $message) {
		if($tid > 0) {
			$message->printMessage();
		} else {
			$message->printSearchedMessage();
		}
	}
?>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<?php
}
?>
<hr class="normal">
<ul id="footlink">
<?php
if($tid > 0) {
?>
<li><a href="/bbs/read.php?id=<?=$board->sname?>&tid=<?=$thread->tid?>"<?=mbi_ack(7)?>><?=mbi("7.")?>スレッドに戻る</a></li>
<?php
}
?>
<li><a href="/bbs/?id=<?=$board->sname?>"<?=mbi_ack(8)?>><?=mbi("8.").$board->name?></a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot(0);
?>
</div>
</body>
</html>
