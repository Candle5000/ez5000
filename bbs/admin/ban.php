<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/input_check.php");

const PAGE_SIZE = 100;

session_start();

// ログイン情報の確認
if(!isset($_SESSION["admin_auth"])) {
	$http = "http";
	if(isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") $http .= "s";
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location:$http://{$_SERVER["HTTP_HOST"]}/bbs/admin/login.php");
	exit;
}

// DB接続
$user_file = "/etc/mysql-user/userBBS.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$mysql = new MySQL($userName, $password, $database);
if($mysql->connect_error) die("データベースの接続に失敗しました");

// 入力エラーリスト
$error_list = array();

// インフォメーションメッセージ
$info_list = array();

// 新規登録フォーム初期値
$add_ip = "";
$add_ua = "";
$add_note = "";

// POST送信時
if($_SERVER["REQUEST_METHOD"] == "POST") {
	if(isset($_POST["submit_add"])) {
		// 新規登録

		$add_ip = isset($_POST["ip"]) ? htmlspecialchars(trim($_POST["ip"])) : "";
		$add_ua = isset($_POST["ua"]) ? htmlspecialchars(trim($_POST["ua"])) : "";
		$add_note = isset($_POST["note"]) ? htmlspecialchars(trim($_POST["note"])) : "";

		// IP入力チェック
		$ip_reg = "([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])";
		if(strlen(trim($_POST["ip"])) > 0 && !preg_match("/^$ip_reg\\.($ip_reg\\.($ip_reg\\.($ip_reg)?)?)?$/", trim($_POST["ip"]))) {
			$error_list[] = "識別できないIPです。";
		}

		// UA入力チェック
		if(strlen(trim($_POST["ip"])) == 0 && strlen(trim($_POST["ua"])) == 0) {
			$error_list[] = "IPかUAのどちらかの入力が必要です。";
		}

		if(count($error_list) == 0) {
			$ip = trim($_POST["ip"]);
			$ua = $mysql->real_escape_string(trim($_POST["ua"]));
			$note = $mysql->real_escape_string(trim($_POST["note"]));
			$sql = "INSERT INTO bbs_ban (ip, ua, note, registered_date) VALUES ('$ip', '$ua', '$note', SYSDATE())";
			$mysql->query($sql);
			if($mysql->error) {
				$error_list[] = "登録時にエラーが発生しました。";
			} else {
				$info_list[] = "規制情報を新規登録しました。";
				$add_ip = "";
				$add_ua = "";
				$add_note = "";
			}
		}
	} else if(isset($_POST["submit_mod"])) {
		// 編集

		$id = key($_POST["submit_mod"]);
		if(!is_numeric($id)) $error_list[] = "不正な入力を検出しました。";
		$note = isset($_POST["note"][$id]) ? $mysql->real_escape_string(trim($_POST["note"][$id])) : "";

		if(count($error_list) == 0) {
			// データの存在チェック
			$sql = "SELECT 1 FROM bbs_ban WHERE id = $id";
			if($mysql->query($sql)->num_rows == 1) {
				// 更新処理
				$sql = "UPDATE bbs_ban SET note = '$note' WHERE id = $id";
				$mysql->query($sql);
				if($mysql->error) {
					$error_list[] = "更新時にエラーが発生しました。";
				} else {
					$info_list[] = "規制情報を更新しました。";
				}
			}
		}
	} else if(isset($_POST["submit_del"])) {
		// 削除

		$id = key($_POST["submit_del"]);
		if(!is_numeric($id)) $error_list[] = "不正な入力を検出しました。";

		if(count($error_list) == 0) {
			// データの存在チェック
			$sql = "SELECT 1 FROM bbs_ban WHERE id = $id";
			if($mysql->query($sql)->num_rows == 1) {
				// 削除処理
				$sql = "DELETE FROM bbs_ban WHERE id = $id";
				$mysql->query($sql);
				if($mysql->error) {
					$error_list[] = "削除時にエラーが発生しました。";
				} else {
					$info_list[] = "規制情報を削除しました。";
				}
			}
		}
	}
}

// GET入力エラーチェック
if(!isset($_GET["page"]) || !is_numeric($_GET["page"]) || $_GET["page"] < 1) {
	$page = 1;
} else {
	$page = $_GET["page"];
}

// ページ設定用
$start = ($page - 1) * PAGE_SIZE;
$size = PAGE_SIZE;

// 規制リストの読み込み
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM bbs_ban ORDER BY registered_date DESC LIMIT $start, $size";
$result = $mysql->query($sql);
$sql = "SELECT FOUND_ROWS() count";
$listCount = $mysql->query($sql)->fetch_object()->count;

// ページ遷移用リンク
$pageCount = ceil($listCount / PAGE_SIZE);
$pageLinkList = array();
for($i = 1; $i <= $pageCount; $i++) {
	if($i == $page) {
		$pageLinkList[] = "$i";
	} else {
		$pageLinkList[] = "<a href=\"./ban.php?page=$i\">$i</a>";
	}
}
$pageLink = implode(" | ", $pageLinkList);
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<h3>* * 掲示板管理メニュー * *</h3>
<h4>書き込み規制設定の編集</h4>
<?php
foreach($error_list as $error) {
?>
<div style="color:#F00;"><?=$error?></div>
<?php
}
foreach($info_list as $info) {
?>
<div style="color:#00F;"><?=$info?></div>
<?php
}
?>
<hr />
<div>新規追加</div>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
IP <input type="text" name="ip" size="15" value="<?=$add_ip?>" /><br />
UA <input type="text" name="ua" size="60" value="<?=$add_ua?>" /><br />
<textarea name="note" cols="80" rows="1" wrap="soft"><?=$add_note?></textarea><br />
<input type="submit" name="submit_add" value=" 追加 " />
</form>
<hr />
<div>ページ移動 <?=$pageLink?></div>
<hr />
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
<?php
if(!$listCount) {
?>
<div>規制情報が登録されていません。</div>
<hr />
<?php
}
while($array = $result->fetch_array()) {
	$ip = is_empty($array["ip"]) ? "未指定" : $array["ip"];
	$ua = is_empty($array["ua"]) ? "未指定" : htmlspecialchars($array["ua"]);
	$note = htmlspecialchars($array["note"]);
?>
IP:<?=$ip?><br />
UA:<?=$ua?><br />
登録日時:<?=$array["registered_date"]?><br />
<textarea name="note[<?=$array["id"]?>]" cols="80" rows="1" wrap="soft"><?=$note?></textarea><br />
<input type="submit" name="submit_mod[<?=$array["id"]?>]" value=" 編集 " />
<input type="submit" name="submit_del[<?=$array["id"]?>]" value=" 削除 " />
<hr />
<?php
}
?>
</form>
<ul style="list-style-type:none; text-align:right;">
<li><a href="./">掲示板管理トップに戻る</a></li>
<li><a href="/" target="_blank">トップページを開く</a></li>
</ul>
<hr />
<hr />
</body>
</html>
