<?php
//=====================================
// 管理者用 アイテムデータ 追加 更新 削除
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/itemdata.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/class/form.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/form.php");
require_once("/var/www/functions/item.php");
require_once("/var/www/functions/xml/item_form_upd.php");
$group_xml = "/var/www/functions/xml/item_group.xml";
$form_login_xml = "/var/www/functions/xml/admin_login_form.xml";
$form_add_xml = "/var/www/functions/xml/item_form_add.xml";
$PAGESIZE = 40;
$table = "items";

$group_id = 10000;
$page = 0;

session_start();

if(isset($_SERVER["REQUEST_METHOD"]) == "POST") {

	// ログアウト
	if(isset($_POST["submit_logout"])) {
		session_destroy();
		selfpage();
	}

	//ログイン
	if(isset($_POST["submit_login"])) {
		$_SESSION["user"] = $_POST["user"];
		$_SESSION["pass"] = $_POST["pass"];
	}

	// 新規作成
	if(isset($_POST["submit_add"])) {
		$cols = "id,name,text,rare,notrade,price,stack,note,hidden";
		foreach($_POST as $key=>$post) {
			if(preg_match("/new_[a-zA-Z]+/", $key)) $values[] = $post;
		}
		$values = implode(",", $values);
		$data->insert_data($table, $cols, $values);
	}

	// 変更
	if(isset($_POST["submit_upd"])) {
		$id = key($_POST["submit_upd"]);
		$target = "id=".$id;
		$cols = array("name","text","rare","notrade","price","stack","note","hidden");
		foreach($cols as $col) {
			$values[] = $_POST[$col][$id];
		}
		$data->update_data($table, $cols, $values, $target);
	}

	// 最初のページ
	if(isset($_POST["submit_select"])) $_POST["page"] = 0;

	//グループの選択
	if(isset($_POST["group"])) $group_id = $_POST["group"];

	//ページの選択
	if(isset($_POST["page"])) $page = $_POST["page"];
}

$form = new Form($_SERVER["PHP_SELF"], "POST", "multipart/form-data");
if(isset($_SESSION["user"]) && isset($_SESSION["pass"])) {
	$data = new AdminData($_SESSION["user"], $_SESSION["pass"], "ezdata");
}
?>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta http-equiv="content-language" content="ja" />
<?php
if(device_info() == "sp") {
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<?php
}
?>
<title>管理者用 追加・更新</title>
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
?>
<h3>* * Item List * *</h3>
<?=$form->start()?>
<input type="submit" name="submit_logout" value="ログアウト">
<div>
アイテムリストに新規追加<br>
<?=$form->load_xml_file($form_add_xml)?>
</div>
<hr>
<div>
グループ
<select name="group">
<?php
	$categories = simplexml_load_file($group_xml);
	foreach($categories->category as $category) {
		foreach($category->group as $group) {
?>
<option <?=form_selected($group["id"], $group_id)?>><?=$category["name"]?> <?=$group["name"]?></option>
<?php
		}
	}
?>
</select>
<input type="submit" name="submit_select" value="表示">
</div>
<hr>
ページ
<select name="page">
<?php
	$data->select_group("id", $table, $group_id, item_group_end($group_id));
	$itemcount = $data->rows();
	for($s_page = 0; $s_page < $itemcount; $s_page += $PAGESIZE) {
?>
<option <?=form_selected($s_page, $page)?>><?=($s_page + 1)?>-<?=($s_page + $PAGESIZE)?></option>
<?php
	}
?>
</select>
<input type="submit" name="submit_page" value="表示">
<hr>
<?php
	// テーブルからデータを読む
	$data->select_group_l("*", $table, $group_id, item_group_end($group_id), $page, $PAGESIZE);
	while($row = $data->fetch()){
?>
<hr>
<div>
<?=$form->load_xml_string(xml_item_form_upd($row))?>
</div>
<?php
	}
?>
<hr>
<?=$itemcount?>件ヒット
</form>
</body>
</html>
<?php
}
?>
