<?php
//=====================================
// モンスターデータ 更新情報
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/monster.php");

$PAGE_ID = 50020;
$LIMIT = 50;
$title = "モンスターデータ更新履歴";

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database);

if(isset($_GET["page"])) {
	if(preg_match("/[^0-9]/", $_GET["page"])) {
		$page = 0;
	} else {
		$page = $_GET["page"];
	}
} else {
	$page = 0;
}

$rows = $data->select_column_p("zone,monster.id,monster.name,nm,nameS,monster.updated", "zone,monster", "zone.id=zone", $page * $LIMIT, $LIMIT, "monster.updated desc,zone,monster.id");

if(($page > 0) && ($rows > 0)) {
	$pagelink = "<a href=\"./updinfo.php?page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
} else {
	$pagelink = mbi("*.")."前のページ | ";
}
if((($page + 1) * 50) < $rows) {
	$pagelink .= "<a href=\"./updinfo.php?page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
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
<h1>更新履歴</h1>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<?php
if($rows > 0) {
	$upd = 0;
	while($row = $data->fetch()) {
		$id = $row["zone"].str_pad($row["id"], 4, "0", STR_PAD_LEFT);
		$name = $row["name"];
		if($row["nm"]) $name = '<span class="nm">'.$name.'</span>';
		if($updflag = ($upd != $row["updated"])) {
			if($upd != 0) {
?>
</ul>
<?php
			}
			$upd = $row["updated"];
?>
<h2><?=$upd?></h2>
<ul id="linklist">
<?php
		}
?>
<li><a href="/db/monster/data/?id=<?=$id?>"><?=$name?>@<?=$row["nameS"]?></a></li>
<?php
	}
}
?>
</ul>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<ul id="footlink">
<li><a href="./"<?=mbi_ack(9)?>><?=mbi("9.")?>モンスターデータ</a></li>
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
