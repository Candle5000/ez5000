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
$PAGE_SIZE = 50;
$error = false;
$mb = (device_info() == "mb" || device_info() == "sp") ? true : false;

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

$txtall = "concat(text, ' ', note, ' ')";
$text_f = "replace($txtall, '\\n', ' ')";

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
	0 => array("name" => "指定なし", "val" => 0, "where" => ""),
	110 => array("name" => "DMG", "val" => item_sql_stmax("DMG"), "where" => "$text_f regexp ' DMG[0-9]+(～[0-9]+)?'"),
	111 => array("name" => "DELAY", "val" => item_sql_stmax("DLY"), "where" => "$text_f regexp ' DLY[0-9]+'"),
	112 => array("name" => "D/D", "val" => "round(".item_sql_stmax("DMG")." / ".item_sql_stmax("DLY").", 3)", "where" => "$text_f regexp ' DMG[0-9]+(～[0-9]+)?' AND $text_f regexp ' DLY[0-9]+'"),
	120 => array("name" => "HP", "val" => item_sql_stmax("HP"), "where" => "$text_f regexp ' HP[\\+-]?[0-9]+'"),
	121 => array("name" => "SP", "val" => item_sql_stmax("SP"), "where" => "$text_f regexp ' SP[\\+-]?[0-9]+'"),
	122 => array("name" => "STR", "val" => item_sql_stmax("STR"), "where" => "$text_f regexp ' STR([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	123 => array("name" => "VIT", "val" => item_sql_stmax("VIT"), "where" => "$text_f regexp ' VIT([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	124 => array("name" => "DEX", "val" => item_sql_stmax("DEX"), "where" => "$text_f regexp ' DEX([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	125 => array("name" => "AGI", "val" => item_sql_stmax("AGI"), "where" => "$text_f regexp ' AGI([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	126 => array("name" => "WIS", "val" => item_sql_stmax("WIS"), "where" => "$text_f regexp ' WIS([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	127 => array("name" => "WIL", "val" => item_sql_stmax("WIL"), "where" => "$text_f regexp ' WIL([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	130 => array("name" => "攻", "val" => item_sql_stmax("攻"), "where" => "$text_f regexp ' 攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	131 => array("name" => "防", "val" => item_sql_stmax("防"), "where" => "$text_f regexp ' 防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	132 => array("name" => "命中", "val" => item_sql_stmax("命中"), "where" => "$text_f regexp ' 命中([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	133 => array("name" => "回避", "val" => item_sql_stmax("回避"), "where" => "$text_f regexp ' 回避([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	134 => array("name" => "魔攻", "val" => item_sql_stmax("魔攻"), "where" => "$text_f regexp ' 魔攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	135 => array("name" => "魔防", "val" => item_sql_stmax("魔防"), "where" => "$text_f regexp ' 魔防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	136 => array("name" => "魔命", "val" => item_sql_stmax("魔命"), "where" => "$text_f regexp ' 魔命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	137 => array("name" => "魔抵", "val" => item_sql_stmax("魔抵"), "where" => "$text_f regexp ' 魔抵([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	138 => array("name" => "遠攻", "val" => item_sql_stmax("遠攻"), "where" => "$text_f regexp ' 遠攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	139 => array("name" => "遠命", "val" => item_sql_stmax("遠命"), "where" => "$text_f regexp ' 遠命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	140 => array("name" => "敵意", "val" => item_sql_stmax("敵意"), "where" => "$text_f regexp ' 敵意([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	141 => array("name" => "詠唱速度", "val" => item_sql_stmax("詠唱速度"), "where" => "$text_f regexp ' 詠唱速度([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	142 => array("name" => "攻撃速度", "val" => item_sql_stmax("攻撃速度"), "where" => "$text_f regexp ' 攻撃速度([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	143 => array("name" => "詠唱中断率", "val" => item_sql_stmax("詠唱中断率"), "where" => "$text_f regexp ' 詠唱中断率([\\+-]?[0-9]+[%]?)'"),
	144 => array("name" => "攻撃中断率", "val" => item_sql_stmax("攻撃中断率"), "where" => "$text_f regexp ' 攻撃中断率([\\+-]?[0-9]+[%]?)'"),
	146 => array("name" => "詠唱妨害", "val" => item_sql_stmax("詠唱妨害"), "where" => "$text_f regexp ' 詠唱妨害([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	147 => array("name" => "攻撃妨害", "val" => item_sql_stmax("攻撃妨害"), "where" => "$text_f regexp ' 攻撃妨害([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	148 => array("name" => "妨害", "val" => item_sql_stmax("妨害"), "where" => "$text_f regexp ' 妨害([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	150 => array("name" => "貫通", "val" => item_sql_stmax("貫通"), "where" => "$text_f regexp ' 貫通([\\+-]?[0-9]+[%]?)'"),
	151 => array("name" => "ヒール回復量", "val" => item_sql_stmax("ヒール回復量"), "where" => "$text_f regexp ' ヒール回復量([\\+-]?[0-9]+[%]?)'"),
	152 => array("name" => "盾防御発動率UP", "val" => 1, "where" => "$text_f regexp ' 盾防御発動率(UP|アップ)'"),
	153 => array("name" => "PROC発動率UP", "val" => 1, "where" => "$text_f regexp ' PROC発動率(UP|アップ)'"),
	160 => array("name" => "PROC:火属性DMG", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)火属性(DMG|ダメージ)'"),
	161 => array("name" => "PROC:水属性DMG", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)水属性(DMG|ダメージ)'"),
	162 => array("name" => "PROC:土属性DMG", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)土属性(DMG|ダメージ)'"),
	163 => array("name" => "PROC:風属性DMG", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)風属性(DMG|ダメージ)'"),
	164 => array("name" => "PROC:光属性DMG", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)光属性(DMG|ダメージ)'"),
	165 => array("name" => "PROC:闇属性DMG", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)闇属性(DMG|ダメージ)'"),
	167 => array("name" => "PROC:HP吸収", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)HP吸収'"),
	168 => array("name" => "PROC:SP吸収", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)SP吸収'"),
	170 => array("name" => "PROC:毒", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)毒'"),
	171 => array("name" => "PROC:麻痺", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)麻痺'"),
	172 => array("name" => "PROC:失神", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)失神'"),
	173 => array("name" => "PROC:スロウ", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)スロウ'"),
	174 => array("name" => "PROC:防御力DOWN", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)防御力(DOWN|ダウン)'"),
	175 => array("name" => "PROC:沈黙", "val" => 1, "where" => "$text_f regexp ' PROC(:|：)沈黙'"),
	180 => array("name" => "HHP", "val" => item_sql_stmax("HHP"), "where" => "$text_f regexp ' HHP([\\+-]?[0-9]+)'"),
	181 => array("name" => "HSP", "val" => item_sql_stmax("HSP"), "where" => "$text_f regexp ' HSP([\\+-]?[0-9]+)'"),
	182 => array("name" => "RHP", "val" => item_sql_stmax("RHP"), "where" => "$text_f regexp ' RHP([\\+-]?[0-9]+)'"),
	183 => array("name" => "RSP", "val" => item_sql_stmax("RSP"), "where" => "$text_f regexp ' RSP([\\+-]?[0-9]+)'"),
	190 => array("name" => "Crit", "val" => item_sql_stmax("Crit"), "where" => "$text_f regexp ' Crit([\\+-]?[0-9]+[%]?)'"),
	191 => array("name" => "カウンター", "val" => item_sql_stmax("カウンター"), "where" => "$text_f regexp ' カウンター([\\+-]?[0-9]+[%]?)'"),
	192 => array("name" => "カウンター妨害", "val" => item_sql_stmax("カウンター妨害"), "where" => "$text_f regexp ' カウンター妨害([\\+-]?[0-9]+[%]?)'"),
	194 => array("name" => "Crit水", "val" => item_sql_stmax("Crit水"), "where" => "$text_f regexp ' Crit水([\\+-]?[0-9]+[%]?)'"),
	200 => array("name" => "火命", "val" => item_sql_stmax("火命"), "where" => "$text_f regexp ' 火命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	201 => array("name" => "水命", "val" => item_sql_stmax("水命"), "where" => "$text_f regexp ' 水命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	202 => array("name" => "土命", "val" => item_sql_stmax("土命"), "where" => "$text_f regexp ' 土命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	203 => array("name" => "風命", "val" => item_sql_stmax("風命"), "where" => "$text_f regexp ' 風命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	204 => array("name" => "光命", "val" => item_sql_stmax("光命"), "where" => "$text_f regexp ' 光命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	205 => array("name" => "闇命", "val" => item_sql_stmax("闇命"), "where" => "$text_f regexp ' 闇命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	210 => array("name" => "火攻", "val" => item_sql_stmax("火攻"), "where" => "$text_f regexp ' 火攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	211 => array("name" => "水攻", "val" => item_sql_stmax("水攻"), "where" => "$text_f regexp ' 水攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	212 => array("name" => "土攻", "val" => item_sql_stmax("土攻"), "where" => "$text_f regexp ' 土攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	213 => array("name" => "風攻", "val" => item_sql_stmax("風攻"), "where" => "$text_f regexp ' 風攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	214 => array("name" => "光攻", "val" => item_sql_stmax("光攻"), "where" => "$text_f regexp ' 光攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	215 => array("name" => "闇攻", "val" => item_sql_stmax("闇攻"), "where" => "$text_f regexp ' 闇攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	220 => array("name" => "火抵", "val" => item_sql_stmax("火抵"), "where" => "$text_f regexp ' 火抵([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	221 => array("name" => "水抵", "val" => item_sql_stmax("水抵"), "where" => "$text_f regexp ' 水抵([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	222 => array("name" => "土抵", "val" => item_sql_stmax("土抵"), "where" => "$text_f regexp ' 土抵([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	223 => array("name" => "風抵", "val" => item_sql_stmax("風抵"), "where" => "$text_f regexp ' 風抵([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	224 => array("name" => "光抵", "val" => item_sql_stmax("光抵"), "where" => "$text_f regexp ' 光抵([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	225 => array("name" => "闇抵", "val" => item_sql_stmax("闇抵"), "where" => "$text_f regexp ' 闇抵([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	300 => array("name" => "ドレイク攻", "val" => item_sql_stmax("ドレイク攻"), "where" => "$text_f regexp ' ドレイク攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	303 => array("name" => "狼防", "val" => item_sql_stmax("狼防"), "where" => "$text_f regexp ' 狼防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	304 => array("name" => "ヘビ攻", "val" => item_sql_stmax("ヘビ攻"), "where" => "$text_f regexp ' ヘビ攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	305 => array("name" => "ヘビ防", "val" => item_sql_stmax("ヘビ防"), "where" => "$text_f regexp ' ヘビ防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	306 => array("name" => "花攻", "val" => item_sql_stmax("花攻"), "where" => "$text_f regexp ' 花攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	309 => array("name" => "ロック防", "val" => item_sql_stmax("ロック防"), "where" => "$text_f regexp ' ロック防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	311 => array("name" => "タウルス防", "val" => item_sql_stmax("タウルス防"), "where" => "$text_f regexp ' タウルス防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	312 => array("name" => "アシュラ攻", "val" => item_sql_stmax("アシュラ攻"), "where" => "$text_f regexp ' アシュラ攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	314 => array("name" => "ウサギ攻", "val" => item_sql_stmax("ウサギ攻"), "where" => "$text_f regexp ' ウサギ攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	316 => array("name" => "悪魔攻", "val" => item_sql_stmax("悪魔攻"), "where" => "$text_f regexp ' 悪魔攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	317 => array("name" => "悪魔防", "val" => item_sql_stmax("悪魔防"), "where" => "$text_f regexp ' 悪魔防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	500 => array("name" => "全攻", "val" => item_sql_stmax("全攻"), "where" => "$text_f regexp ' 全攻([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	501 => array("name" => "全命", "val" => item_sql_stmax("全命"), "where" => "$text_f regexp ' 全命([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	502 => array("name" => "全防", "val" => item_sql_stmax("全防"), "where" => "$text_f regexp ' 全防([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	510 => array("name" => "暗躍", "val" => item_sql_stmax("暗躍"), "where" => "$text_f regexp ' 暗躍([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	511 => array("name" => "結界無効", "val" => item_sql_stmax("結界無効"), "where" => "$text_f regexp ' 結界無効([\\+-]?[0-9]+[%]?)'"),
	512 => array("name" => "消費SP減少", "val" => 1, "where" => "$text_f regexp ' 消費SP減少'"),
	520 => array("name" => "毒抵", "val" => item_sql_stmax("毒抵"), "where" => "$text_f regexp ' 毒抵([\\+-]?[0-9]+[%]?)'"),
	521 => array("name" => "暗闇抵", "val" => item_sql_stmax("暗闇抵"), "where" => "$text_f regexp ' 暗闇抵([\\+-]?[0-9]+[%]?)'"),
	522 => array("name" => "麻痺抵", "val" => item_sql_stmax("麻痺抵"), "where" => "$text_f regexp ' 麻痺抵([\\+-]?[0-9]+[%]?)'"),
	523 => array("name" => "沈黙抵", "val" => item_sql_stmax("沈黙抵"), "where" => "$text_f regexp ' 沈黙抵([\\+-]?[0-9]+[%]?)'"),
	524 => array("name" => "失神抵", "val" => item_sql_stmax("失神抵"), "where" => "$text_f regexp ' 失神抵([\\+-]?[0-9]+[%]?)'"),
	525 => array("name" => "睡眠抵", "val" => item_sql_stmax("睡眠抵"), "where" => "$text_f regexp ' 睡眠抵([\\+-]?[0-9]+[%]?)'"),
	526 => array("name" => "窒息抵", "val" => item_sql_stmax("窒息抵"), "where" => "$text_f regexp ' 窒息抵([\\+-]?[0-9]+[%]?)'"),
	527 => array("name" => "鈍足抵", "val" => item_sql_stmax("鈍足抵"), "where" => "$text_f regexp ' 鈍足抵([\\+-]?[0-9]+[%]?)'"),
	528 => array("name" => "禁足抵", "val" => item_sql_stmax("禁足抵"), "where" => "$text_f regexp ' 禁足抵([\\+-]?[0-9]+[%]?)'"),
	529 => array("name" => "スロウ抵", "val" => item_sql_stmax("スロウ抵"), "where" => "$text_f regexp ' スロウ抵([\\+-]?[0-9]+[%]?)'"),
	530 => array("name" => "恐怖抵", "val" => item_sql_stmax("恐怖抵"), "where" => "$text_f regexp ' 恐怖抵([\\+-]?[0-9]+[%]?)'"),
	600 => array("name" => "攻撃ブースト", "val" => 1, "where" => "$text_f regexp ' 攻撃ブースト'"),
	601 => array("name" => "魔法ブースト", "val" => 1, "where" => "$text_f regexp ' 魔法ブースト'"),
	602 => array("name" => "スキルブースト", "val" => 1, "where" => "$text_f regexp ' スキルブースト'"),
	610 => array("name" => "CSP", "val" => item_sql_stmax("CSP"), "where" => "$text_f regexp ' CSP([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	611 => array("name" => "チャージ", "val" => item_sql_stmax("チャージ"), "where" => "$text_f regexp ' チャージ([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	612 => array("name" => "気合", "val" => item_sql_stmax("気合"), "where" => "$text_f regexp ' 気合([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	613 => array("name" => "メタルガード", "val" => item_sql_stmax("メタルガード"), "where" => "$text_f regexp ' メタルガード([\\+-]?[0-9]+|[0-9]+～[0-9]+)'"),
	614 => array("name" => "金属値UP", "val" => 1, "where" => "$text_f regexp ' 金属値UP'"),
	615 => array("name" => "矢強化", "val" => 1, "where" => "$text_f regexp ' 矢強化'"),
	616 => array("name" => "EXPUP", "val" => item_sql_stmax("EXPUP"), "where" => "$text_f regexp ' EXPUP[0-9]+'")
);

// 装備種別フォーム入力取得
if(!$error) {
	if(isset($_GET["category"]) && count($_GET["category"]) <= count($eqType)) {
		foreach($_GET["category"] as $categoryID) {
			if(!isset($eqType[$categoryID])) {
				$error = true;
				break;
			}
		}
		if(!$error) $eq_list = $_GET["category"];
	} else {
		$eq_list = array();
	}
}

// 装備レベルフォーム入力取得
if(!$error) {
	// MAX Lv
	if(isset($_GET["max_lv"]) && is_numeric($_GET["max_lv"]) && $_GET["max_lv"] <= $MAX_LV && $_GET["max_lv"] >= 1) {
		$lv_max = $_GET["max_lv"];
	} else {
		$error = true;
	}

	// MIN Lv
	if(isset($_GET["min_lv"]) && is_numeric($_GET["min_lv"]) && $_GET["min_lv"] <= $MAX_LV && $_GET["min_lv"] >= 1) {
		$lv_min = $_GET["min_lv"];
	} else {
		$error = true;
	}
}

// 属性フォーム入力取得
if(!$error) {
	// Based on Lv
	if(isset($_GET["bol"]) && $_GET["bol"] == 0 || $_GET["bol"] == 1 || $_GET["bol"] == 2) {
		$bol = $_GET["bol"];
	} else {
		$error = true;
	}

	// RARE
	if(isset($_GET["rare"]) && $_GET["rare"] == 0 || $_GET["rare"] == 1 || $_GET["rare"] == 2) {
		$rare = $_GET["rare"];
	} else {
		$error = true;
	}

	// NOTRADE
	if(isset($_GET["notrade"]) && $_GET["notrade"] == 0 || $_GET["notrade"] == 1 || $_GET["notrade"] == 2) {
		$notrade = $_GET["notrade"];
	} else {
		$error = true;
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
		$error = true;
	}
}

// ステータスフォーム入力取得
if(!$error) {
	// ステータス1
	if(isset($_GET["status1"])) {
		if(isset($status[$_GET["status1"]])) {
			$st1 = $_GET["status1"];
		} else {
			$error = true;
		}
	} else {
		$error = true;
	}

	// ソート設定1
	if(isset($_GET["sort1"])) {
		if($_GET["sort1"] == 0 || $_GET["sort1"] == 1) {
			$sort1 = $_GET["sort1"];
		} else {
			$error = true;
		}
	} else {
		$error = true;
	}

	// ステータス2
	if(isset($_GET["status2"])) {
		if(isset($status[$_GET["status2"]])) {
			$st2 = $_GET["status2"];
		} else {
			$error = true;
		}
	} else {
		$error = true;
	}

	// ソート設定2
	if(isset($_GET["sort2"])) {
		if($_GET["sort2"] == 0 || $_GET["sort2"] == 1) {
			$sort2 = $_GET["sort2"];
		} else {
			$error = true;
		}
	} else {
		$error = true;
	}
}

// ステータス1が指定なしの場合
if(!$error && $st1 == 0) {
	$st1 = $st2;
	$st2 = 0;
	$sort1 = $sort2;
	$sort2 = 0;
}

// Max Lv < Min Lv の場合
if(!$error && $lv_max < $lv_min) {
	$buf = $lv_max;
	$lv_max = $lv_min;
	$lv_min = $buf;
}

// 合計値ソートフォーム入力取得
if(!$error) {
	if(isset($_GET["sum_sort"])) {
		if($_GET["sum_sort"] == 0 || $_GET["sum_sort"] == 1 || $_GET["sum_sort"] == 2) {
			$sum_sort = ($st1 == 0 || $st2 == 0) ? 0 : $_GET["sum_sort"];
		} else {
			$error = true;
		}
	} else {
		$error = true;
	}
}

// ページ入力取得
if(!$error) {
	if(isset($_GET["page"])) {
		if(is_numeric($_GET["page"]) && $_GET["page"] >= 0) {
			$page = $_GET["page"];
		} else {
			$error = true;
		}
	} else {
		$page = 0;
	}
}

// SQL文生成
if(!$error) {
	$lv_loc = "locate('Lv', $txtall)";
	$lv_end = "locate(' ', $text_f, $lv_loc)";
	$lv_txt = "substring($txtall, $lv_loc, $lv_end - $lv_loc)";
	$lv_val = "substring($txtall, $lv_loc + 2, $lv_end - $lv_loc - 2)";
	$lv_num = "case when $lv_txt like '%→%' then substring($lv_val, 1, locate('→', $lv_val) - 1) else replace($lv_val, '～', '') end";

	// column
	$sql_column = "id,name";
	if($st1 != 0) $sql_column .= ",{$status["$st1"]["val"]} + 0 as st1";
	if($st2 != 0) $sql_column .= ",{$status["$st2"]["val"]} + 0 as st2";
	if($sum_sort != 0) $sql_column .= ",{$status["$st1"]["val"]} + {$status["$st2"]["val"]} as sum";

	// where
	$sql_where = "hidden = 0";
	foreach($eq_list as $eq) {
		$sql_eqtype[] = "id BETWEEN ".($eq * 1000 + 1)." AND ".($eq * 1000 + 1000);
	}
	if(isset($sql_eqtype)) {
		$sql_where .= " AND (".implode(" OR ", $sql_eqtype).")";
	} else {
		$sql_where .= " AND (id BETWEEN 20001 AND 50000)";
	}
	$sql_where .= " AND ($lv_num) >= $lv_min AND ($lv_num) <= $lv_max";
	if($bol == 1) {
		$sql_where .= " AND $text_f NOT regexp 'Lv[0-9]+→[0-9]+' AND $text_f NOT like '%～%～%'";
	} else if($bol == 2) {
		$sql_where .= " AND ($text_f regexp 'Lv[0-9]+→[0-9]+' OR $text_f like '%～%～%')";
	}
	if($rare == 1) {
		$sql_where .= " AND rare = 0";
	} else if($rare == 2) {
		$sql_where .= " AND rare = 1";
	}
	if($notrade == 1) {
		$sql_where .= " AND notrade = 0";
	} else if($notrade == 2) {
		$sql_where .= " AND notrade = 1";
	}
	if($metal == 1) {
		$sql_where .= " AND $text_f like '%[%]%'";
	} else if($metal == 2) {
		$sql_where .= " AND $text_f like '%<%>%'";
	}
	if($st1 != 0) $sql_where .= " AND ".$status["$st1"]["where"];
	if($st2 != 0) $sql_where .= " AND ".$status["$st2"]["where"];

	// order
	$sql_order = "";
	if($sum_sort != 0) {
		$sql_order .= ($sum_sort == 1) ? "sum desc, " : "sum asc, ";
		$sql_order .= ($sort1 == 0) ? "st1 desc, " : "st1 asc, ";
		$sql_order .= ($sort2 == 0) ? "st2 desc, " : "st2 asc, ";
	} else {
		if($st2 != 0) {
			$sql_order .= ($sort1 == 0) ? "st1 desc, " : "st1 asc, ";
			$sql_order .= ($sort2 == 0) ? "st2 desc, " : "st2 asc, ";
		} else if ($st1 != 0) {
			$sql_order .= ($sort1 == 0) ? "st1 desc, " : "st1 asc, ";
		}
	}
	$sql_order .= "id asc";

	// limit
	$sql_limit = ($page * $PAGE_SIZE).",$PAGE_SIZE";

	$sql = "SELECT id FROM items WHERE $sql_where";
	$data->query($sql);
	$rows = $data->rows();
	$sql = "SELECT $sql_column FROM items WHERE $sql_where ORDER BY $sql_order LIMIT $sql_limit";
	$data->query($sql);
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
$i = 0;
foreach($eqType as $t_id => $type) {
	$br = ($t_id == 32 || ($mb && ($t_id == 22 || $t_id == 25 || $t_id == 43 || $t_id == 46))) ? "<br />" : "";
	if(isset($eq_list[$i])) {
		$checked = (!$error && $t_id == $eq_list[$i]) ? " checked" : "";
		if(!$error && $t_id == $eq_list[$i] && isset($eq_list[$i + 1])) $i++;
	} else {
		$checked = "";
	}
?>
<input type="checkbox" name="category[]" value="<?=$t_id?>"<?=$checked?>><?=$type?><?=$br?>
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
		if(!$error) {
			$selected = (($i == 0 && $j == $lv_min) || ($i == 1 && $j == $lv_max)) ? " selected" : "";
		} else {
			$selected = (($i == 0 && $j == 1) || ($i == 1 && $j == $MAX_LV)) ? " selected" : "";
		}
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
$option_label = array("指定なし", "除外", "のみ");
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
<?php
	foreach($option_label as $val => $label) {
		$selected = (!$error && $val == $_GET["$select_name"]) ? " selected" : "";
?>
<option value="<?=$val?>"<?=$selected?>><?=$label?></option>
<?php
	}
?>
</select>
<?php
	if($mb) {
?>
<br />
<?php
	}
}
$option_label = array("指定なし", "非金属のみ", "金属製のみ");
?>
金属値<select name="metal">
<?php
foreach($option_label as $val => $label) {
	$selected = (!$error && $val == $_GET["metal"]) ? " selected" : "";
?>
<option value="<?=$val?>"<?=$selected?>><?=$label?></option>
<?php
}
?>
</select><br />
<?php
$option_label = array("降順", "昇順");
for($i = 1; $i <= 2; $i++) {
?>
ステータス<?=$i?>
<?php
	if($mb) {
?>
<br />
<?php
	}
?>
<select name="status<?=$i?>">
<?php
	foreach($status as $st_id => $st_data) {
		$selected = (!$error && (($i == 1 && $st1 == $st_id) || ($i == 2 && $st2 == $st_id))) ? " selected" : "";
?>
<option value="<?=$st_id?>"<?=$selected?>><?=$st_data["name"]?></option>
<?php
	}
?>
</select>
<select name="sort<?=$i?>">
<?php
foreach($option_label as $val => $label) {
	$selected = (!$error && (($i == 1 && $val == $sort1) || ($i == 2 && $val == $sort2))) ? " selected" : "";
?>
<option value="<?=$val?>"<?=$selected?>><?=$label?></option>
<?php
}
?>
</select><br />
<?php
}
$option_label = array("しない", "降順", "昇順");
?>
合計値でソート
<select name="sum_sort">
<?php
foreach($option_label as $val => $label) {
	$selected = (!$error && $val == $sum_sort) ? " selected" : "";
?>
<option value="<?=$val?>"<?=$selected?>><?=$label?></option>
<?php
}
?>
</select>
<?php
if($mb) {
?>
<br />
<?php
}
?>
<input type="hidden" name="page" value="0">
<input type="submit" value="検索">
</form>
<?php
if(!$error) {
	foreach($eq_list as $eq) {
		$get[] = urlencode("category[]")."=".$eq;
	}
	$get[] = "min_lv=$lv_min";
	$get[] = "max_lv=$lv_max";
	$get[] = "bol=$bol";
	$get[] = "rare=$rare";
	$get[] = "notrade=$notrade";
	$get[] = "metal=$metal";
	$get[] = "status1=$st1";
	$get[] = "sort1=$sort1";
	$get[] = "status2=$st2";
	$get[] = "sort2=$sort2";
	$get[] = "sum_sort=$sum_sort";
	$get = implode("&", $get);
	if(($page > 0) && ($rows > 0)) {
		$pagelink = "<a href=\"./eqsearch.php?".$get."&page=".($page - 1)."\"".mbi_ack("*").">".mbi("*.")."前のページ</a> | ";
	} else {
		$pagelink = mbi("*.")."前のページ | ";
	}
	if((($page + 1) * $PAGE_SIZE) < $rows) {
		$pagelink .= "<a href=\"./eqsearch.php?".$get."&page=".($page + 1)."\"".mbi_ack("#").">".mbi("#.")."次のページ</a>";
	} else {
		$pagelink .= mbi("#.")."次のページ";
	}
?>
<hr class="normal">
<div>検索結果 : <?=$rows?> 件中 <?=(($page * $PAGE_SIZE) + 1)?> - <?=(($page + 1) * $PAGE_SIZE)?> 件</div>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<hr class="normal">
<ul id="linklist">
<?php
	if($rows > 0) {
		while($row = $data->fetch()) {
			$id = $row["id"];
			$name = $row["name"];
			$info = "";
			if($st1 != 0) {
				$info .= "<br />└{$status["$st1"]["name"]}:{$row["st1"]}";
				if($st2 != 0) {
					$info .= " {$status["$st2"]["name"]}:{$row["st2"]}";
					if($sum_sort != 0) {
						$info .= " 合計:{$row["sum"]}";
					}
				}
			}
?>
<li><a href="/db/item/data/?id=<?=$id?>"><?=$name?></a><?=$info?></li>
<?php
		}
	}
?>
</ul>
<?php
}
?>
<?php
if(!$error) {
?>
<hr class="normal">
<div class="cnt"><?=$pagelink?></div>
<?php
}
?>
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
