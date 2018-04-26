<?php
//=====================================
// ゾーンデータ リスト閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
session_start();

$id = (isset($_GET["id"]) && is_numeric($_GET['id'])) ? $_GET["id"] : 0;
$mode = (isset($_GET["mode"]) && ($_GET["mode"] == 1 || $_GET["mode"] == 2)) ? $_GET["mode"] : 0;

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

if($id == 0) {
	$title = "ゾーンデータ";
	$PAGE_ID = 30000;
} else {
	if(!$data->select_id("zone", $id)) toppage();
	$row = $data->fetch();
	$name = $row["name"];
	$nameE = $row["nameE"];
	$event = $row["event"];
	$image = file_exists("/var/www/img/zone/".sprintf("%03d", $id).$mode.".gif") ? sprintf("%03d", $id).$mode : "0000";
	$z_count = $data-> access_count("zone", $id, $row["count"]);
	$title = "ゾーンデータ $name";
	if($mode == 1) $title .= " クエスト";
	if($mode == 2) $title .= " モンスター";
}
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<?php
if($id == 0) {
//一覧表示
?>
<h1>ゾーンデータ</h1>
<hr class="normal">
<?php
	for($i = 0; $i < 2; $i++) {
		$flag = true;
		if($i == 1) echo "<h2>イベント</h2>\n";
?>
<ul class="linklist">
<?php
		$data->select_column("id,name,enabled", "zone", array("event", "enabled"), array($i, 1));
		while($row = $data->fetch()){
			$z_id = $row["id"];
			$z_name = $row["name"];
			$id_f = isset($data->is_admin) ? sprintf("%03d:", $z_id) : "";
			if($flag && $z_id > 200) {
				$flag = false;
				echo "<br />\n";
			}
?>
<li><?=$id_f?><a href="./?id=<?=$z_id?>"><?=$z_name?></a></li>
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
} else {
//詳細表示
?>
<h1>ゾーンデータ</h1>
<hr class="normal">
<h2><?=$name?><br /><?=$nameE?></h2>
<div class="img"><img src="/img/zone/<?=$image?>.gif" class="map" /></div>
<h2>表示切替</h2>
<ul class="linklist">
<?php
if($mode != 0) echo "<li><a href=\"./?id=$id\">総合情報</a></li>";
if($mode != 1) echo "<li><a href=\"./?id=$id&mode=1\">クエスト情報</a></li>";
if($mode != 2) echo "<li><a href=\"./?id=$id&mode=2\">モンスター情報</a></li>";
?>
</ul>
<?php
	if($mode == 0) {
?>
<h2>ショップ</h2>
<ul class="linklist">
<?php
		if($data->select_column_a("id,name", "quest", "id BETWEEN 10000 AND 20000 AND note LIKE '%##z$id##%'")) {
			while($row = $data->fetch()) {
				$shop_id = $row["id"];
				$shop_name = preg_replace("/^[^A-Z]+/", "", $row["name"]);
?>
<li><a href="/db/quest/data/?id=<?=$shop_id?>"><?=$shop_name?></a></li>
<?php
			}
		} else {
?>
<li>特になし</li>
<?php
		}
?>
</ul>
<?php
		if($data->select_column_a("id,name", "quest", "id BETWEEN 30000 AND 50000 AND note LIKE '%##z$id##%'")) {
?>
<h2>製作</h2>
<ul class="linklist">
<?php
			while($row = $data->fetch()) {
				$shop_id = $row["id"];
				$shop_name = preg_replace("/^[^A-Z]+/", "", $row["name"]);
?>
<li><a href="/db/quest/data/?id=<?=$shop_id?>"><?=$shop_name?></a></li>
<?php
			}
?>
</ul>
<?php
		}
		if($data->select_column_a("id,name", "quest", "id BETWEEN 20000 AND 30000 AND note LIKE '%##z$id##%'")) {
?>
<h2>宝箱</h2>
<ul class="linklist">
<?php
			while($row = $data->fetch()) {
				$chest_id = $row["id"];
				$chest_name = $row["name"];
?>
<li><a href="/db/quest/data/?id=<?=$chest_id?>"><?=$chest_name?></a></li>
<?php
			}
?>
</ul>
<?php
		}
	} else if($mode == 1) {
?>
<h2>クエスト</h2>
<ul class="linklist">
<?php
		if($data->select_column_a("id,name", "quest", "id BETWEEN 50000 AND 90000 AND note LIKE '%##z$id##%'")) {
			while($row = $data->fetch()) {
				$q_id = $row["id"];
				$q_name = $row["name"];
?>
<li><a href="/db/quest/data/?id=<?=$q_id?>"><?=$q_name?></a></li>
<?php
			}
		} else if($event && $data->select_column_a("id,name", "quest", "id > 90000 AND note LIKE '%##z$id##%'")) {
			while($row = $data->fetch()) {
				$q_id = $row["id"];
				$q_name = $row["name"];
?>
<li><a href="/db/quest/data/?id=<?=$q_id?>"><?=$q_name?></a></li>
<?php
			}
		} else {
?>
<li>特になし</li>
<?php
		}
?>
</ul>
<?php
	} else if($mode == 2) {
?>
<h2>モンスター</h2>
<ul class="linklist">
<?php
		if($data->select_column_a("id,name,nm", "monster", "zone=$id AND event=0")) {
			while($row = $data->fetch()) {
				$m_id = $id.sprintf("%04d", $row["id"]);
				$m_name = $row["name"];
				if($row["nm"]) $m_name = "<span class=\"nm\">$m_name</span>";
?>
<li><a href="/db/monster/data/?id=<?=$m_id?>"><?=$m_name?></a></li>
<?php
			}
		} else if($event && $data->select_column_a("id,name,nm", "monster", "zone=$id AND event=1")) {
			while($row = $data->fetch()) {
				$m_id = $id.sprintf("%04d", $row["id"]);
				$m_name = $row["name"];
				if($row["nm"]) $m_name = "<span class=\"nm\">$m_name</span>";
?>
<li><a href="/db/monster/data/?id=<?=$m_id?>"><?=$m_name?></a></li>
<?php
			}
		} else {
?>
<li>特になし</li>
<?php
		}
?>
</ul>
<?php
	}
?>
<hr class="normal">
<ul id="footlink">
<li><a href="./"<?=mbi_ack(9)?>><?=mbi("9.")?>ゾーンデータ</a></li>
<?php
}
?>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
if($id == 0) {
	$data->select_id("accesscount", $PAGE_ID);
	$c_data = $data->fetch();
	pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
} else {
	pagefoot($z_count);
}
?>
</div>
</body>
</html>
