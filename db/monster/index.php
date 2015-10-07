<?php
//=====================================
// モンスターデータ リスト閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/monster.php");
$cname = monster_category();
session_start();

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
	$id = $_GET['id'];
} else {
	$id = -1;
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

//種族個体数チェック
$data->select_column("id", "monster", "category", $id);
if($data->rows() < 5 && $id != 900) $id = -1;

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
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<?php
if($id != -1) {
//種族別一覧表示
?>
<h1>モンスターデータ</h1>
<hr class="normal">
<?php
	for($i = 0; $i < 2; $i++) {
		if($i == 0) {
?>
<h2><?=$cname[$id]?></h2>
<?php
		} else {
?>
<h2>イベントモンスター</h2>
<?php
		}
?>
<ul id="linklist">
<?php
		$flag = 0;

		//種族ID取得
		if($id == 900) {
			$data->select_group_by("category", "monster", "", "category", "HAVING COUNT(id) < 5");
			while($rows = $data->fetch()) {
				$categories[] = $rows["category"];
			}
		} else {
			$categories[] = $id;
		}

		//検索出力
		foreach($categories as $category) {
			$column = array("category", "event");
			$value = array($category, $i);
			$data->select_column_p("zone,monster.id,monster.name,nm,nameS", "zone,monster", "category=$category and monster.event=$i and zone.id=zone", 0, 0, "zone,monster.id");
			while($row = $data->fetch()) {
				$flag = 1;
				$id = $row["zone"].str_pad($row["id"], 4, "0", STR_PAD_LEFT);
				$name = $row["name"];
				$id_f = isset($data->is_admin) ? sprintf("%07d:", $id) : "";
				if($row["nm"]) $name = '<span class="nm">'.$name.'</span>';
?>
<li><?=$id_f?><a href="/db/monster/data/?id=<?=$id?>"><?=$name?>@<?=$row["nameS"]?></a></li>
<?php
			}
		}
		
		unset($categories);

		//ヒット件数0
		if($flag == 0) {
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
<li><a href="./"<?=mbi_ack(9)?>><?=mbi("9.")?>モンスターデータ</a></li>
<?php
} else {
//一覧表示
?>
<h1>モンスターデータ</h1>
<ul id="linklist">
<li><a href="./updinfo.php">更新履歴</a></li>
</ul>
<hr class="normal">
<ul id="linklist">
<?php
	$data->select_group_by("category", "monster", "", "category", "HAVING COUNT(id) >= 5");
	while($rows = $data->fetch()) {
		$id = $rows["category"];
		$o_flag = ($id == 900);
?>
<li><a href="/db/monster/?id=<?=$id?>"><?=$cname[$id]?></a></li>
<?php
	}

	if(!$o_flag) {
?>
<li><a href="/db/monster/?id=900"><?=$cname[900]?></a></li>
<?php
	}
?>
</ul>
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
