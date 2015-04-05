<?php
//=====================================
// アイテムデータ リスト閲覧用
//=====================================
require_once("../../class/mysql.php");
require_once("../../class/guestdata.php");
require_once("../../functions/template.php");
require_once("../../functions/item.php");
$xml_file = "/var/www/functions/xml/item_group.xml";

$id = 0;
if(isset($_GET['id'])) {
	$id = $_GET['id'];
}

if(item_group($id) != -1) {
	$title = "アイテムデータ ".item_category(item_category_id($id))." ".item_group($id);
	$PAGE_ID = 20000 + (int)($id / 10);
} else {
	$title = "アイテムデータ";
	$PAGE_ID = 20000;
}

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database);
?>
<html>
<head>
<?pagehead($title)?>
</head>
<body>
<div id="all">
<?php
if(item_group($id) != -1) {
//グループ表示
?>
<h1>アイテムデータ</h1>
<hr class="normal">
<h2><?=item_category(item_category_id($id))?> <?=item_group($id)?></h2>
<ul id="linklist">
<?php
	$data->select_column("id,name", "items", "id", "BETWEEN ".($id + 1)." AND ".item_group_end($id));
	while($row = $data->fetch()){
		$id = $row["id"];
		$name = $row["name"];
?>
<li><a href="/db/item/data/?id=<?=$id?>"><?=$name?></a></li>
<?php
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
<h1>アイテムデータ</h1>
<hr class="normal">
<form action="/db/item/search.php" method="GET" enctype="multipart/form-data">
<input type="text" name="words" value=""><br />
<input type="radio" name="mode" value="AND" checked>AND 
<input type="radio" name="mode" value="OR">OR 
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
<ul id="linklist">
<li><a href="/db/item/updinfo.php">更新履歴</a></li>
</ul>
<?php
if(device_info() == 'mb') {
?>
<hr class="normal">
<div class="cnt">
<a href="#i1" accesskey="1">1.消</a>|<a href="#i2" accesskey="2">2.主</a>|<a href="#i3" accesskey="3">3.補</a>|<a href="#i4" accesskey="4">4.防</a>|<a href="#i5" accesskey="5">5.材</a>
</div>
<?php
}
$categories = simplexml_load_file($xml_file);
$i = 0;
foreach($categories->category as $category) {
	$i++;
?>
<?=mbi("<a name=\"i".$i."\">")?><h2><?=$category["name"]?></h2><?=mbi("</a>")?>
<ul id="linklist">
<?php
	foreach($category->group as $group) {
?>
<li><a href="/db/item/?id=<?=$group["id"]?>"><?=$group["name"]?></a></li>
<?php
	}
?>
</ul>
<?php
}
?>
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

