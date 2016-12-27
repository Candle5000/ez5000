<?php
//=====================================
// スレッド一覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/board.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/bbs/class/guestLogin.php");
require_once("/var/www/functions/template.php");
session_start();
$LIMIT = 20;

// クッキー設定
setcookie("cookiecheck", true, time() + 864000);

if(!isset($_GET["id"])) die("ERROR01:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR02:無効なIDです");

$page = (isset($_GET["page"]) && preg_match("/^[0-9]+$/", $_GET["page"])) ? $_GET["page"] : 0;

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

// ゲストログイン情報
$guest = new GuestLogin($mysql);

// 掲示板情報を取得
$sql = "UPDATE `board` SET `access_cnt`=`access_cnt`+1 WHERE `name`='$id'";
$mysql->query($sql);
$sql = "SELECT * FROM `board` WHERE `name`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR03:存在しないIDです");
$board = new Board($result->fetch_array());
$title = $board->title;

// 過去ログの存在を確認
$sql = "SELECT COUNT(1) AS `archive_cnt` FROM `thread_archive` WHERE `bid`='{$board->bid}'";
$archive_cnt = $mysql->query($sql)->fetch_object()->archive_cnt;

// スレッド数を取得
$sql = "SELECT COUNT(1) AS `count` FROM `thread` WHERE `bid`='{$board->bid}'";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR04:存在しないIDです");
$array = $result->fetch_array();
$rows = $array["count"];

// スレッド一覧を取得
$sql = "SELECT T.tid,T.subject,T.tindex,";
$sql .= "IF(LENGTH(T.readpass) > 0,TRUE,FALSE) isset_readpass,IF(LENGTH(T.writepass) > 0,TRUE,FALSE) isset_writepass,";
$sql .= "T.access_cnt,COUNT(1) message_cnt,T.update_ts,T.locked,T.top,T.next_tmid";
$sql .= " FROM thread T JOIN message M ON T.bid = M.bid AND T.tid = M.tid WHERE T.bid = '{$board->bid}'";
$sql .= " GROUP BY T.tid ORDER BY T.top DESC, T.tindex DESC";
$sql .= " LIMIT ".($page * $LIMIT).",$LIMIT";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR05:存在しないIDです");

// ページ切り替えリンク生成
if(($page > 0) && ($rows > 0)) {
	$pagelink = "[<a href=\"./?id=$id&page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前へ</a>] ";
} else {
	$pagelink = "[".mbi("*.")."前へ] ";
}
$pagelink .= "[P ".($page + 1)."/".ceil($rows / $LIMIT)." ]";
if((($page + 1) * $LIMIT) < $rows) {
	$pagelink .= " [<a href=\"./?id=$id&page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次へ</a>]";
} else {
	$pagelink .= " [".mbi("#.")."次へ]";
}
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1><?=$board->title?></h1>
<hr class="normal">
<p>
[<a href="./form.php?mode=thform&id=<?=$board->name?>"<?=mbi_ack(8)?>><?=mbi("8.")?>新規スレ</a>] [<a href="./search.php?id=<?=$board->name?>"<?=mbi_ack(4)?>><?=mbi("4.")?>検索</a>]
</p>
<hr class="normal">
<div id="pagelink"><?=$pagelink?></div>
<hr class="normal">
<ul id="threadlist">
<?php
if($result->num_rows) {
	$date = date("Y-m-d H:i:s", strtotime("-2 day"));
	while($array = $result->fetch_array()) {
		$thread = new Thread($array);
		$new = (strtotime($date) < strtotime($thread->update_ts)) ? "<span class=\"nc6\">New</span>" : "";
		if($thread->locked) {
			$marker = "※";
		} else if($thread->top) {
			if($thread->isset_readpass) {
				$marker = "◆";
			} else {
				$marker = "▼";
			}
		} else {
			if($thread->isset_readpass) {
				$marker = "◇";
			} else {
				$marker = "▽";
			}
		}
?>
<li><span class="nc5"><?=$marker?></span><a href="./read.php?id=<?=$board->name?>&tid=<?=$thread->tid?>"><?=htmlspecialchars($thread->subject)."(".$thread->message_cnt.")"?></a><?=$new?></li>
<?php
	}
} else {
?>
<li>スレッドがありません</li>
<?php
}
?>
</ul>
<hr class="normal">
<div id="pagelink"><?=$pagelink?></div>
<hr class="normal">
<ul id="footlink">
<?php
if($archive_cnt > 0) {
?>
<li><a href="/bbs/archive/?id=<?=$board->name?>"<?=mbi_ack(6)?>><?=mbi("6.")?>過去ログ</a></li>
<?php
}
?>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot($board->access_cnt);
?>
</div>
</body>
</html>
