<?php
//=====================================
// クエストデータ 個別データ閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/quest.php");
$xml = "/var/www/functions/xml/quest_group.xml";

if($id = isset($_GET['id'])) {
	$id = $_GET['id'];
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

$q_name = "";
if($data->is_added("quest", $id)) {
	$data->select_id("quest", $id);
	$quest = $data->fetch();
	$q_name = $quest["name"];
	$category = quest_category_array();
	$category_id = quest_category_id($category, $id);
	$category_name = $category[$category_id];
	$q_note = $data->data_link(nl2br($quest["note"]));
	if(!strlen($q_note)) {
		$q_note .= "準備中";
	}
	$q_updated = $quest["updated"];
	$q_count = $data->access_count("quest", $id, $quest["count"]);
} else {
	toppage();
}
$title = "クエストデータ $q_name";
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>クエストデータ</h1>
<h2><?=$q_name?></h2>
<p>
分類:<?=$category_name?><br />
<br />
<?=$q_note?>
</p>
<hr class="normal">
<ul id="footlink">
<li><a href="../?id=<?=$category_id?>"<?=mbi_ack(7)?>><?=mbi("7.")?><?=$category_name?></a></li>
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>クエストデータ</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?=pagefoot($q_count)?>
</div>
</body>
</html>
