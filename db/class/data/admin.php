<?php
//=====================================
// 管理者用 クラスステータスデータ 追加 更新 削除
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/class/form.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/class.php");
require_once("/var/www/functions/xml/status_form_upd.php");
$form_login_xml = "/var/www/functions/xml/admin_login_form.xml";
$form_add_xml = "/var/www/functions/xml/status_form_add.xml";

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
	$table = isset($_POST["table"]) ? $_POST["table"] : "FIG";
	$data->select_all("class");
	$select_part = array('part' => 'select', 'name' => 'table', 'selected' => $table);
	while($row = $data->fetch()) {
		$select_part["option"]["{$row["nameS"]}"] = $row["name"];
	}
}

if(isset($_SERVER["REQUEST_METHOD"]) == "POST" && !isset($login_err)) {

	// ログアウト
	if(isset($_POST["submit_logout"])) {
		session_destroy();
		selfpage();
	}

	// 新規作成
	if(isset($_POST["submit_add"])) {
		$cols = array("lv","hp","sp","str","vit","dex","agi","wis","wil");
		foreach($cols as $col) {
			$values[] = isset($_POST["new_".$col]) ? "'".$_POST["new_".$col]."'" : 0;
		}
		$cols = implode(",", $cols);
		$values = implode(",", $values);
		$data->insert_data($table, $cols, $values);
		$data->timestamp("class", "nameS='$table'");
	}

	// 範囲指定新規作成
	if(isset($_POST["submit_addmulti"])) {
		for($lv = $_POST["start"]; $lv <= $_POST["end"]; $lv++) {
			$data->insert_data($table, "lv", $lv);
		}
		$data->timestamp("class", "nameS='$table'");
	}

	// 変更
	if(isset($_POST["submit_upd"])) {
		$lv = key($_POST["submit_upd"]);
		$target = "lv=".$lv;
		$cols = array("hp","sp","str","vit","dex","agi","wis","wil");
		foreach($cols as $col) {
			$values[] = isset($_POST[$col][$id]) ? preg_replace("/[\r][\n]/", "\n", $_POST[$col][$id]) : 0;
		}
		$data->update_data($table, $cols, $values, $target);
		$data->timestamp("class", "nameS='$table'");
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
?>
<h3>* * Status Data * *</h3>
<?=$form->start()?>
<?=$form->submit("logout", "ログアウト")?>
<hr>
<div>
ステータスデータに新規追加<br>
<?=$form->load_xml_file($form_add_xml)?>
</div>
<hr>
<div>
<?=$form->build($select_part)?>
<?=$form->submit("table", "表示")?>
</div>
<hr>
<?php
	$data->select_all($table);
	$count = $data->rows();
	while($row = $data->fetch()) {
		$row["lv"] = num_length($row["lv"]);
?>
<div>
<?=$form->load_xml_string(xml_status_form_upd($row))?>
</div>
<?php
		if(($row["lv"] % 5) == 0) {
			echo "<hr>\n";
		}
	}
?>
<hr>
<?=$count?>件ヒット
<?php
}
?>
</form>
</body>
</html>
