<?php
//=====================================
// スキルデータ 個別データ閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/skill.php");
$xml = "/var/www/functions/xml/skill_group.xml";

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

$s_name = "";
if($data->is_added("skill", $id)) {
	$data->select_id("skill", $id);
	$skill = $data->fetch();
	$s_name = $skill["name"];
	$categories = simplexml_load_file($xml);
	foreach($categories->category as $category) {
		foreach($category->group as $group) {
			if($skill["category"] == $group["id"]) {
				$category_id = $category["id"];
				$category_name = $category["name"];
				$group_id = $group["id"];
				$group_name = $group["name"];
			}
		}
	}
	$s_learning = $data->data_link(nl2br($skill["learning"]));
	$s_learning = preg_replace("/##group##/", $group_name, $s_learning);
	if(!strlen($s_learning)) {
		$s_learning .= "準備中";
	}
	$s_cost = $skill["cost"];
	$s_recast = skill_recast($skill["recast"]);
	$s_cast = skill_cast($skill["cast"]);
	$s_text = nl2br(str_replace("  ", "　", $skill["text"]));
	if(!strlen($s_text)) {
		$s_text .= "準備中";
	}
	$s_note = $data->data_link(nl2br($skill["note"]));
	if(!strlen($s_note)) {
		$s_note .= "準備中";
	}
	if($skill["ep"] == 0) {
		$s_ep = "";
	} else {
		$s_ep = "<br />EP:".$skill["ep"];
	}
	$s_enhance = $data->data_link(nl2br($skill["enhance"]));
	$s_updated = $skill["updated"];
	$s_count = $data->access_count("skill", $id, $skill["count"]);
} else {
	toppage();
}
$title = "スキルデータ $s_name";
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>スキルデータ</h1>
<hr class="normal">
<div class="cnt">
<table border="1" id="item">
<tr><th colspan="2"><?=$s_name?></th></tr>
<tr><td class="cnt" width="24%">分類</td><td><?=$category_name?>:<?=$group_name?></td></tr>
<?php
if($category_id == 1 || $category_id == 4) {
?>
<tr><td class="cnt">習得</td><td><?=$s_learning?></td></tr>
<?php
}
?>
<tr><td class="cnt">消費SP</td><td><?=$s_cost?></td></tr>
<tr><td class="cnt">再使用</td><td><?=$s_recast?></td></tr>
<tr><td class="cnt">詠唱</td><td><?=$s_cast?></td></tr>
<tr><td colspan="2"><?=$s_text?></td></tr>
<tr><td colspan="2"><?=$s_note?></td></tr>
<?php
if(strlen($s_enhance)) {
?>
<tr><td class="cnt">強化<?=$s_ep?></td><td><?=$s_enhance?></td></tr>
<?php
}
?>
<tr><td class="cnt">更新</td><td><?=$s_updated?></td></tr>
</table>
</div>
<hr class="normal">
<ul id="footlink">
<li><a href="../?id=<?=$group_id?>"<?=mbi_ack(7)?>><?=mbi("7.")?><?=$group_name?></a></li>
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>スキルデータ</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?=pagefoot($s_count)?>
</div>
</body>
</html>
