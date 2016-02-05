<?php
//=====================================
// アイテムデータ 更新情報
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");
session_start();

$PAGE_ID = 20020;
$LIMIT = 50;
$group = item_group();
$title = "アイテムデータ更新履歴";

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

if(isset($_GET["page"])) {
	if(preg_match("/[^0-9]/", $_GET["page"])) {
		$page = 0;
	} else {
		$page = $_GET["page"];
	}
} else {
	$page = 0;
}

if(isset($data->is_admin)) {
	$rows = $data->select_all_l("id,name,updated,hidden", "items", $page * $LIMIT, $LIMIT, "updated desc, id");
} else {
	$rows = $data->select_column_p("id,name,updated,hidden", "items", "hidden=0", $page * $LIMIT, $LIMIT, "updated desc, id");
}

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
		$id = $row["id"];
		$groupName = isset($group[item_group_id($id)]) ? $group[item_group_id($id)] : "不明";
		$name = $row["hidden"] ? "<span class=\"nm\">".$row["name"]."</span>" : $row["name"];
		$id_f = isset($data->is_admin) ? sprintf("%d:", $id) : "";
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
<li><?=$id_f?><a href="/db/item/data/?id=<?=$id?>"><?=$name?></a>(<?=$groupName?>)</li>
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
