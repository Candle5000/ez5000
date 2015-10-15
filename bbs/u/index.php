<?php
//=====================================
// スレッド一覧
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/bbs/class/boad.php");
require_once("/var/www/bbs/class/thread.php");
require_once("/var/www/functions/template.php");
$LIMIT = 20;

if(!isset($_GET["id"])) die("ERROR01:IDがありません");
$id = $_GET["id"];
if(!preg_match("/^[a-zA-Z0-9]{1,16}$/", $id)) die("ERROR02:無効なIDです");

$page = (isset($_GET["page"]) && preg_match("/^[0-9]+$/", $_GET["page"])) ? $_GET["page"] : 0;

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
$sql = "UPDATE `boad` SET `count`=`count`+1 WHERE `sname`='$id'";
$mysql->query($sql);
$sql = "SELECT * FROM `boad` WHERE `sname`='$id'";
$result = $mysql->query($sql);
if(!$result->num_rows) die("ERROR03:存在しないIDです");
$boad = new Boad($result->fetch_array());
$title = $boad->name;

// スレッド数を取得
$sql = "SELECT COUNT(1) AS `count` FROM `{$id}_t`";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR04:存在しないIDです");
$array = $result->fetch_array();
$rows = $array["count"];

// スレッド一覧を取得
$sql = "SELECT * FROM `{$id}_t` ORDER BY `top` DESC, `tindex` DESC LIMIT ".($page * $LIMIT).",$LIMIT";
$result = $mysql->query($sql);
if($mysql->error) die("ERROR05:存在しないIDです");

// ページ切り替えリンク生成
if(($page > 0) && ($rows > 0)) {
	$pagelink = "<a href=\"./?id=$id&page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
} else {
	$pagelink = mbi("*.")."前のページ | ";
}
if((($page + 1) * $LIMIT) < $rows) {
	$pagelink .= "<a href=\"./?id=$id&page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
} else {
	$pagelink .= mbi("#.")."次のページ";
}
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1><?=$boad->name?></h1>
<hr class="normal">
<p>
[<a href="./form.php?mode=thform&id=<?=$boad->sname?>"<?=mbi_ack(8)?>><?=mbi("8.")?>新規スレ</a>]
</p>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<?php
if(device_info() == 'mb') {
	echo "<div id=\"threadlist\">\n";
} else {
	echo "<ul id=\"threadlist\">\n";
}
if($result->num_rows) {
	$date = date("Y-m-d H:i:s", strtotime("-2 day"));
	while($array = $result->fetch_array()) {
		$thread = new Thread($array);
		$new = (strtotime($date) < strtotime($thread->updated)) ? "<span class=\"nc6\">New</span>" : "";
		if($thread->locked) {
			$marker = "※";
		} else if($thread->top) {
			$marker = "▼";
		} else {
			$marker = "▽";
		}
		$li_h = (device_info() == 'mb') ? "" : "<li>";
		$li_t = (device_info() == 'mb') ? "<br />\n" : "</li>\n";
?>
<?=$li_h?><span class="nc5"><?=$marker?></span><a href="./read.php?id=<?=$boad->sname?>&tid=<?=$thread->tid?>"><?=htmlspecialchars($thread->title)."(".$thread->mcount.")"?></a><?=$new.$li_t?>
<?php
	}
} else {
?>
<li>スレッドがありません</li>
<?php
}
if(device_info() == 'mb') {
	echo '</div>';
} else {
	echo '</ul>';
}
?>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<ul id="footlink">
<li><a href="/bbs/"<?=mbi_ack(9)?>><?=mbi("9.")?>掲示板一覧</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
pagefoot($boad->count);
?>
</div>
</body>
</html>
