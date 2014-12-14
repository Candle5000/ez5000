<?php
//=====================================
// アイテムデータ 個別データ閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/item.php");

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
$data = new GuestData($userName, $password, $database, 0);

$i_name = "";
if($data->is_added("items", $id)) {
	$data->select_id("items", $id);
	$item = $data->fetch();
	$i_name = $item["name"];
	$category = item_category_id($id);
	$categoryName = item_category($category);
	$group = item_group_id($id);
	$groupName = item_group($group);
	$i_text = nl2br(str_replace("  ", "　", $item["text"]));
	$i_rare = item_attribute($item["rare"]);
	$i_notrade = item_attribute($item["notrade"]);
	$i_price = item_price($item["price"]);
	$i_stack = $item["stack"];
	$i_note = nl2br($item["note"]);
	$i_updated = $item["updated"];
	$i_count = $data->access_count("items", $id, $item["count"]);
} else {
	toppage();
}
$title = "アイテムデータ $i_name";
?>
<html>
<head>
<?pagehead($title)?>
</head>
<body>
<div id="all">
<h1>アイテムデータ</h1>
<hr class="normal">
<div class="cnt">
<table border="1" id="item">
<tr><th colspan="2"><?=$i_name?></th></tr>
<tr><td class="cnt" width="18%">分類</td><td><?=$categoryName?>:<?=$groupName?></td></tr>
<tr><td colspan="2"><?=$i_text?></td></tr>
<tr><td colspan="2"><span class="<?=$i_rare?>">RARE</span> <span class="<?=$i_notrade?>">NOTRADE</span></td></tr>
<tr><td class="cnt">売却</td><td><?=$i_price?></td></tr>
<tr><td class="cnt">ｽﾀｯｸ</td><td><?=$i_stack?></td></tr>
<tr><td class="cnt">備考</td><td><?=$i_note?></td></tr>
<tr><td class="cnt">使用</td><td>準備中</td></tr>
<tr><td class="cnt">入手</td><td>準備中</td></tr>
<tr><td class="cnt">更新</td><td><?=$i_updated?></td></tr>
</table>
</div>
<hr class="normal">
<ul id="footlink">
<li><a href="../?id=<?=$group?>"<?=mbi_ack(7)?>><?=mbi("7.")?><?=$groupName?></a></li>
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>アイテムデータ</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?pagefoot($i_count)?>
</div>
</body>
</html>

