<?php
//=====================================
// 管理者用 掲示板管理メニュー
//=====================================
require_once("/var/www/bbs/class/mysql.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/input_check.php");

const PAGE_SIZE = 50;

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

// POST送信時
if($_SERVER["REQUEST_METHOD"] == "POST") {
	if(isset($_POST["submit_del"])) {
		// 削除

		$id = key($_POST["submit_del"]);
		if(!is_numeric($id)) $error_list[] = "不正な入力を検出しました。";

		if(count($error_list) == 0) {
			// データの存在チェック
			$sql = "SELECT 1 FROM report WHERE id = $id";
			if($mysql->query($sql)->num_rows == 1) {
				// トランザクション開始
				$mysql->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				// 削除ログ登録
				$sql = "INSERT INTO report_deleted (id, bid, mid, comment, ts, ip, hostname, ua, uid, user_id, guest_id, created_at) ";
				$sql .= "SELECT id, bid, mid, comment, ts, ip, hostname, ua, uid, user_id, guest_id, NOW()";
				$sql .= " FROM report WHERE id = $id";
				$mysql->query($sql);
				if($mysql->error) {
					$error_list[] = "処理中にエラーが発生しました。";
					$mysql->rollback();
				} else {
					// 削除処理
					$sql = "DELETE FROM report WHERE id = $id";
					$mysql->query($sql);
					if($mysql->error) {
						$error_list[] = "処理中にエラーが発生しました。";
						$mysql->rollback();
					} else {
						$info_list[] = "通報を対応済みにしました。";
						$mysql->commit();
					}
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

// 通報リストの読み込み
$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM report ORDER BY id DESC LIMIT $start, $size";
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
		$pageLinkList[] = "<a href=\"./report.php?page=$i\">$i</a>";
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
<h4>通報一覧</h4>
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
<div>ページ移動 <?=$pageLink?></div>
<hr />
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST">
<?php
if(!$listCount) {
?>
<div>通報が登録されていません。</div>
<hr />
<?php
}
while($array = $result->fetch_array()) {
?>
<?=nl2br(htmlspecialchars($array["comment"]))?><br />
IP:<?=$array["ip"]?><br />
UA:<?=htmlspecialchars($array["ua"])?><br />
固体識別番号:<?=$array["uid"]?><br />
ユーザーID:<?=$array["user_id"]?><br />
ゲストID:<?=$array["guest_id"]?><br />
登録日時:<?=$array["ts"]?><br />
<input type="submit" name="submit_del[<?=$array["id"]?>]" value=" 処理済 " />
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
