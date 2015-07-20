<?php
//=====================================
// クエストデータ リスト閲覧用
//=====================================
require_once("../../class/mysql.php");
require_once("../../class/guestdata.php");
require_once("../../functions/template.php");
require_once("../../functions/quest.php");
$xml = "/var/www/functions/xml/quest_group.xml";

$category = quest_category_array();
$id = (isset($_GET['id']) && isset($category[$_GET['id']])) ? $_GET['id'] : 0;

if($id == 0) {
	$title = "クエストデータ";
	$PAGE_ID = 40000;
} else {
	$title = "クエストデータ ".$category[$id];
	$PAGE_ID = 40000 + ($id * 100);
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
<h1>クエストデータ</h1>
<hr class="normal">
<ul id="linklist">
<?php
foreach($category as $c_id => $c_name) {
?>
<li><a href="./?id=<?=$c_id?>"><?=$c_name?></a></li>
<?php
}
?>
</ul>
<hr class="normal">
<ul id="footlink">
<?php
} else {
//グループ表示
?>
<h1>クエストデータ</h1>
<hr class="normal">
<h2><?=$category[$id]?></h2>
<ul id="linklist">
<?php
	$end = quest_category_end($category, $id);
	$data->select_column("id,name", "skill", "id", "BETWEEN $id AND $end");
	while($row = $data->fetch()){
		$q_id = $row["id"];
		$q_name = $row["name"];
?>
<li><a href="./data/?id=<?=$q_id?>"><?=$q_name?></a></li>
<?php
	}
?>
</ul>
<hr class="normal">
<ul id="footlink">
<li><a href="./"<?=mbi_ack(9)?>><?=mbi("9.")?>クエストデータ</a></li>
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
