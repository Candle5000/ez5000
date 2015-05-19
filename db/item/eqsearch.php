<?php
//=====================================
// アイテムデータ データ検索
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");

$PAGE_ID = 20030;
$title = "装備アイテム検索";
$MAX_LV = "60";

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

$eqType = array("短剣", "長剣", "斧", "槌", "杖", "弓", "矢", "盾", "書物", "頭", "胴", "脚", "足", "首", "腰", "背", "装飾品", "魂");
$maxLv = 60;
$status = array(
	0 => "指定なし",
	110 => "DMG",
	111 => "DELAY",
	112 => "D/D",
	120 => "HP",
	121 => "SP",
	122 => "STR",
	123 => "VIT",
	124 => "DEX",
	125 => "AGI",
	126 => "WIS",
	127 => "WIL",
	130 => "攻",
	131 => "防",
	132 => "命中",
	133 => "回避",
	134 => "魔攻",
	135 => "魔命",
	136 => "魔抵",
	137 => "遠攻",
	138 => "遠命",
	140 => "敵意",
	141 => "詠唱速度",
	142 => "攻撃速度",
	143 => "詠唱中断率",
	144 => "攻撃中断率",
	146 => "詠唱妨害",
	147 => "攻撃妨害",
	148 => "妨害",
	150 => "貫通",
	151 => "ヒール回復量",
	152 => "盾防御発動率UP",
	160 => "PROC:火属性DMG",
	161 => "PROC:水属性DMG",
	162 => "PROC:土属性DMG",
	163 => "PROC:風属性DMG",
	164 => "PROC:光属性DMG",
	165 => "PROC:HP吸収",
	166 => "PROC:SP吸収",
	170 => "PROC:毒",
	171 => "PROC:麻痺",
	172 => "PROC:失神",
	173 => "PROC:スロウ",
	174 => "PROC:防御DOWN",
	175 => "PROC:沈黙",
	180 => "HHP",
	181 => "HSP",
	182 => "RHP",
	183 => "RSP",
	190 => "クリティカル",
	191 => "カウンター",
	200 => "火命",
	201 => "水命",
	202 => "土命",
	203 => "風命",
	204 => "光命",
	205 => "闇命",
	210 => "火攻",
	211 => "水攻",
	212 => "土攻",
	213 => "風攻",
	214 => "光攻",
	215 => "闇攻",
	220 => "火抵",
	221 => "水抵",
	222 => "土抵",
	223 => "風抵",
	224 => "光抵",
	225 => "闇抵",
	300 => "ドレイク攻",
	303 => "狼防",
	304 => "ヘビ攻",
	305 => "ヘビ防",
	306 => "花攻",
	309 => "ロック防",
	311 => "タウルス防",
	312 => "アシュラ攻",
	314 => "ウサギ攻",
	316 => "悪魔攻",
	317 => "悪魔防",
	400 => "全攻",
	401 => "全命",
	402 => "全防",
	410 => "暗躍",
	411 => "消費SP減少",
	420 => "毒抵",
	421 => "暗闇抵",
	422 => "麻痺抵",
	423 => "沈黙抵",
	424 => "失神抵",
	425 => "睡眠抵",
	426 => "窒息抵",
	427 => "鈍足抵",
	428 => "禁足抵",
	429 => "スロウ抵",
	430 => "恐怖抵",
	500 => "攻撃ブースト",
	501 => "魔法ブースト",
	502 => "スキルブースト",
	510 => "CSP",
	511 => "チャージ",
	512 => "気合",
	513 => "メタルガード",
	514 => "金属値UP",
	515 => "矢強化",
	516 => "EXPUP"
);
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1><?=$title?></h1>
<hr class="normal">
<form action="./eqresult.php" method="GET" enctype="multipart/form-data">
<input type="checkbox" name="category" value="20">短剣
<input type="checkbox" name="category" value="21">長剣
<input type="checkbox" name="category" value="22">斧
<input type="checkbox" name="category" value="23">槌
<input type="checkbox" name="category" value="24">杖
<input type="checkbox" name="category" value="25">弓
<input type="checkbox" name="category" value="30">矢
<input type="checkbox" name="category" value="31">盾
<input type="checkbox" name="category" value="32">書物<br />
<input type="checkbox" name="category" value="40">頭
<input type="checkbox" name="category" value="41">胴
<input type="checkbox" name="category" value="42">脚
<input type="checkbox" name="category" value="43">足
<input type="checkbox" name="category" value="44">首
<input type="checkbox" name="category" value="45">腰
<input type="checkbox" name="category" value="46">背
<input type="checkbox" name="category" value="48">装飾品
<input type="checkbox" name="category" value="49">魂<br />
Lv
<?php
for($i = 0; $i < 2; $i++) {
	$lv_name = ($i == 0) ? "max_lv" : "min_lv";
?>
<select name="<?=$lv_name?>">
<?php
	for($j = 1; $j <= $MAX_LV; $j++) {
		$selected = (($i == 0 && $j == 1) || ($i == 1 && $j == $MAX_LV)) ? " selected" : "";
?>
<option value="<?=$j?>"<?=$selected?>><?=$j?></option>
<?php
	}
?>
</select>
<?php
	if($i == 0) {
?>
～
<?php
	}
}
?>
<br />
<?php
for($i = 0; $i < 3; $i++) {
	switch($i) {
		case 0:
			$select_label = "Lv依存";
			$select_name = "bol";
			break;
		case 1:
			$select_label = "RARE属性";
			$select_name = "rare";
			break;
		case 2:
			$select_label = "NOTRADE属性";
			$select_name = "notrade";
			break;
		default:
			$select_label = "";
			$select_name = "";
			break;
	}
?>
<?=$select_label?><select name="<?=$select_name?>">
<option value="0">指定なし</option>
<option value="1">除外</option>
<option value="2">のみ</option>
</select>
<?php
}
?>
金属値
<select name="metal">
<option value="0">指定なし</option>
<option value="1">1以上</option>
<option value="2">0以下</option>
<option value="3">-1以下</option>
</select><br />
<?php
for($i = 1; $i <= 2; $i++) {
?>
ステータス1
<select name="status<?=$i?>">
<?php
	foreach($status as $st_id => $st_label) {
?>
<option value="<?=$st_id?>"><?=$st_label?></option>
<?php
	}
?>
</select>
<select name="sort<?=$i?>">
<option value="0">昇順</option>
<option value="1">降順</option>
</select><br />
<?php
}
?>
合計値でソート
<select name="sum_sort">
<option value="0">しない</option>
<option value="1">昇順</option>
<option value="2">降順</option>
</select>
<input type="hidden" name="page" value="0">
<input type="submit" value="検索">
</form>
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
