<?php
//=====================================
// モンスターデータ用関数
//=====================================

//----------------------------------------
// 種族
//----------------------------------------
function monster_category() {
	$xml = "/var/www/functions/xml/monster_group.xml";
	$categories = simplexml_load_file($xml);
	foreach($categories->category as $category) {
		foreach($category->group as $group) {
			if(!preg_match("/^#/", $group["name"])) {
				$ct["{$group["id"]}"] = $group["name"];
			}
		}
	}
	if(isset($ct)) {
		return($ct);
	} else {
		return(-1);
	}
}

//----------------------------------------
// 移動速度
//----------------------------------------
function monster_walkspeed() {
	$ws = array(
		-1 => '不明',
		0  => '移動しない',
		1  => '遅い',
		2  => 'やや遅い',
		3  => '普通',
		4  => 'やや速い',
		5  => '速い');
	return($ws);
}

//----------------------------------------
// 攻撃速度
//----------------------------------------
function monster_delay($dly) {
	switch($dly) {
		case 0:
			return("─");
			break;
		case -1:
			return("極速");
			break;
		case -2:
			return("速い");
			break;
		case -3:
			return("やや速い");
			break;
		case -4:
			return("普通");
			break;
		case -5:
			return("やや遅い");
			break;
		case -6:
			return("遅い");
			break;
		case -7:
			return("極遅");
			break;
		case -8:
			return("不明");
			break;
		default:
			return($dly);
	}
}

//----------------------------------------
// 索敵
//----------------------------------------
function monster_search() {
	$ws = array(
		-1 => '不明',
		0  => 'なし',
		1  => 'なし(聴覚)',
		2  => '視覚',
		3  => '聴覚',
		4  => '聴覚(やや広)',
		5  => '聴覚(広範囲)',
		6  => '視覚(見破り)',
		7  => '視覚(周囲+ハイド無効)',
		8  => '視覚(前長距離+周囲+ハイド無効)',
	);
	return($ws);
}

//----------------------------------------
// レベル
//----------------------------------------
function monster_level($max, $min) {
	if($max == -1 || $min == -1) {
		return("不明");
	} else if($max == $min) {
		return($max);
	} else if($max < $min && $max == 0) {
		return($min."以上");
	} else {
		return($m_level = $min."～".$max);
	}
}

//----------------------------------------
// ドロップ
//----------------------------------------
function monster_drop($drop) {
	$drop = preg_split("/(\n|\r\n)/", $drop);
	$i = -1;
	foreach($drop as $dropitem) {
		if(preg_match("/##(x?[0-9]#)*x?[0-9]##/", $dropitem)) {
			$head = explode("#", $dropitem);
			foreach($head as $h) {
				if(preg_match("/x[0-9]/", $h)) {
					$droplist["head"][preg_replace("/x/", "", $h)] = 2;
				} else {
					$droplist["head"][$h] = 1;
				}
			}
			$i++;
		} else if($dropitem == "##") {
			$i++;
		} else {
			$droplist["list"][$i][] = $dropitem;
		}
	}
	if(isset($droplist["list"][-1])) $droplist["list"][-1] = implode("<br />\n", $droplist["list"][-1]);
	for($i = 0; isset($droplist["list"][$i]); $i++) {
		$droplist["list"][$i] = implode("<br />\n", $droplist["list"][$i]);
	}
	return(isset($droplist) ? $droplist : -1);
}

//----------------------------------------
// スティール/ソウル
//----------------------------------------
function monster_item($data, $id) {
	if($id == -1) {
		return("不明");
	} else if($id == 0) {
		return("なし");
	} else {
		$data->select_id("items", $id);
		$item = $data->fetch();
		return($data->rows() ? "<a href=\"{$item["id"]}\">{$item["name"]}</a>" : "ERROR:未登録ID");
	}
}
?>
