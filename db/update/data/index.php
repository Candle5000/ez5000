<?php
//=====================================
// アプリ更新情報 個別データ閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");

if($id = isset($_GET['id']) && preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $_GET["id"])) {
	$id = $_GET['id'];
} else {
	//toppage();
	die("0");
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
if(mysqli_connect_error()) {
	die("データベースの接続に失敗しました");
}

if($data->select_id("updinfo", $id)) {
	$updinfo = $data->fetch();
	$detail = $data->data_link(nl2br($updinfo["detail"]));
	if(!strlen($detail)) {
		$detail .= "準備中";
	}
	$count = $data->access_count("updinfo", $id, $updinfo["count"]);
} else {
	//toppage();
	die("1");
}
$title = "アプリ更新情報 $id";
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>アプリ更新情報</h1>
<h2><?=$id?></h2>
<p>
<?=$detail?>
</p>
<hr class="normal">
<ul id="footlink">
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>アプリ更新情報</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?=pagefoot($count)?>
</div>
</body>
</html>
