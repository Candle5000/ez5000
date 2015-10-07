<?php
//=====================================
// アイテムデータ リスト閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/item.php");
$xml_file = "/var/www/functions/xml/item_group.xml";
session_start();

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
	$id = $_GET['id'];
} else {
	$id = 0;
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
?>
<html>
<head>
<?=pagehead($title)?>
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
	$data->select_column("id,name", "items", array("id", "hidden"), array("BETWEEN ".($id + 1)." AND ".item_group_end($id), "0"));
	while($row = $data->fetch()){
		$i_id = $row["id"];
		$i_name = $row["name"];
		$id_f = isset($data->is_admin) ? sprintf("%d:", $i_id) : "";
?>
<li><?=$id_f?><a href="/db/item/data/?id=<?=$i_id?>"><?=$i_name?></a></li>
<?php
	}
?>
</ul>
<?php
	if(isset($data->is_admin)) {
?>
<h2>未実装</h2>
<ul id="linklist">
<?php
		$data->select_column("id,name", "items", array("id", "hidden"), array("BETWEEN ".($id + 1)." AND ".item_group_end($id), "1"));
		while($row = $data->fetch()){
			$i_id = $row["id"];
			$i_name = $row["name"];
?>
<li><?=$i_id?>:<a href="/db/item/data/?id=<?=$i_id?>"><span class="nm"><?=$i_name?></span></a></li>
<?php
		}
		if($data->rows() == 0) {
?>
<li>特に無し</li>
<?php
		}
?>
</ul>
<?php
	}
?>
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
<li><a href="/db/item/eqsearch.php">装備アイテム検索</a></li>
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
<?php
}
?>
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

