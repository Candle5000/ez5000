<?php
//=====================================
// 管理者用 ゾーンデータ 追加 更新 削除
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/class/form.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/xml/zone_form_upd.php");
$form_login_xml = "/var/www/functions/xml/admin_login_form.xml";
$form_add_xml = "/var/www/functions/xml/zone_form_add.xml";
$table = "zone";

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

	// 新規作成
	if(isset($_POST["submit_add"])) {
		$cols = array("id","name","nameE","nameS","event");
		foreach($cols as $col) {
			$values[] = isset($_POST["new_".$col]) ? "'".mysqli_real_escape_string($data->m_Con, $_POST["new_".$col])."'" : 0;
		}
		$cols = implode(",", $cols);
		$values = implode(",", $values);
		$data->insert_data($table, $cols, $values);
		$data->timestamp($table, "id=".$_POST["new_id"]);
	}

	// 変更
	if(isset($_POST["submit_upd"])) {
		$id = key($_POST["submit_upd"]);
		$target = "id=".$id;
		$cols = array("name","nameE","nameS","event");
		foreach($cols as $col) {
			$values[] = isset($_POST[$col][$id]) ? mysqli_real_escape_string($data->m_Con, preg_replace("/[\r][\n]/", "\n", $_POST[$col][$id])) : 0;
		}
		$data->update_data($table, $cols, $values, $target);
		$data->timestamp($table, $target);
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
<body>
<h3>* * Zone Data * *</h3>
<a href="/admin.php">管理メニューに戻る</a>
<?=$form->start()?>
<?=$form->submit("logout", "ログアウト")?>
<div>
ゾーンデータに新規追加<br>
<?=$form->load_xml_file($form_add_xml)?>
</div>
<hr>
<?php
	$data->select_all($table);
	while($row = $data->fetch()) {
?>
<hr>
<div>
<?=$form->load_xml_string(xml_zone_form_upd($row))?>
</div>
<?php
	}
?>
<hr>
<?=$data->rows()?>件ヒット
<?php
}
?>
</form>
</body>
</html>
