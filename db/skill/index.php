<?php
//=====================================
// スキルデータ リスト閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/skill.php");
$xml = "/var/www/functions/xml/skill_group.xml";
session_start();

$id = 0;
if(isset($_GET['id'])) {
	$id = $_GET['id'];
}

$categories = simplexml_load_file($xml);
foreach($categories->category as $category) {
	foreach($category->group as $group) {
		if($id == $group["id"]) {
			$name["category"] = $category["name"];
			$name["group"] = $group["name"];
		}
	}
}

if(isset($name)) {
	$title = "スキルデータ ".$name["category"]." ".$name["group"];
	$PAGE_ID = 70000 + ($id * 100);
} else {
	$title = "スキルデータ";
	$PAGE_ID = 70000;
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
if(isset($name)) {
//グループ表示
?>
<h1>スキルデータ</h1>
<hr class="normal">
<h2><?=$name["category"]?> <?=$name["group"]?></h2>
<ul id="linklist">
<?php
	$data->select_column("id,name", "skill", "category", $id);
	while($row = $data->fetch()){
		$id = $row["id"];
		$name = $row["name"];
		$id_f = isset($data->is_admin) ? sprintf("%04d:", $id) : "";
?>
<li><?=$id_f?><a href="./data/?id=<?=$id?>"><?=$name?></a></li>
<?php
	}
?>
</ul>
<hr class="normal">
<ul id="footlink">
<li><a href="./"<?=mbi_ack(9)?>><?=mbi("9.")?>スキルデータ</a></li>
<?php
} else {
//一覧表示
?>
<h1>スキルデータ</h1>
<hr class="normal">
<?php
if(device_info() == 'mb') {
?>
<hr class="normal">
<div class="cnt">
<a href="#s1" accesskey="1">1.クラス</a>|<a href="#s2" accesskey="2">2.魔法</a>|<a href="#s3" accesskey="3">3.モンスター</a>
</div>
<?php
}
$i = 0;
foreach($categories->category as $category) {
	$i++;
?>
<?=mbi("<a name=\"s".$i."\">")?><h2><?=$category["name"]?></h2><?=mbi("</a>")?>
<ul id="linklist">
<?php
	foreach($category->group as $group) {
?>
<li><a href="./?id=<?=$group["id"]?>"><?=$group["name"]?></a></li>
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
