<?php
//=====================================
// 管理者用 戦闘/属性スキルデータ 追加 更新 削除
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/class/form.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/class.php");
require_once("/var/www/functions/xml/bmskill_form_upd.php");
$form_login_xml = "/var/www/functions/xml/admin_login_form.xml";
$form_add_xml = "/var/www/functions/xml/bmskill_form_add.xml";
$table = "bmskill";

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
	$data->select_all($table);
}

if(isset($_SERVER["REQUEST_METHOD"]) == "POST" && !isset($login_err)) {

	// ログアウト
	if(isset($_POST["submit_logout"])) {
		session_destroy();
		selfpage();
	}

	// 新規作成
	if(isset($_POST["submit_add"])) {
		$cols = array("id","S","A","B","C","D","E","F");
		foreach($cols as $col) {
			$values[] = isset($_POST["new_".$col]) ? "'".$_POST["new_".$col]."'" : 0;
		}
		$cols = implode(",", $cols);
		$values = implode(",", $values);
		$data->insert_data($table, $cols, $values);
	}

	// 範囲指定新規作成
	if(isset($_POST["submit_addmulti"])) {
		for($id = $_POST["start"]; $id <= $_POST["end"]; $id++) {
			$data->insert_data($table, "id", $id);
		}
	}

	// 変更
	if(isset($_POST["submit_upd"])) {
		$id = key($_POST["submit_upd"]);
		$target = "id=".$id;
		$cols = array("S","A","B","C","D","E","F");
		foreach($cols as $col) {
			$values[] = isset($_POST[$col][$id]) ? preg_replace("/[\r][\n]/", "\n", $_POST[$col][$id]) : 0;
		}
		$data->update_data($table, $cols, $values, $target);
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
戦闘/属性スキルに新規追加<br>
<?=$form->load_xml_file($form_add_xml)?>
</div>
<hr>
<?php
	$data->select_all($table);
	$count = $data->rows();
	while($row = $data->fetch()) {
		$row["id"] = num_length($row["id"]);
?>
<div>
<?=$form->load_xml_string(xml_bmskill_form_upd($row))?>
</div>
<?php
		if(($row["id"] % 5) == 0) {
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
