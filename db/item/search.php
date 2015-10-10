<?php
//=====================================
// アイテムデータ データ検索
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");
session_start();

$PAGE_ID = 20010;
$title = "アイテム検索";

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
if(isset($_SESSION["user"]) && isset($_SESSION["pass"])) {
	$data = new AdminData($_SESSION["user"], $_SESSION["pass"], "ezdata");
	if(!$data->is_admin) {
		session_destroy();
		die("データベースの接続に失敗しました");
	}
} else {
	$data = new GuestData($userName, $password, $database);
}
if(mysqli_connect_error()) {
	die("データベースの接続に失敗しました");
}

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
		if(isset($_GET["enc"])) {
			if(mb_convert_encoding($_GET["enc"], "UTF-8", "SJIS-WIN") == "あ") {
				$words = mb_convert_encoding($words, "UTF-8", "SJIS-WIN");
			} else if(urldecode($_GET["enc"]) == "あ") {
				$words = urldecode($words);
			} else if(mb_convert_encoding(urldecode($_GET["enc"]), "UTF-8", "SJIS-WIN") == "あ") {
				$words = mb_convert_encoding(urldecode($words), "UTF-8", "SJIS-WIN");
			}
		}
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
<?=pagehead($title)?>
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
		$id_f = isset($data->is_admin) ? sprintf("%d:", $id) : "";
?>
<li><?=$id_f?><a href="/db/item/data/?id=<?=$id?>"><?=$name?></a></li>
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
<?php
$data->select_id("accesscount", $PAGE_ID);
$c_data = $data->fetch();
pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
?>
</div>
</body>
</html>

