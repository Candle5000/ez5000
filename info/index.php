<?php
//=====================================
// インフォメーション一覧
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");

$PAGE_ID = 10200;
$PAGESIZE = 20;

$title = "インフォメーション";

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
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

$data = new GuestData($userName, $password, $database);
$rows = $data->select_all_l("*", "info", ($page * $PAGESIZE), $PAGESIZE, "id", "desc");

if(($page > 0) && ($rows > 0)) {
	$pagelink = "<a href=\"./?page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
} else {
	$pagelink = mbi("*.")."前のページ | ";
}
if((($page + 1) * $PAGESIZE) < $rows) {
	$pagelink .= "<a href=\"./?page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
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
<h1>インフォメーション</h1>
<hr class="normal">
<?=$rows?> 件中 <?=(($page * $PAGESIZE) + 1)?> - <?=(($page + 1) * $PAGESIZE)?> 件
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<?php
if($rows > 0) {
	while($row = $data->fetch()) {
?>
<div id="infobox">
<div id="date"><?=preg_replace("/-/", "/", $row["id"])?></div>
<p>
<span id="boxtitle">■<?=$row["subject"]?></span><br />
<?=nl2br($row["info"])?>
</p>
</div>
<?php
	}
}
?>
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
