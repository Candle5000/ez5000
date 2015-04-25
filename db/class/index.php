<?php
//=====================================
// クラスデータ リスト閲覧用
//=====================================
require_once("../../class/mysql.php");
require_once("../../class/guestdata.php");
require_once("../../functions/template.php");
require_once("../../functions/class.php");

$PAGE_ID = 60000;
$title = "クラスデータ";
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
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>クラスデータ</h1>
<hr class="normal">
<ul id="linklist">
<?php
$data->select_all("class");
while($row = $data->fetch()) {
	$id = $row["id"];
	$name = $row["name"];
	if($id == 201) {
?>
</ul>
<h2>上級クラス</h2>
<ul id="linklist">
<?php
	}
?>
<li><a href="./data/?id=<?=$id?>"><?=$name?></a></li>
<?php
}
?>
</ul>
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

