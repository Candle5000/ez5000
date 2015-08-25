<?php
//=====================================
// アイテムデータ用関数
//=====================================

//----------------------------------------
// 売却価格
//----------------------------------------
function item_price($price) {
	switch($price) {
		case -1:
			return("不明");
			break;
		case 0:
			return("売却不可");
			break;
		default:
			return("$price B");
			break;
	}
}

//----------------------------------------
// RARE NOTRADEのON OFF
//----------------------------------------
function item_attribute($bool) {
	if($bool) {
		return("on");
	} else {
		return("off");
	}
}

//----------------------------------------
// アイテムカテゴリ名
//----------------------------------------
function item_category($id) {
	$xml = "/var/www/functions/xml/item_group.xml";
	$categories = simplexml_load_file($xml);
	foreach($categories->category as $category) {
		if($category["id"] == $id) {
			return($category["name"]);
		}
	}
}

//----------------------------------------
// アイテムグループ名
//----------------------------------------
function item_group() {
	$xml = "/var/www/functions/xml/item_group.xml";
	$categories = simplexml_load_file($xml);
	foreach($categories->category as $category) {
		foreach($category->group as $group) {
			if(func_num_args() == 1 && $group["id"] == func_get_arg(0)) {
				return($group["name"]);
			} else if(func_num_args() == 0) {
				$groupList["{$group["id"]}"] = $group["name"];
			}
		}
	}
	if(isset($groupList)) {
		return($groupList);
	} else {
		return(-1);
	}
}

//----------------------------------------
// カテゴリ始点
//----------------------------------------
function item_category_id($id) {
	if($id < 50000) {
		$id -= $id % 10000;
		return($id);
	} else {
		return(50000);
	}
}

//----------------------------------------
// グループ始点
//----------------------------------------
function item_group_id($id) {
	if($id < 13000) {
		$id -= $id % 100;
		if($id == 11700) {
			return($id - 1);
		}
	} else {
		$id--;
		$id -= $id % 1000;
	}
	return($id);
}

//----------------------------------------
// グループ終点
//----------------------------------------
function item_group_end($start) {
	if(item_group($start) == -1) {
		return(-1);
	}
	if($start < 13000) {
		return($start + 99);
	} else {
		return($start + 1000);
	}
}

//----------------------------------------
// SQL用ステータス数値(全体)
//----------------------------------------
function item_sql_stval($name) {
	$name_l = mb_strlen($name);
	$txtall = "concat(text, ' ', note)";
	$text_f = "replace(concat($txtall, ' '), '\\n', ' ')";
	$st_loc = "locate('$name', $txtall)";
	$st_end = "locate(' ', $text_f, $st_loc)";
	$st_txt = "substring($txtall, $st_loc, $st_end - $st_loc)";
	$st_val = "substring($txtall, $st_loc, + $name_l, $st_end - $st_loc - $name_l)";
	return($st_val);
}

//----------------------------------------
// SQL用ステータス数値(最大値のみ)
//----------------------------------------
function item_sql_stmax($name) {
	$name_l = mb_strlen($name) + 1;
	$txtall = "concat(text, ' ', note, ' ')";
	$text_f = "replace($txtall, '\\n', ' ')";
	$st_loc = "locate(' $name', $text_f)";
	$st_end = "locate(' ', $text_f, $st_loc + 1)";
	$st_txt = "substring($text_f, $st_loc, $st_end - $st_loc)";
	$st_val = "substring($text_f, $st_loc + $name_l, $st_end - $st_loc - $name_l)";
	$st_max = "case when $st_txt like '%～%' then substring($st_val, locate('～', $st_val) + 1) else replace(replace($st_val, '+', ''), '%', '') end";
	return($st_max);
}
?>
