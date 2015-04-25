<?php
//=====================================
// 管理者用 アイテムデータ 追加 更新 削除
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/class/form.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/monster.php");
require_once("/var/www/functions/xml/monster_form_upd.php");
$group_xml = "/var/www/functions/xml/monster_group.xml";
$form_login_xml = "/var/www/functions/xml/admin_login_form.xml";
$form_add_xml = "/var/www/functions/xml/monster_form_add.xml";
$PAGESIZE = 8;
$table = "monster";
$zone = "zone";

session_start();

//ログイン
if(isset($_SERVER["REQUEST_METHOD"]) == "POST") {
	if(isset($_POST["submit_login"])) {
		$_SESSION["user"] = $_POST["user"];
		$_SESSION["pass"] = $_POST["pass"];
	}
}

$form = new Form($_SERVER["PHP_SELF"], "POST", "multipart/form-data");
if(isset($_SESSION["user"]) && isset($_SESSION["pass"])) {
	$data = new AdminData($_SESSION["user"], $_SESSION["pass"], "ezdata");
	if(!$data->is_admin || mysqli_connect_error()) {
		session_destroy();
		$login_err = "<div style=\"color:#F00;\">ログイン情報が間違っています</div>";
	}
}

if(isset($_SERVER["REQUEST_METHOD"]) == "POST" && !isset($login_err)) {

	// ログアウト
	if(isset($_POST["submit_logout"])) {
		session_destroy();
		selfpage();
	}

	// 最初のページ
	if(isset($_POST["submit_group"])) $_POST["page"] = 0;

	// ゾーンの選択
	if(isset($_POST["submit_group"])) {
		$zone_id = $_POST["group"];
	} else if(isset($_POST["zone"])) {
		$zone_id = $_POST["zone"];
	} else {
		$zone_id = 1;
	}

	// ページの選択
	$page = isset($_POST["page"]) ? $_POST["page"] : 0;

	// 新規作成
	if(isset($_POST["submit_add"])) {
		$_POST["new_zone"] = $zone_id;
		$cols = array("zone","id","name","nm","category","image","walkspeed","delay","search","follow","link","maxlevel","minlevel","repop","maxpop","skill","dropitem","soul","steal","note","event");
		foreach($cols as $col) {
			$values[] = isset($_POST["new_".$col]) ? "'".mysql_real_escape_string($_POST["new_".$col])."'" : 0;
		}
		$cols = implode(",", $cols);
		$values = implode(",", $values);
		$data->insert_data($table, $cols, $values);
		$data->timestamp($table, "zone=".$_POST["new_zone"]." AND id=".$_POST["new_id"]);
	}

	// 変更
	if(isset($_POST["submit_upd"])) {
		$id = key($_POST["submit_upd"]);
		$target = "zone=".$zone_id." AND id=".$id;
		$cols = array("name","nm","category","image","walkspeed","delay","search","follow","link","maxlevel","minlevel","repop","maxpop","skill","dropitem","soul","steal","note","event");
		foreach($cols as $col) {
			$values[] = isset($_POST[$col][$id]) ? mysql_real_escape_string(preg_replace("/[\r][\n]/", "\n", $_POST[$col][$id])) : 0;
		}
		$data->update_data($table, $cols, $values, $target);
		$data->timestamp($table, $target);
	}

	// 登録件数
	if(isset($_SESSION["user"]) && isset($_SESSION["pass"])) {
		$data->select_column("id", $table, "zone", $zone_id);
		$count = $data->rows();
	}
}
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<?php
if(!isset($_SESSION["user"]) || !isset($_SESSION["pass"]) || isset($login_err)) {
//管理ログイン
if(isset($login_err)) echo $login_err;
?>
<?=$form->start()?>
<?=$form->load_xml_file($form_login_xml)?>
<?=$form->close()?>
管理者ユーザー名とパスワードを入力してください
</body>
</html>
<?php
} else {
//ログイン済
	$part_hidden = array('part' => 'input', 'type' => 'hidden', 'name' => 'zone', 'value' => $zone_id);
	$data->select_all($zone);
	$part = array('part' => 'select', 'name' => 'group', 'selected' => $zone_id);
	while($row = $data->fetch()) {
		$part["option"]["{$row["id"]}"] = str_pad($row["id"], 3, "0", STR_PAD_LEFT).":".$row["name"];
	}
?>
<h3>* * Monster List * *</h3>
<?=$form->start()?>
<?=$form->build($part_hidden)?>
<?=$form->submit("logout", "ログアウト")?>
<div>
モンスターリストに新規追加<br>
<?=$form->load_xml_file($form_add_xml)?>
</div>
<hr>
<div>
ゾーン
<?=$form->build($part)?>
<?=$form->submit("group", "表示")?>
</div>
<hr>
ページ
<?=$form->build_select_page($count, $PAGESIZE, $page)?>
<hr>
<?php
	$data->select_column_l("*", $table, "zone", $zone_id, $page, $PAGESIZE);
	while($row = $data->fetch()){
?>
<hr>
<div>
<?=$form->load_xml_string(xml_monster_form_upd($row))?>
</div>
<?php
	}
?>
<hr>
<?=$count?>件ヒット
</form>
</body>
</html>
<?php
}
?>
