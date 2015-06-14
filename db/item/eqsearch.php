<?php
//=====================================
// アイテムデータ 装備データ検索
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");

$PAGE_ID = 20030;
$title = "装備アイテム検索";
$MAX_LV = "60";
$error = false;

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

$eqType = array(
	20 => "短剣",
	21 => "長剣",
	22 => "斧",
	23 => "槌",
	24 => "杖",
	25 => "弓",
	30 => "矢",
	31 => "盾",
	32 => "書物",
	40 => "頭",
	41 => "胴",
	42 => "脚",
	43 => "足",
	44 => "首",
	45 => "腰",
	46 => "背",
	48 => "装飾品",
	49 => "魂"
);
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

// 装備種別フォーム入力取得
if(!$error) {
	if(isset($_GET["category"]) && count($_GET["category"]) <= count($eqType)) {
		foreach($_GET["category"] as $categoryID) {
			if(isset($eqType[$categoryID])) {
				$which_category[] = "id BETWEEN ".($categoryID * 1000)." AND ".($categoryID * 1000 + 999);
			} else {
				$error = true;
				break;
			}
		}
		if(!$error) $sql_which[] = implode($which_category, " OR ");
	} else {
		$sql_which[] = "id BETWEEN 20000 AND 49999";
	}
}

// 装備レベルフォーム入力取得
if(!$error) {
	//MAX Lv
	if(isset($_GET["max_lv"])) {
		if(is_numeric($_GET["max_lv"]) && $_GET["max_lv"] <= $MAX_LV && $_GET["max_lv"] >= 1) {
			$lv_max = $_GET["max_lv"];
		} else {
			$error = true;
		}
	} else {
		$lv_max = $MAX_LV;
	}

	//MIN Lv
	if(isset($_GET["min_lv"])) {
		if(is_numeric($_GET["min_lv"]) && $_GET["min_lv"] <= $MAX_LV && $_GET["min_lv"] >= 1) {
			$lv_min = $_GET["min_lv"];
		} else {
			$error = true;
		}
	} else {
		$lv_min = 1;
	}
}

// 属性フォーム入力取得
if(!$error) {
	// Based on Lv
	if(isset($_GET["bol"])) {
		if($_GET["bol"] == 0 || $_GET["bol"] == 1 || $_GET["bol"] == 2) {
			$bol = $_GET["bol"];
		} else {
			$error = true;
		}
	} else {
		$bol = 0;
	}

	// RARE
	if(isset($_GET["rare"])) {
		if($_GET["rare"] == 0 || $_GET["rare"] == 1 || $_GET["rare"] == 2) {
			$rare = $_GET["rare"];
		} else {
			$error = true;
		}
	} else {
		$rare = 0;
	}

	// NOTRADE
	if(isset($_GET["notrade"])) {
		if($_GET["notrade"] == 0 || $_GET["notrade"] == 1 || $_GET["notrade"] == 2) {
			$notrade = $_GET["notrade"];
		} else {
			$error = true;
		}
	} else {
		$notrade = 0;
	}
}

// 金属値フォーム入力取得
if(!$error) {
	if(isset($_GET["metal"])) {
		if($_GET["metal"] == 0 || $_GET["metal"] == 1 || $_GET["metal"] == 2 || $_GET["metal"] == 3) {
			$metal = $_GET["metal"];
		} else {
			$error = true;
		}
	} else {
		$metal = 0;
	}
}

// ステータスフォーム入力取得
if(!$error) {
	if(isset($_GET["status1"])) {
		if(isset($status[$_GET["status1"]])) {
			$status1 = $_GET["status1"];
		} else {
			$error = true;
		}
	} else {
		$status1 = 0;
	}

	if(isset($_GET["sort1"])) {
		if($_GET["sort1"] == 0 || $_GET["sort1"] == 1) {
			$sort1 = $_GET["sort1"];
		} else {
			$error = true;
		}
	} else {
		$sort1 = 0;
	}

	if(isset($_GET["status2"])) {
		if(isset($status[$_GET["status2"]])) {
			$status2 = $_GET["status2"];
		} else {
			$error = true;
		}
	} else {
		$status2 = 0;
	}

	if(isset($_GET["sort2"])) {
		if($_GET["sort2"] == 0 || $_GET["sort2"] == 1) {
			$sort2 = $_GET["sort2"];
		} else {
			$error = true;
		}
	} else {
		$sort2 = 0;
	}
}

// 合計値ソートフォーム入力取得
if(!$error) {
	if(isset($_GET["sum_sort"])) {
		if($_GET["sum_sort"] == 0 || $_GET["sum_sort"] == 1 || $_GET["sum_sort"] == 2) {
			$sum_sort = $_GET["sum_sort"];
		} else {
			$error = true;
		}
	} else {
		$sum_sort = 0;
	}
}

// ページ入力取得
if(!$error) {
	if(isset($_GET["page"])) {
		if(is_numeric($_GET["page"]) && $_GET["page"] > 0) {
			$page = $_GET["page"];
		} else {
			$error = true;
		}
	} else {
		$page = 0;
	}
}
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1><?=$title?></h1>
<hr class="normal">
<form action="<?=$_SERVER["PHP_SELF"]?>" method="GET" enctype="multipart/form-data">
<?php
foreach($eqType as $t_id => $type) {
	$br = ($t_id == 32) ? "<br />" : "";
?>
<input type="checkbox" name="category[]" value="<?=$t_id?>" checked><?=$type?><?=$br?>
<?php
}
?>
<br />
Lv
<?php
for($i = 0; $i < 2; $i++) {
	$lv_name = ($i == 0) ? "min_lv" : "max_lv";
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
金属値<select name="metal">
<option value="0">指定なし</option>
<option value="1">1以上</option>
<option value="2">0以下</option>
<option value="3">-1以下</option>
</select><br />
<?php
for($i = 1; $i <= 2; $i++) {
?>
ステータス<?=$i?>
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
