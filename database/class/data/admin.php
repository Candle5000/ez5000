<?php
//=====================================
// 管理者用 クラスステータスデータ 追加 更新 削除
//=====================================
mb_regex_encoding("UTF-8");
require_once("../../../class/mysql.php");
require_once("../../../class/statusdata.php");
require_once("../../../functions/form.php");
require_once("../../../functions/class.php");

session_start();
if(isset($_SERVER["REQUEST_METHOD"]) == "POST") {
	if(isset($_POST["submit_login"])) {
		$_SESSION["user"] = $_POST["user"];
		$_SESSION["pass"] = $_POST["pass"];
	}
}

$table = "FIG";
if(isset($_POST["class"])) {
	$table = $_POST["class"];
}

//管理ログイン
if(!isset($_SESSION["user"]) || !isset($_SESSION["pass"])) {
?>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<title>管理者用 追加・更新・削除</title>
</head>
<body>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST" enctype="multipart/form-data">
ユーザー名<input type="text" name="user" value="" size="10"><br />
パスワード<input type="password" name="pass" value="" size="10"><br />
<input type="submit" name="submit_login" value="ログイン"><br />
</form>
管理者ユーザー名とパスワードを入力してください
</body>
</html>
<?php
} else {
//ログイン済

	$status = new StatusData($_SESSION["user"], $_SESSION["pass"], "ezdata", $table);

	//-----------------------------
	// POSTされたとき
	//-----------------------------
	if($_SERVER["REQUEST_METHOD"] == "POST") {
		
		//-----------------------------
		// ログアウト
		//-----------------------------
		if(isset($_POST["submit_logout"])) {
			session_destroy();
		}
		
		//-----------------------------
		// 範囲指定新規作成
		//-----------------------------
		if(isset($_POST["submit_addempty"])) {
			$status->add_empty($_POST["start"], $_POST["end"]);
		}
		
		//-----------------------------
		// 新規作成
		//-----------------------------
		if(isset($_POST["submit_add"])) {
			$status->read_data($_POST["new_lv"], $_POST["new_hp"], $_POST["new_sp"], $_POST["new_str"], $_POST["new_vit"], $_POST["new_dex"], $_POST["new_agi"], $_POST["new_wis"], $_POST["new_wil"]);
			$status->add_data();
		}
		
		//--------------------------------
		// 変更
		//--------------------------------
		if (isset($_POST["submit_upd"])) {
			$lv = key($_POST["submit_upd"]);
			$status->read_data($lv, $_POST["hp"][$lv], $_POST["sp"][$lv], $_POST["str"][$lv], $_POST["vit"][$lv], $_POST["dex"][$lv], $_POST["agi"][$lv], $_POST["wis"][$lv], $_POST["wil"][$lv]);
			$status->update_data();
		}
		
		//--------------------------------
		// 削除
		//--------------------------------
		if (isset($_POST["submit_del"])) {
			$lv = key($_POST["submit_del"]);
			$status->delete_data($lv);
		}
	}
?>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<title>管理者用 追加・更新・削除</title>
</head>
<body>
<?$status->print_error()?>
<h3>* * Status Data * *</h3>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST" enctype="multipart/form-data">
<input type="submit" name="submit_logout" value="ログアウト">
<hr>
<div>
Lv<input type="text" name="start" value="" size="1">から 
Lv<input type="text" name="end" value="" size="1"> まで 
<input type="submit" name="submit_addempty" value="追加">
</div>
<hr>
<div>
ステータスデータに新規追加<br>
Lv<input type="text" name="new_lv" value="" size="1"> 
HP<input type="text" name="new_hp" value="" size="2"> 
SP<input type="text" name="new_sp" value="" size="2"> 
STR<input type="text" name="new_str" value="" size="1"> 
VIT<input type="text" name="new_vit" value="" size="1"> 
DEX<input type="text" name="new_dex" value="" size="1"> 
AGI<input type="text" name="new_agi" value="" size="1"> 
WIS<input type="text" name="new_wis" value="" size="1"> 
WIL<input type="text" name="new_wil" value="" size="1"> 
<input type="submit" name="submit_add" value="追加">
</div>
<hr>
<div>
<select name="class">
<option <?= form_selected("FIG", $table) ?>>ファイター</option>
<option <?= form_selected("MAG", $table) ?>>メイジ</option>
<option <?= form_selected("CLR", $table) ?>>クレリック</option>
<option <?= form_selected("SCO", $table) ?>>スカウト</option>
<option <?= form_selected("WAR", $table) ?>>ウォーリア</option>
<option <?= form_selected("GRD", $table) ?>>ガーディアン</option>
<option <?= form_selected("SOR", $table) ?>>ソーサラー</option>
<option <?= form_selected("WLK", $table) ?>>ウォーロック</option>
<option <?= form_selected("TMP", $table) ?>>テンプラー</option>
<option <?= form_selected("BIS", $table) ?>>ビショップ</option>
<option <?= form_selected("RNG", $table) ?>>レンジャー</option>
<option <?= form_selected("ROG", $table) ?>>ローグ</option>
</select>
<input type="submit" name="submit_select" value="表示">
</div>
<hr>
<?php
	//----------------------------------------	
	// テーブルからデータを読む
	//----------------------------------------
	$status->select_all();
	while($row = $status->fetch()) {
		$lv = num_length($row["lv"]);
		$hp = $row["hp"];
		$sp = $row["sp"];
		$str = $row["str"];
		$vit = $row["vit"];
		$dex = $row["dex"];
		$agi = $row["agi"];
		$wis = $row["wis"];
		$wil = $row["wil"];
?>
<div>
Lv<?=$lv?>:
HP<input type="text" name="hp[<?=$lv?>]" value="<?=$hp?>" size="2">
SP<input type="text" name="sp[<?=$lv?>]" value="<?=$sp?>" size="2">
STR<input type="text" name="str[<?=$lv?>]" value="<?=$str?>" size="1">
VIT<input type="text" name="vit[<?=$lv?>]" value="<?=$vit?>" size="1">
DEX<input type="text" name="dex[<?=$lv?>]" value="<?=$dex?>" size="1">
AGI<input type="text" name="agi[<?=$lv?>]" value="<?=$agi?>" size="1">
WIS<input type="text" name="wis[<?=$lv?>]" value="<?=$wis?>" size="1">
WIL<input type="text" name="wil[<?=$lv?>]" value="<?=$wil?>" size="1">
<input type="submit" name="submit_upd[<?=$lv?>]" value="変更">
<input type="submit" name="submit_del[<?=$lv?>]" value="削除">
</div>
<?php
		if(($lv % 5) == 0) {
			echo "<hr>\n";
		}
	}
	//ここまでwhileループ[終了の閉じカッコ]
?>
<hr>
<?=$status->rows()?>件ヒット
<?php
}
?>
</form>
</body>
</html>

