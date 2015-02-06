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
?>
