<?php
//=====================================
// モンスターデータ リスト閲覧用
//=====================================
require_once("../../class/mysql.php");
require_once("../../class/guestdata.php");
require_once("../../functions/template.php");
require_once("../../functions/monster.php");
$cname = monster_category();

$id = isset($_GET['id']) ? $_GET['id'] : -1;

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database, 0);

$data->select_column("id", "monster", "category", $id);
if($data->rows() < 5 && $id != 0) $id = -1;

if($id != -1) {
	$title = "モンスターデータ ".$cname[$id];
	$PAGE_ID = 50000 + ($id * 10);
} else {
	$title = "モンスターデータ";
	$PAGE_ID = 50000;
}
?>
<html>
<head>
<?pagehead($title)?>
</head>
<body>
<div id="all">
<?php
if($id != -1) {
//種族別一覧表示
?>
<h1>モンスターデータ</h1>
<hr class="normal">
<h2><?=$cname[$id]?></h2>
<ul id="linklist">
<?php
	if($id == 0) {
		$data->select_group_by("category", "monster", "", "category", "HAVING COUNT(id) < 5");
		while($rows = $data->fetch()) {
			$categories[] = $rows["category"];
		}
	} else {
		$categories[] = $id;
	}
	foreach($categories as $category) {
		$data->select_column("zone,id,name,nm", "monster", "category", $category);
		while($row = $data->fetch()) {
			$id = $row["zone"].str_pad($row["id"], 4, "0", STR_PAD_LEFT);
			$name = $row["name"];
			if($row["nm"]) $name = '<span class="nm">'.$name.'</span>';
?>
<li><a href="/db/monster/data/?id=<?=$id?>"><?=$name?></a></li>
<?php
		}
	}
?>
</ul>
<hr class="normal">
<ul id="footlink">
<li><a href="./"<?=mbi_ack(9)?>><?=mbi("9.")?>アイテムデータ</a></li>
<?php
} else {
//一覧表示
?>
<h1>モンスターデータ</h1>
<ul id="linklist">
<?php
	$data->select_group_by("category", "monster", "", "category", "HAVING COUNT(id) >= 5");
	while($rows = $data->fetch()) {
		$id = $rows["category"];
?>
<li><a href="/db/monster/?id=<?=$id?>"><?=$cname[$id]?></a></li>
<?php
	}
?>
<li><a href="/db/monster/?id=0"><?=$cname[0]?></a></li>
</ul>
<hr class="normal">
<ul id="footlink">
<?
}
?>
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
