<?php
//=====================================
// 管理者用 掲示板管理メニューログイン
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");

session_start();

//ログイン
if(isset($_SERVER["REQUEST_METHOD"]) == "POST") {
	if(isset($_POST["submit_login"])) {

		// DB接続
		$user_file = "/etc/mysql-user/user5000.ini";
		if($fp_user = fopen($user_file, "r")) {
			$userName = rtrim(fgets($fp_user));
			$password = rtrim(fgets($fp_user));
			$database = rtrim(fgets($fp_user));
		} else {
			die("接続設定の読み込みに失敗しました");
		}
		$mysql = new MySQL($userName, $password, $database);
		if($mysql->connect_error) die("データベースの接続に失敗しました");

		$userid = $mysql->real_escape_string(rtrim($_POST["user"]));
		$passwd = "70a9a9e8c1ba195424a1aac50c7afd03df860f01".rtrim($mysql->real_escape_string($_POST["pass"]));
		$sql = "SELECT 1 FROM bbs_admin WHERE user_id = '$userid' AND password = PASSWORD('$passwd')";
		echo $sql;
		$result = $mysql->query($sql);
		if($result->num_rows > 0) {
			$_SESSION["admin_auth"] = $userid;
		} else {
			$error_list[] = "ユーザー名またはパスワードが違います";
		}
	}
}

// ログイン状態の確認 ログイン済みならメニューへリダイレクト
if(isset($_SESSION["admin_auth"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/admin.php");
	exit;
}
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h1>管理ログイン</h1>
<?php
// 入力エラーリスト表示
if(isset($error_list)) {
?>
<div style="color:#F00;">
<?=implode("<br />\n", $error_list)?>
</div>
<?php
}
?>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="post" enctype="multipart/form-data">
<div>
ユーザー名<br />
<input name="user" type="text" maxlength="32" value="" />
</div>
<div>
パスワード<br />
<input name="pass" type="password" maxlength="32" value="" />
</div>
<input type="submit" name="submit_login" value=" 送信 " />
</form>
</body>
</html>
