<?php
//=====================================
// アイテムデータ 装備データ検索
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");
session_start();

$PAGE_ID = 20030;
$title = "装備アイテム検索";
$MAX_LV = "70";
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
if(isset($_SESSION["user"]) && isset($_SESSION["pass"])) {
	$data = new AdminData($_SESSION["user"], $_SESSION["pass"], "ezdata");
	if(!$data->is_admin) {
		session_destroy();
		die("データベースの接続に失敗しました");
	}
} else {
	$data = new GuestData($userName, $password, $database);
}
if(mysqli_connect_error()) {
	die("データベースの接続に失敗しました");
}

$sql = "SELECT * FROM `equip_class` ORDER BY `id`";
$data->query($sql);
while($array = $data->fetch()) {
	$eqType["{$array["id"]}"] = $array["name"];
}
if(empty($eqType)) die("データ読み込みに失敗しました\n");
$sql = "SELECT * FROM `parameter` ORDER BY `id`";
$data->query($sql);
$status[0] = "指定なし";
while($array = $data->fetch()) {
	$status["{$array["id"]}"] = $array["name"];
}
if(count($status) < 2) die("データ読み込みに失敗しました\n");

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

	// column
	$sql_column = "`items`.`id`,`name`,`lv_min`,`lv_max`";
	if($st1 != 0) $sql_column .= ",`st1`.`value_max` AS `val1`";
	if($st2 != 0) $sql_column .= ",`st2`.`value_max` AS `val2`";
	if($sum_sort != 0) $sql_column .= ",`st1`.`value_max` + `st2`.`value_max` AS `sum`";

	// from
	$sql_from = "(`items` NATURAL JOIN `equip`)";
	if($st1 != 0) $sql_from .= ",`equip_parameter` AS `st1`";
	if($st2 != 0) $sql_from .= ",`equip_parameter` AS `st2`";

	// where
	$sql_where = "`hidden`='0'";
	foreach($eq_list as $eq) {
		//$sql_eqtype[] = "`id` BETWEEN '".($eq * 1000 + 1)."' AND '".($eq * 1000 + 1000)."'";
		$sql_eqtype[] = "'$eq'";
	}
	if(isset($sql_eqtype)) {
		$sql_where .= " AND `class_id` IN (".implode(", ", $sql_eqtype).")";
	}
	$sql_where .= " AND (`lv_min` BETWEEN '$lv_min' AND '$lv_max')";
	if($bol == 1) {
		$sql_where .= " AND `lv_max`='-1'";
	} else if($bol == 2) {
		$sql_where .= " AND `lv_max`!='-1'";
	}
	if($rare == 1) {
		$sql_where .= " AND `rare`='0'";
	} else if($rare == 2) {
		$sql_where .= " AND `rare`='1'";
	}
	if($notrade == 1) {
		$sql_where .= " AND `notrade`='0'";
	} else if($notrade == 2) {
		$sql_where .= " AND `notrade`='1'";
	}
	if($metal == 1) {
		$sql_where .= " AND NOT `is_metal`";
	} else if($metal == 2) {
		$sql_where .= " AND `is_metal`";
	}
	if($st1 != 0) $sql_where .= " AND `id`=`st1`.`item_id` AND `st1`.`parameter_id`='$st1' AND NOT `st1`.`adversity`";
	if($st2 != 0) $sql_where .= " AND `id`=`st2`.`item_id` AND `st2`.`parameter_id`='$st2' AND NOT `st2`.`adversity`";

	// order
	$sql_order = "";
	if($sum_sort != 0) {
		$sql_order .= ($sum_sort == 1) ? "`sum` DESC, " : "`sum`, ";
		$sql_order .= ($sort1 == 0) ? "`val1` DESC, " : "`val1`, ";
		$sql_order .= ($sort2 == 0) ? "`val2` DESC, " : "`val2`, ";
	} else {
		if($st2 != 0) {
			$sql_order .= ($sort1 == 0) ? "`val1` DESC, " : "`val1`, ";
			$sql_order .= ($sort2 == 0) ? "`val2` DESC, " : "`val2`, ";
		} else if ($st1 != 0) {
			$sql_order .= ($sort1 == 0) ? "`val1` DESC, " : "`val1`, ";
		}
	}
	$sql_order .= "`id`";

	// limit
	$sql_limit = ($page * $PAGE_SIZE).",$PAGE_SIZE";

	$sql = "SELECT COUNT(1) AS `rows` FROM $sql_from WHERE $sql_where";
	$data->query($sql);
	$result = $data->fetch();
	$rows = $result["rows"];
	$sql = "SELECT $sql_column FROM $sql_from WHERE $sql_where ORDER BY $sql_order LIMIT $sql_limit";
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
<option value="<?=$st_id?>"<?=$selected?>><?=$st_data?></option>
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
			$id_f = isset($data->is_admin) ? sprintf("%d:", $id) : "";
			$info = "";
			if($status["$st1"] == "D/D") $row["val1"] = str_pad($row["val1"], 5, 0, STR_PAD_RIGHT);
			if($status["$st2"] == "D/D") $row["val2"] = str_pad($row["val2"], 5, 0, STR_PAD_RIGHT);
			if($st1 != 0) {
				$info .= "<br />└{$status["$st1"]}:".$row["val1"];
				if($st2 != 0) {
					$info .= " {$status["$st2"]}:".$row["val2"];
					if($sum_sort != 0) {
						$info .= " 合計:".$row["sum"];
					}
				}
			}
?>
<li><?=$id_f?><a href="/db/item/data/?id=<?=$id?>"><?=$name?></a><?=$info?></li>
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
