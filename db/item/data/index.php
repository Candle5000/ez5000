<?php
//=====================================
// アイテムデータ 個別データ閲覧用
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/item.php");

if($id = isset($_GET['id'])) {
	$id = $_GET['id'];
}

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

$i_name = "";
if($data->is_added("items", $id)) {
	$data->select_id("items", $id);
	$item = $data->fetch();
	$i_name = $item["name"];
	$category = item_category_id($id);
	$categoryName = item_category($category);
	$group = item_group_id($id);
	$groupName = item_group($group);
	$i_text = nl2br(str_replace("  ", "　", $item["text"]));
	$i_rare = item_attribute($item["rare"]);
	$i_notrade = item_attribute($item["notrade"]);
	$i_price = item_price($item["price"]);
	$i_stack = $item["stack"];
	$i_note = nl2br($item["note"]);

	//D/D計算
	if($flag = preg_match("/DMG([0-9]+(～[0-9]+)?).*?DLY([0-9]+)/ms", $i_text, $val)) {
		if(preg_match("/^([0-9]+)～([0-9]+)/", $val[1], $dmg)) {
			$min = sprintf("%0.3f", round(($dmg[1] / $val[3]), 3));
			$max = sprintf("%0.3f", round(($dmg[2] / $val[3]), 3));
			$dpd = $min." ～ ".$max;
		} else {
			$dpd = sprintf("%0.3f", round(($val[1] / $val[3]), 3));
		}
	}

	/* 使用 */

	//スキル習得
	$data->select_column_a("id,name", "skill", "learning LIKE '%##use##i".$id."魔法書##%'");
	if($data->rows()) {
		while($learn = $data->fetch()) {
			$l_id = $learn["id"];
			$l_name = $learn["name"];
			$i_use[] = "<a href=\"/db/skill/data/?id=$l_id\">$l_name</a>の習得";
		}
	}

	//モンスター出現
	$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "repop LIKE '%##use##i$id##%' AND monster.event=0 AND zone.id=zone");
	if($data->rows()) {
		while($repop = $data->fetch()) {
			$r_id = $repop["zone"] * 10000 + $repop["id"];
			$r_name = $repop["nm"] ? "<span class=\"nm\">".$repop["name"]."</span>" : $repop["name"];
			$i_use[] = "<a href=\"/db/monster/data/?id=$r_id\">$r_name@{$repop["nameS"]}</a>の出現";
		}
	}

	//宝箱
	$data->select_column_a("id,name", "quest", "note LIKE '%##use##i$id##%' AND id BETWEEN 20000 AND 30000");
	if($data->rows()) {
		while($chest = $data->fetch()) {
			$c_id = $chest["id"];
			$c_name = $chest["name"];
			$i_use[] = "<a href=\"/db/quest/data/?id=$c_id\">$c_name</a>の解錠";
		}
	}

	//製作
	$data->select_column_a("note", "quest", "note LIKE '%##use##i$id##%' AND id BETWEEN 30000 AND 40000");
	if($data->rows()) {
		while($product = $data->fetch()) {
			$p_link = preg_match("/##get(##i[0-9]+##)pri[0-9]+##([^g]*?|[^g]*?g[^e]*?|[^g]*?ge[^t]*?)##use##i$id##([^g]*?|[^g]*?g[^e]*?|[^g]*?ge[^t]*?).*?##end##/s", $product["note"], $match) ? $match[1] : -1;
			$i_use[] = ($p_link != -1) ? $p_link."の製作" : "";
		}
	}

	//クエスト
	$data->select_column_a("id,name", "quest", "note LIKE '%##use##i$id##%' AND id BETWEEN 50000 AND 90000");
	if($data->rows()) {
		while($quest = $data->fetch()) {
			$q_id = $chest["id"];
			$q_name = $chest["name"];
			$i_use[] = "<a href=\"/db/quest/data/?id=$q_id\">$q_name</a>";
		}
	}

	//イベント
	if(!isset($i_use)) {

		//モンスター出現
		$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "repop LIKE '%##use##i$id##%' AND monster.event=1 AND zone.id=zone");
		if($data->rows()) {
			while($repop = $data->fetch()) {
				$r_id = $repop["zone"] * 10000 + $repop["id"];
				$r_name = $repop["nm"] ? "<span class=\"nm\">".$repop["name"]."</span>" : $repop["name"];
				$i_use[] = "<a href=\"/db/monster/data/?id=$r_id\">$r_name@{$repop["nameS"]}</a>の出現";
			}
		}

		//クエスト
		$data->select_column_a("id,name", "quest", "note LIKE '%##use##i$id##%' AND id > 90000");
		if($data->rows()) {
			while($quest = $data->fetch()) {
				$q_id = $chest["id"];
				$q_name = $chest["name"];
				$i_use[] = "<a href=\"/db/quest/data/?id=$q_id\">$q_name</a>";
			}
		}
	}

	$i_use = isset($i_use) ? $data->data_link(implode("<br />\n", $i_use)) : "特になし";

	/* 入手 */
	$i = 0;

	//購入
	$data->select_column_a("id,name,note", "quest", "note LIKE '%##get##i$id##%' AND id BETWEEN 10000 AND 20000");
	if($data->rows()) {
		$i_get[$i]["label"] = "購入";
		while($buy = $data->fetch()) {
			$b_id = $buy["id"];
			$b_name = $buy["name"];
			$b_price = preg_match("/##get##i$id##pri([0-9]+)##/", $buy["note"], $match) ? "({$match[1]} B)" : "";
			$b_link[] = "<a href=\"/db/quest/data/?id=$b_id\">$b_name</a>$b_price";
		}
		$i_get[$i]["data"] = implode("<br />\n", $b_link);
		$i++;
	}

	//宝箱
	$data->select_column_a("id,name", "quest", "note LIKE '%##get##i$id##%' AND id BETWEEN 20000 AND 30000");
	if($data->rows()) {
		$i_get[$i]["label"] = "宝箱";
		while($chest = $data->fetch()) {
			$c_id = $chest["id"];
			$c_name = $chest["name"];
			$c_link[] = "<a href=\"/db/quest/data/?id=$c_id\">$c_name</a>";
		}
		$i_get[$i]["data"] = implode("<br />\n", $c_link);
		$i++;
	}

	//製作
	$data->select_column_a("id,name,note", "quest", "note LIKE '%##get##i$id##%' AND id BETWEEN 30000 AND 40000");
	if($data->rows()) {
		$i_get[$i]["label"] = "製作";
		while($product = $data->fetch()) {
			$p_id = $product["id"];
			$p_name = $product["name"];
			$p_price = preg_match("/##get##i$id##pri([0-9]+)##(.*?)##end##/s", $product["note"], $match) ? "({$match[1]} B)" : "";
			$p_need = isset($match[2]) ? $data->data_link($match[2]) : "";
			$p_text[] = nl2br("<a href=\"/db/quest/data/?id=$p_id\">$p_name</a>$p_price$p_need");
		}
		$i_get[$i]["data"] = implode("<hr class=\"normal\" />\n", $p_text);
		$i++;
	}

	//ドロップ
	$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "dropitem like '%##i$id##%' and monster.event=0 and zone.id=zone");
	if($data->rows()) {
		$i_get[$i]["label"] = "ﾄﾞﾛｯﾌﾟ";
		while($monster = $data->fetch()) {
			$m_id = $monster["zone"] * 10000 + $monster["id"];
			$m_name = $monster["nm"] ? "<span class=\"nm\">".$monster["name"]."</span>" : $monster["name"];
			$m_drop[] = "<a href=\"/db/monster/data/?id=$m_id\">$m_name@{$monster["nameS"]}</a>";
		}
		$i_get[$i]["data"] = implode("<br />\n", $m_drop);
		$i++;
	}

	//ソウル
	$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "soul=$id and monster.event=0 and zone.id=zone");
	if($data->rows()) {
		$i_get[$i]["label"] = "捕獲";
		while($monster = $data->fetch()) {
			$m_id = $monster["zone"] * 10000 + $monster["id"];
			$m_name = $monster["nm"] ? "<span class=\"nm\">".$monster["name"]."</span>" : $monster["name"];
			$m_soul[] = "<a href=\"/db/monster/data/?id=$m_id\">$m_name@{$monster["nameS"]}</a>";
		}
		$i_get[$i]["data"] = implode("<br />\n", $m_soul);
		$i++;
	}

	//スティール
	$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "steal=$id and monster.event=0 and zone.id=zone");
	if($data->rows()) {
		$i_get[$i]["label"] = "ｽﾃｨｰﾙ";
		while($monster = $data->fetch()) {
			$m_id = $monster["zone"] * 10000 + $monster["id"];
			$m_name = $monster["nm"] ? "<span class=\"nm\">".$monster["name"]."</span>" : $monster["name"];
			$m_steal[] = "<a href=\"/db/monster/data/?id=$m_id\">$m_name@{$monster["nameS"]}</a>";
		}
		$i_get[$i]["data"] = implode("<br />\n", $m_steal);
		$i++;
	}

	//クエスト
	$data->select_column_a("id,name", "quest", "note LIKE '%##get##i$id##%' AND id BETWEEN 50000 AND 90000");
	if($data->rows()) {
		$i_get[$i]["label"] = "ｸｴｽﾄ";
		while($quest = $data->fetch()) {
			$q_id = $quest["id"];
			$q_name = $quest["name"];
			$q_link[] = "<a href=\"/db/quest/data/?id=$q_id\">$q_name</a>";
		}
		$i_get[$i]["data"] = implode("<br />\n", $q_link);
		$i++;
	}

	if(!isset($i_get)) {
		//イベント限定入手

		//ドロップ
		$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "dropitem like '%##i$id##%' and monster.event=1 and zone.id=zone");
		if($data->rows()) {
			$i_get[$i]["label"] = "ﾄﾞﾛｯﾌﾟ";
			while($monster = $data->fetch()) {
				$m_id = $monster["zone"] * 10000 + $monster["id"];
				$m_name = $monster["nm"] ? "<span class=\"nm\">".$monster["name"]."</span>" : $monster["name"];
			$m_drop[] = "<a href=\"/db/monster/data/?id=$m_id\">$m_name@{$monster["nameS"]}</a>";
			}
			$i_get[$i]["data"] = implode("<br />\n", $m_drop);
			$i++;
		}

		//ソウル
		$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "soul=$id and monster.event=1 and zone.id=zone");
		if($data->rows()) {
			$i_get[$i]["label"] = "捕獲";
			while($monster = $data->fetch()) {
				$m_id = $monster["zone"] * 10000 + $monster["id"];
				$m_name = $monster["nm"] ? "<span class=\"nm\">".$monster["name"]."</span>" : $monster["name"];
				$m_soul[] = "<a href=\"/db/monster/data/?id=$m_id\">$m_name@{$monster["nameS"]}</a>";
			}
			$i_get[$i]["data"] = implode("<br />\n", $m_soul);
			$i++;
		}

		//スティール
		$data->select_column_a("zone,monster.id,monster.name,nm,nameS", "zone,monster", "steal=$id and monster.event=1 and zone.id=zone");
		if($data->rows()) {
			$i_get[$i]["label"] = "ｽﾃｨｰﾙ";
			while($monster = $data->fetch()) {
				$m_id = $monster["zone"] * 10000 + $monster["id"];
				$m_name = $monster["nm"] ? "<span class=\"nm\">".$monster["name"]."</span>" : $monster["name"];
				$m_steal[] = "<a href=\"/db/monster/data/?id=$m_id\">$m_name@{$monster["nameS"]}</a>";
			}
			$i_get[$i]["data"] = implode("<br />\n", $m_steal);
			$i++;
		}

		//イベント
		$data->select_column_a("id,name", "quest", "note LIKE '%##get##i$id##%' AND id > 90000");
		if($data->rows()) {
			$i_get[$i]["label"] = "ｲﾍﾞﾝﾄ";
			while($event = $data->fetch()) {
				$e_id = $event["id"];
				$e_name = $event["name"];
				$e_link[] = "<a href=\"/db/quest/data/?id=$e_id\">$e_name</a>";
			}
			$i_get[$i]["data"] = implode("<br />\n", $e_link);
			$i++;
		}
	}

	$i_updated = $item["updated"];
	$i_count = $data->access_count("items", $id, $item["count"]);
} else {
	toppage();
}
$title = "アイテムデータ $i_name";
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>アイテムデータ</h1>
<hr class="normal">
<div class="cnt">
<table border="1" id="item">
<tr><th colspan="2"><?=$i_name?></th></tr>
<tr><td class="cnt" width="18%">分類</td><td><?=$categoryName?>:<?=$groupName?></td></tr>
<tr><td colspan="2"><?=$i_text?></td></tr>
<?php
if($flag) {
?>
<tr><td class="cnt">D/D</td><td><?=$dpd?></td></tr>
<?php
}
?>
<tr><td colspan="2"><span class="<?=$i_rare?>">RARE</span> <span class="<?=$i_notrade?>">NOTRADE</span></td></tr>
<tr><td class="cnt">売却</td><td><?=$i_price?></td></tr>
<tr><td class="cnt">ｽﾀｯｸ</td><td><?=$i_stack?></td></tr>
<tr><td class="cnt">備考</td><td><?=$i_note?></td></tr>
<tr><td class="cnt">使用</td><td><?=$i_use?></td></tr>
<tr><td colspan="2">入手</th></tr>
<?php
if(!isset($i_get)) {
?>
<tr><td class="cnt"></td><td>不明</td></tr>
<?php
} else {
	for($i = 0; isset($i_get[$i]); $i++) {
?>
<tr><td class="cnt"><?=$i_get[$i]["label"]?></td><td><?=$i_get[$i]["data"]?></td></tr>
<?php
	}
}
?>
<tr><td class="cnt">更新</td><td><?=$i_updated?></td></tr>
</table>
</div>
<hr class="normal">
<ul id="footlink">
<li><a href="../?id=<?=$group?>"<?=mbi_ack(7)?>><?=mbi("7.")?><?=$groupName?></a></li>
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>アイテムデータ</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?=pagefoot($i_count)?>
</div>
</body>
</html>
