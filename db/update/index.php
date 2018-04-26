<?php
//=====================================
// アプリ更新情報 リスト閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
session_start();

$PAGE_ID = 16000;
$LIMIT = 50;
$title = "アプリ更新情報";

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

$rows = $data->select_all_l("id", "updinfo", $page * $LIMIT, $LIMIT, "id desc");

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
<h1>アプリ更新情報</h1>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<ul class="linklist">
<?php
if($rows > 0) {
	$upd = 0;
	while($row = $data->fetch()) {
		$id = $row["id"];
?>
<li><a href="/db/update/data/?id=<?=$id?>"><?=$id?></a></li>
<?php
	}
}
?>
</ul>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<ul id="footlink">
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
