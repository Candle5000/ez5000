<?php
//=====================================
// スキルデータ用関数
//=====================================

//----------------------------------------
// 再使用時間
//----------------------------------------
function skill_recast($recast) {
	if($recast == -1) {
		return("不明");
	} else if($recast == 0) {
		return("─");
	} else {
		$min = floor($recast / 60);
		$sec = $recast % 60;
		$time = "";
		if($min > 0) {
			$time .= $min."分 ";
		}
		if($sec > 0) {
			$time .= $sec."秒";
		}
		return($time);
	}
}

//----------------------------------------
// 詠唱速度
//----------------------------------------
function skill_cast($cast) {
	switch($cast) {
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
			return($cast);
	}
}

?>

