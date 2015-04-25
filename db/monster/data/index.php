<?php
//=====================================
// モンスターデータ 個別データ閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/monster.php");

$category = monster_category();
$walkspeed = monster_walkspeed();
$search = monster_search();

if(isset($_GET['id'])) {
	$zone = floor($_GET['id'] / 10000);
	$id = $_GET['id'] % 10000;
} else {
	$zone = 0;
	$id = 0;
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

$m_name = "";
$column = array("zone", "id");
$value = array($zone, $id);
$data->select_column("*", "monster", $column, $value);
if($data->rows()) {
	$monster = $data->fetch();
	$m_name = $monster["name"];
	$m_nm = $monster["nm"] ? " class=\"nm\"" : "";
	$data->select_id("zone", $zone);
	$zoneData = $data->fetch();
	$zoneName = $zoneData["name"];
	$m_categoryId = $monster["category"];
	$m_categoryName = $category[$m_categoryId];
	$data->select_column("id", "monster", "category", $m_categoryId);
	$link_id = ($data->rows() < 5) ? 900 : $m_categoryId;
	$m_image = $monster["image"];
	$m_walkspeed = $walkspeed[$monster["walkspeed"]];
	$m_delay = monster_delay($monster["delay"]);
	$m_search = $search[$monster["search"]];
	$m_follow = ($monster["follow"] == "") ? "不明" : $data->data_link(nl2br($monster["follow"]));
	$m_link = ($monster["link"] == "") ? "不明" : $data->data_link(nl2br($monster["link"]));
	$m_level = monster_level($monster["maxlevel"], $monster["minlevel"]);
	$m_repop = ($monster["repop"] == "") ? "不明" : $data->data_link(nl2br($monster["repop"]));
	$m_skill = ($monster["skill"] == "") ? "不明" : $data->data_link(nl2br($monster["skill"]));
	$m_dropitem = ($monster["dropitem"] == "") ? "" : monster_drop($data->data_link($monster["dropitem"]));
	$m_soul = monster_item($data, $monster["soul"]);
	$m_steal = monster_item($data, $monster["steal"]);
	$m_note = ($monster["note"] == "") ? "特になし" : $data->data_link(nl2br($monster["note"]));
	$m_event = $monster["event"];
	$m_updated = $monster["updated"];
	$m_count = $data->access_count("monster", $_GET['id'], $monster["count"]);
} else {
	toppage();
}
$title = "モンスターデータ $m_name";
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>モンスターデータ</h1>
<hr class="normal">
<div class="cnt">
<table border="1" id="item">
<tr><th colspan="2"<?=$m_nm?>><?=$m_name?></th></tr>
<tr><td class="cnt" colspan="2">画像:準備中</td></tr>
<tr><td class="cnt" width="18%">ｿﾞｰﾝ</td><td><?=$zoneName?></td></tr>
<tr><td class="cnt">種族</td><td><?=$m_categoryName?></td></tr>
<tr><td class="cnt">移動</td><td><?=$m_walkspeed?></td></tr>
<tr><td class="cnt">攻速</td><td><?=$m_delay?></td></tr>
<tr><td class="cnt">索敵</td><td><?=$m_search?></td></tr>
<tr><td class="cnt">追尾</td><td><?=$m_follow?></td></tr>
<tr><td class="cnt">ﾘﾝｸ</td><td><?=$m_link?></td></tr>
<tr><td class="cnt">ﾚﾍﾞﾙ</td><td><?=$m_level?></td></tr>
<tr><td class="cnt">出現</td><td><?=$m_repop?></td></tr>
<tr><td class="cnt">ｽｷﾙ</td><td><?=$m_skill?></td></tr>
<tr><td colspan="2">ドロップ</td></tr>
<?php
if($m_dropitem == "") {
?>
<tr><td colspan="2">不明</td></tr>
<?php
} else if($m_dropitem == -1 || (!isset($m_dropitem["list"][-1]) && !isset($m_dropitem["list"][0]))) {
?>
<tr><td class="cnt"></td><td>ERROR:データ読込に失敗</td></tr>
<?php
} else if(count($m_dropitem["list"]) == 1 && isset($m_dropitem["list"][-1])) {
?>
<tr><td class="cnt">枠不明</td><td>
<?=$m_dropitem["list"][-1]?>
</td></tr>
<?php
} else {
	for($i = 0; isset($m_dropitem["list"][$i]); $i++) {
		$trclass = isset($m_dropitem["head"][$i]) ? " class=\"rare\"" : "";
		$frame = (isset($m_dropitem["head"][$i]) && $m_dropitem["head"][$i] == 2) ? "特殊".($i + 1) : "枠".($i + 1);
?>
<tr><td class="cnt"><?=$frame?></td><td<?=$trclass?>>
<?=$m_dropitem["list"][$i]?>
</td></tr>
<?php
	}
}
?>
<tr><td class="cnt">ｿｳﾙ</td><td><?=$m_soul?></td></tr>
<tr><td class="cnt">ｽﾃｨｰﾙ</td><td><?=$m_steal?></td></tr>
<tr><td class="cnt">備考</td><td><?=$m_note?></td></tr>
<tr><td class="cnt">更新</td><td><?=$m_updated?></td></tr>
</table>
</div>
<hr class="normal">
<ul id="footlink">
<li><a href="../?id=<?=$link_id?>"<?=mbi_ack(7)?>><?=mbi("7.")?><?=$category[$link_id]?></a></li>
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>モンスターデータ</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?=pagefoot($m_count)?>
</div>
</body>
</html>
