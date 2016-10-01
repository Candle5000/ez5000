<?php
//=====================================
// 管理者用 メニューページ
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/class/admindata.php");
require_once("/var/www/class/form.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/xml/info_form_upd.php");
$form_login_xml = "/var/www/functions/xml/admin_login_form.xml";

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
<h3>* * 管理メニュー * *</h3>
<?=$form->start()?>
<?=$form->submit("logout", "ログアウト")?>
</form>
<div style="color:#F00;">[管理補佐の方へ]<br>
権限のない機能は使用しないでください。<br>
データの削除は管理人に申請してください。</div>
<ul>
<li><a href="/info/admin.php">インフォメーション</a></li>
<li><a href="/db/update/admin.php">アプリ更新情報</a></li>
<li><a href="/db/item/admin.php">アイテムデータ</a></li>
<li><a href="/db/zone/admin.php">ゾーンデータ</a></li>
<li><a href="/db/monster/admin.php">モンスターデータ</a></li>
<li><a href="/db/quest/admin.php">クエストデータ</a></li>
<li><a href="/db/class/admin.php">クラスデータ</a></li>
<li><a href="/db/class/data/admin.php">ステータスデータ</a></li>
<li><a href="/db/class/skill/admin.php">戦闘/属性スキルデータ</a></li>
<li><a href="/db/skill/admin.php">スキルデータ</a></li>
</ul>
<hr>
<a href="/" target="_blank">トップページを開く</a>
<hr>
<hr>
</body>
</html>
<?php
}
?>
