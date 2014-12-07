<?php
//=====================================
// クラスデータ 詳細データ閲覧用
//=====================================
require_once("../../../class/mysql.php");
require_once("../../../class/guestdata.php");
require_once("../../../functions/template.php");
require_once("../../../functions/class.php");
$MAX_Lv = 55;

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

$title = "クラスデータ ";
if($data->is_added("class", $id)) {
	$data->search_id("class", $id);
	$class = $data->fetch();
	$name = $class["name"];
	$nameE = $class["nameE"];
	$nameS = $class["nameS"];
	$dagger = $class["dagger"];
	$sword = $class["sword"];
	$axe = $class["axe"];
	$hammer = $class["hammer"];
	$wand = $class["wand"];
	$bow = $class["bow"];
	$dodge = $class["dodge"];
	$shield = $class["shield"];
	$element = $class["element"];
	$light = $class["light"];
	$dark = $class["dark"];
	$note = nl2br($class["note"]);
	$updated = $class["updated"];
	$count = $data->access_count("class", $id, $class["count"]);
	$title = $title.$name;
} else {
	toppage();
}
?>
<html>
<head>
<?pagehead($title)?>
</head>
<body>
<div id="all">
<h1>クラスデータ</h1>
<hr class="normal">
<?php
if(isset($class)) {
	if($id > 200) {
		$classtype = "元ｸﾗｽ";
		$classid = (int)(($id - 1) / 2) + 1;
		$data->search_id("class", $classid);
		$c0 = $data->fetch();
		$classlist = "<a href=\"./?id=".$classid."\">".$c0["name"]."</a>";
	} else {
		$classtype = "上級ｸﾗｽ";
		$classid = $id * 2;
		$data->select_group(($classid - 1), $classid, "id,name", "class");
		$c1 = $data->fetch();
		$c2 = $data->fetch();
		$classlist = "<a href=\"./?id=".$c1["id"]."\">".$c1["name"]."</a><br />\n<a href=\"./?id=".$c2["id"]."\">".$c2["name"]."</a>";
	}
}
?>
<div class="cnt">
<table border="1" id="class">
<tr><th colspan="4"><?=$name?> (<?=$nameE?>)</th></tr>
<tr><td colspan="4">武器スキル/魔法スキル</td></tr>
<tr class="cnt"><td width="25%">短剣:<span class="<?=$dagger?>"><?=$dagger?></span></td><td width="25%">長剣:<span class="<?=$sword?>"><?=$sword?></span></td><td width="25%">　斧:<span class="<?=$axe?>"><?=$axe?></span></td><td width="25%">　槌:<span class="<?=$hammer?>"><?=$hammer?></span></td></tr>
<tr class="cnt"><td>　杖:<span class="<?=$wand?>"><?=$wand?></span></td><td>　弓:<span class="<?=$bow?>"><?=$bow?></span></td><td>回避:<span class="<?=$dodge?>"><?=$dodge?></span></td><td>　盾:<span class="<?=$shield?>"><?=$shield?></span></td></tr>
<tr class="cnt"><td>元素:<span class="<?=$element?>"><?=$element?></span></td><td>　光:<span class="<?=$light?>"><?=$light?></span></td><td>　闇:<span class="<?=$dark?>"><?=$dark?></span></td><td></td></tr>
<tr><td colspan="4">習得スキル(準備中)</td></tr>
<tr><td class="cnt"><?=$classtype?></td><td colspan="3"><?=$classlist?></td></tr>
<tr><td colspan="4"><?=$note?></td></tr>
</table>
<table border="1" id="status">
<tr><th colspan="9">ステータス</td></tr>
<tr class="small"><td>Lv</td><td width="14%">HP</td><td width="14%">SP</td><td width="11%">STR</td><td width="11%">VIT</td><td width="11%">DEX</td><td width="11%">AGI</td><td width="11%">WIS</td><td width="11%">WIL</td></tr>
<?php
$data->select_group(1, $MAX_Lv, "*", $nameS);
while($st = $data->fetch()) {
	if(($st["lv"] % 5 == 0) || ($st["lv"] == 1)) {
		echo "<tr><td>".$st["lv"]."</td><td>".$st["hp"]."</td><td>".$st["sp"]."</td><td>".$st["str"]."</td><td>".$st["vit"]."</td><td>".$st["dex"]."</td><td>".$st["agi"]."</td><td>".$st["wis"]."</td><td>".$st["wil"]."</td></tr>";
	}
}
?>
<tr><td colspan="2" class="cnt">更新</td><td colspan="7" class="lft"><?=$updated?></td></tr>
</table>
</div>
<hr class="normal">
<ul id="footlink">
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>クラスデータ</a></li>
<li><a href="/database/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<hr class="normal">
<?pagefoot($count)?>
</div>
</body>
</html>

