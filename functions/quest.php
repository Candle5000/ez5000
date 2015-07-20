<?php
//=====================================
// クエストデータ用関数
//=====================================

//----------------------------------------
// アイテムカテゴリ名
//----------------------------------------
function quest_category($id) {
	$xml = "/var/www/functions/xml/item_group.xml";
	$categories = simplexml_load_file($xml);
	foreach($categories->category as $category) {
		if($category["id"] == $id) {
			return($category["name"]);
		}
	}
}

//----------------------------------------
// クエストカテゴリ配列
//----------------------------------------
function quest_category_array() {
	$xml = "/var/www/functions/xml/quest_group.xml";
	$categories = simplexml_load_file($xml);
	foreach($categories->category as $category) {
		foreach($category->group as $group) {
			$c["{$group["id"]}"] = $group["name"];
		}
	}
	return($c);
}

//----------------------------------------
// カテゴリ始点
//----------------------------------------
function quest_category_id($category, $id) {
	while(next($category)) {
		if($id < key($category)) {
			prev($category);
			return(key($category));
		}
	}
	end($category);
	return(key($category));
}

//----------------------------------------
// カテゴリ終点
//----------------------------------------
function quest_category_end($category, $id) {
	while(next($category)) {
		if($id < key($category)) {
			return(key($category));
		}
	}
	return(9999);
}
?>
