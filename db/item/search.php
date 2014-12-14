<?php
//=====================================
// アイテムデータ データ検索
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");

$PAGE_ID = 20010;

$hidden = 1;
if(isset($_GET['hidden'])) {
	if($_GET['hidden'] == 0) {
		setcookie("hidden", 0, time() + 86400);
	} else {
		setcookie("hidden", 0, time() - 3600);
	}
}
if(isset($_COOKIE['hidden'])) {
	$hidden = $_COOKIE['hidden'];
}

$title = "アイテム検索";

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database, $hidden);

if(isset($_GET["mode"])) {
	if($_GET["mode"] == "OR") {
		$mode = "OR";
	} else {
		$mode = "AND";
	}
} else {
	$mode = "AND";
}

if(isset($_GET["page"])) {
	if(preg_match("/[^0-9]/", $_GET["page"])) {
		$page = 0;
	} else {
		$page = $_GET["page"];
	}
} else {
	$page = 0;
}

if(isset($_GET["words"])) {
	$words = $_GET["words"];
	if(device_info() == 'mb' && isset($_GET["enc"])) {
		$enc = mb_detect_encoding($_GET["enc"]);
		$words = mb_convert_encoding($words, "UTF-8", $enc);
	}
	$rows = $data->search_words($words, "items", $mode, ($page * 50));
} else {
	$words = "";
	$rows = 0;
}

if(($page > 0) && ($rows > 0)) {
	$pagelink = "<a href=\"./search.php?words=".urlencode($words)."&mode=".$mode."&page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
} else {
	$pagelink = mbi("*.")."前のページ | ";
}
if((($page + 1) * 50) < $rows) {
	$pagelink .= "<a href=\"./search.php?words=".urlencode($words)."&mode=".$mode."&page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
} else {
	$pagelink .= mbi("#.")."次のページ";
}
?>
<html>
<head>
<?pagehead($title)?>
</head>
<body>
<div id="all">
<h1>アイテム検索</h1>
<hr class="normal">
<form action="<?=$_SERVER["PHP_SELF"]?>" method="GET" enctype="multipart/form-data">
<input type="text" name="words" value="<?=htmlentities($words, ENT_QUOTES, "utf-8")?>"><br />
<input type="radio" name="mode" <?=form_radio_checked("AND", $mode)?>>AND 
<input type="radio" name="mode" <?=form_radio_checked("OR", $mode)?>>OR 
<input type="hidden" name="page" value="0">
<?php
if(device_info() == 'mb') {
?>
<input type="hidden" name="enc" value="あ">
<?php
}
?>
<input type="submit" value="検索">
</form>
<hr class="normal">
[<?=htmlentities($words, ENT_QUOTES, "utf-8")?>]の検索結果<br />
<?=$rows?> 件中 <?=(($page * 50) + 1)?> - <?=(($page + 1) * 50)?> 件
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<ul id="linklist">
<?php
if($rows > 0) {
	while($row = $data->fetch()) {
		$id = $row["id"];
		$name = $row["name"];
?>
<li><a href="/db/item/data/?id=<?=$id?>"><?=$name?></a></li>
<?php
	}
}
?>
</ul>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<ul id="footlink">
<li><a href="./"<?=mbi_ack(9)?>><?=mbi("9.")?>アイテムデータ</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?
$data->select_id("accesscount", $PAGE_ID);
$c_data = $data->fetch();
pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
?>
</div>
</body>
</html>

