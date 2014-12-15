<?php
//=====================================
// 管理者用 インフォメーション 追加 更新 削除
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/class/form.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/xml/info_form_upd.php");
$form_login_xml = "/var/www/functions/xml/admin_login_form.xml";
$form_add_xml = "/var/www/functions/xml/info_form_add.xml";
$PAGESIZE = 20;
$table = "info";

$page = 0;

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
}

if(isset($_SERVER["REQUEST_METHOD"]) == "POST") {

	// ログアウト
	if(isset($_POST["submit_logout"])) {
		session_destroy();
		selfpage();
	}

	// 新規作成
	if(isset($_POST["submit_add"])) {
		$cols = array("id","subject","info");
		foreach($cols as $col) {
			$values[] = isset($_POST["new_".$col]) ? "'".$_POST["new_".$col]."'" : 0;
		}
		$cols = implode(",", $cols);
		$values = implode(",", $values);
		$data->insert_data($table, $cols, $values);
	}

	// 変更
	if(isset($_POST["submit_upd"])) {
		$id = key($_POST["submit_upd"]);
		$target = "id=".$id;
		$cols = array("subject","info");
		foreach($cols as $col) {
			$values[] = isset($_POST[$col][$id]) ? preg_replace("/[\r][\n]/", "\n", $_POST[$col][$id]) : 0;
		}
		$data->update_data($table, $cols, $values, $target);
	}

	//ページの選択
	if(isset($_POST["page"])) $page = $_POST["page"];
}
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<?php
if(!isset($_SESSION["user"]) || !isset($_SESSION["pass"])) {
//管理ログイン
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
$count = $data->select_all_l("*", $table, $page, $PAGESIZE, "id", "desc");
?>
<h3>* * Infomation List * *</h3>
<?=$form->start()?>
<?=$form->submit("logout", "ログアウト")?>
<div>
インフォメーションに新規追加<br>
<?=$form->load_xml_file($form_add_xml)?>
</div>
<hr>
ページ
<?=$form->build_select_page($count, $PAGESIZE, $page)?>
<hr>
<?php
	while($row = $data->fetch()){
?>
<hr>
<div>
<?=$form->load_xml_string(xml_info_form_upd($row))?>
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
