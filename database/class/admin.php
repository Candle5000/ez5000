<?php
//=====================================
// 管理者用 クラスデータ 追加 更新 削除
//=====================================
mb_regex_encoding("UTF-8");
require_once("../../class/mysql.php");
require_once("../../class/classdata.php");
require_once("../../functions/form.php");

session_start();
if(isset($_SERVER["REQUEST_METHOD"]) == "POST") {
	if(isset($_POST["submit_login"])) {
		$_SESSION["user"] = $_POST["user"];
		$_SESSION["pass"] = $_POST["pass"];
	}
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

	$class = new ClassData($_SESSION["user"], $_SESSION["pass"], "ezdata");

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
		// 新規作成
		//-----------------------------
		if(isset($_POST["submit_add"])) {
			$class->read_data($_POST["new_id"], $_POST["new_name"], $_POST["new_nameE"], $_POST["new_nameS"], $_POST["new_dagger"], $_POST["new_sword"], $_POST["new_axe"], $_POST["new_hammer"], $_POST["new_wand"], $_POST["new_bow"], $_POST["new_dodge"], $_POST["new_shield"], $_POST["new_element"], $_POST["new_light"], $_POST["new_dark"], $_POST["new_note"]);
			$class->add_data();
		}
		
		//--------------------------------
		// 変更
		//--------------------------------
		if (isset($_POST["submit_upd"])) {
			$id = key($_POST["submit_upd"]);
			$class->read_data($id, $_POST["name"][$id], $_POST["nameE"][$id], $_POST["nameS"][$id], $_POST["dagger"][$id], $_POST["sword"][$id], $_POST["axe"][$id], $_POST["hammer"][$id], $_POST["wand"][$id], $_POST["bow"][$id], $_POST["dodge"][$id], $_POST["shield"][$id], $_POST["element"][$id], $_POST["light"][$id], $_POST["dark"][$id], $_POST["note"][$id]);
			$class->update_data();
		}
		
		//--------------------------------
		// 削除
		//--------------------------------
		if (isset($_POST["submit_del"])) {
			$id = key($_POST["submit_del"]);
			$class->delete_data($id);
		}
	}
?>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<title>管理者用 追加・更新・削除</title>
</head>
<body>
<?$class->print_error()?>
<h3>* * Class Data * *</h3>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST" enctype="multipart/form-data">
<input type="submit" name="submit_logout" value="ログアウト">
<div>
クラスデータに新規追加<br>
ID<input type="text" name="new_id" value="" size="4"> 
NAME<input type="text" name="new_name" value="" size="20"><br>
E_NAME<input type="text" name="new_nameE" value="" size="20"> 
S_NAME<input type="text" name="new_nameS" value="" size="4"><br>
短剣<input type="text" name="new_dagger" value="" size="1"> 
長剣<input type="text" name="new_sword" value="" size="1"> 
斧<input type="text" name="new_axe" value="" size="1"> 
槌<input type="text" name="new_hammer" value="" size="1"> 
杖<input type="text" name="new_wand" value="" size="1"> 
弓<input type="text" name="new_bow" value="" size="1"><br />
回避<input type="text" name="new_dodge" value="" size="1"> 
盾<input type="text" name="new_shield" value="" size="1"> 
元素<input type="text" name="new_element" value="" size="1"> 
光<input type="text" name="new_light" value="" size="1"> 
闇<input type="text" name="new_dark" value="" size="1"><br />
<textarea cols="48" name="new_note"></textarea><br />
<input type="submit" name="submit_add" value="追加">
</div>
<hr>
<?php
	//----------------------------------------	
	// テーブルからデータを読む
	//----------------------------------------
	$class->select_all();
	while($row = $class->fetch()) {
		$id = $row["id"];
		$name = $row["name"];
		$nameE = $row["nameE"];
		$nameS = $row["nameS"];
		$dagger = $row["dagger"];
		$sword = $row["sword"];
		$axe = $row["axe"];
		$hammer = $row["hammer"];
		$wand = $row["wand"];
		$bow = $row["bow"];
		$dodge = $row["dodge"];
		$shield = $row["shield"];
		$element = $row["element"];
		$light = $row["light"];
		$dark = $row["dark"];
		$note = $row["note"];
?>
<hr>
<div>
ID<?=$id?>:
NAME<input type="text" name="name[<?=$id?>]" value="<?=$name?>" size="20"><br />
NAME_E<input type="text" name="nameE[<?=$id?>]" value="<?=$nameE?>" size="20"> 
NAME_S<input type="text" name="nameS[<?=$id?>]" value="<?=$nameS?>" size="4"><br />
短剣<input type="text" name="dagger[<?=$id?>]" value="<?=$dagger?>" size="1"> 
長剣<input type="text" name="sword[<?=$id?>]" value="<?=$sword?>" size="1"> 
斧<input type="text" name="axe[<?=$id?>]" value="<?=$axe?>" size="1"> 
槌<input type="text" name="hammer[<?=$id?>]" value="<?=$hammer?>" size="1"> 
杖<input type="text" name="wand[<?=$id?>]" value="<?=$wand?>" size="1"> 
弓<input type="text" name="bow[<?=$id?>]" value="<?=$bow?>" size="1"><br />
回避<input type="text" name="dodge[<?=$id?>]" value="<?=$dodge?>" size="1"> 
盾<input type="text" name="shield[<?=$id?>]" value="<?=$shield?>" size="1"> 
元素<input type="text" name="element[<?=$id?>]" value="<?=$element?>" size="1"> 
光<input type="text" name="light[<?=$id?>]" value="<?=$light?>" size="1"> 
闇<input type="text" name="dark[<?=$id?>]" value="<?=$dark?>" size="1"><br />
<textarea cols="48" name="note[<?=$id?>]"><?=$note?></textarea><br />
<input type="submit" name="submit_upd[<?=$id?>]" value="変更">
<input type="submit" name="submit_del[<?=$id?>]" value="削除">
</div>
<?php
	}
	//ここまでwhileループ[終了の閉じカッコ]
?>
<hr>
<?=$class->rows()?>件ヒット
<?php
}
?>
</form>
</body>
</html>

