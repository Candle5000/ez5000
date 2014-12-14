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
function item_group($id) {
	$xml = "/var/www/functions/xml/item_group.xml";
	$categories = simplexml_load_file($xml);
	foreach($categories->category as $category) {
		foreach($category->group as $group) {
			if($group["id"] == $id) {
				return($group["name"]);
			}
		}
	}
	return(-1);
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

?>
