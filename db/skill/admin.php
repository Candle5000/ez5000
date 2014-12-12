<?php
//=====================================
// 管理者用 スキルデータ 追加 更新 削除
//=====================================
require_once("../../class/mysql.php");
require_once("../../class/skilldata.php");
require_once("../../functions/form.php");
$xml = "/var/www/functions/xml/skill_group.xml";
$PAGESIZE = 20;

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

	$skill = new SkillData($_SESSION["user"], $_SESSION["pass"], "ezdata");
	$form_error = "";
	$registered = "";
	$n_name = "";
	$page = 0;

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
			$skill->read_data($_POST["new_id"], $_POST["new_name"], $_POST["new_category"], $_POST["new_learning"], $_POST["new_cost"], $_POST["new_recast"], $_POST["new_cast"], $_POST["new_text"], $_POST["new_note"], $_POST["new_ep"], $_POST["new_enhance"]);
			$skill->add_full_data();
		}

		//--------------------------------
		// 変更
		//--------------------------------
		if(isset($_POST["submit_upd"])) {
			$id = key($_POST["submit_upd"]);
			$skill->read_data($id, $_POST["name"][$id], $_POST["category"][$id], $_POST["learning"][$id], $_POST["cost"][$id], $_POST["recast"][$id], $_POST["cast"][$id], $_POST["text"][$id], $_POST["note"][$id], $_POST["ep"][$id], $_POST["enhance"][$id]);
			$skill->update_data();
		}

		//--------------------------------
		// 削除
		//--------------------------------
		if(isset($_POST["submit_del"])) {
			$id = key($_POST["submit_del"]);
			$skill->delete_data($id);
		}

		//--------------------------------
		// 最初のページ
		//--------------------------------
		if(isset($_POST["submit_select"])) {
			$_POST["page"] = 0;
		}
	}
?>
<html>
<head>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<title>管理者用 追加・更新・削除</title>
</head>
<body>
<?php
	if(strlen($skill->error)) {
		$form_error = $skill->error;
	}
	if(isset($skill->error_list)) {
		echo '<pre style="text-align:left">';
		print_r($skill->error_list);
		echo '</pre>';
	}
	if(strlen($form_error)) {
		echo "<div id=\"error\">".$form_error."</div>";
	}
	echo $n_name;
?>
<h3>* * Skill List * *</h3>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST" enctype="multipart/form-data">
<input type="submit" name="submit_logout" value="ログアウト">
<div>
スキルリストに新規追加<br>
ID<input type="text" name="new_id" value="" size="4"> 
Name<input type="text" name="new_name" value="" size="20"> 
Category<input type="text" name="new_category" value="" size="3"><br>
Learning<textarea cols="48" rows="2" name="new_learning"></textarea><br>
Cost<input type="text" name="new_cost" value="" size="3"> 
Recast<input type="text" name="new_recast" value="" size="6"> 
Cast<input type="text" name="new_cast" value="" size="6"><br>
Text<textarea cols="48" rows="2" name="new_text"></textarea><br>
Note<textarea cols="48" name="new_note"></textarea><br>
Enhance : EP<input type="text" name="new_ep" value="" size="3"><br> 
<textarea cols="48" rows="2" name="new_enhance"></textarea><br>
<input type="submit" name="submit_add" value="追加">
</div>
<?php
	//アイテムグループの選択
	$group_id = 10;
	if(isset($_POST["group"])) {
		$group_id = $_POST["group"];
	}
?>
<hr>
<div>
グループ
<select name="group">
<?php
	$categories = simplexml_load_file($xml);
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
<?php
	//ページの選択
	$page = 0;
	if(isset($_POST["page"])) {
		$page = $_POST["page"];
	}
?>
<hr>
ページ
<select name="page">
<?php
	$skill->search_category($group_id);
	$skillcount = $skill->rows();
	for($s_page = 0; $s_page < $skillcount; $s_page += $PAGESIZE) {
?>
<option <?=form_selected($s_page, $page)?>><?=($s_page + 1)?>-<?=($s_page + $PAGESIZE)?></option>
<?php
	}
?>
</select>
<input type="submit" name="submit_page" value="表示">
<hr>
<?php
	//----------------------------------------
	// テーブルからデータを読む
	//----------------------------------------
	$skill->search_categoryl($group_id, $page, $PAGESIZE);
	while($row = $skill->fetch()) {
		$id = $row["id"];
		$name = $row["name"];
		$category = $row["category"];
		$learning = $row["learning"];
		$cost = $row["cost"];
		$recast = $row["recast"];
		$cast = $row["cast"];
		$text = $row["text"];
		$note = $row["note"];
		$ep = $row["ep"];
		$enhance = $row["enhance"];
?>
<hr>
<div>
<?=$id?>:
Name<input type="text" name="name[<?=$id?>]" value="<?=$name?>" size="20"> 
Category<input type="text" name="category[<?=$id?>]" value="<?=$category?>" size="3"><br>
Learning<textarea cols="48" rows="2" name="learning[<?=$id?>]"><?=$learning?></textarea><br>
Cost<input type="text" name="cost[<?=$id?>]" value="<?=$cost?>" size="3"> 
Recast<input type="text" name="recast[<?=$id?>]" value="<?=$recast?>" size="6"> 
Cast<input type="text" name="cast[<?=$id?>]" value="<?=$cast?>" size="6"><br>
Text<textarea cols="48" rows="2" name="text[<?=$id?>]"><?=$text?></textarea><br>
Note<textarea cols="48" name="note[<?=$id?>]"><?=$note?></textarea><br>
Enhance : EP<input type="text" name="ep[<?=$id?>]" value="<?=$ep?>" size="3"><br> 
<textarea cols="48" rows="2" name="enhance[<?=$id?>]"><?=$enhance?></textarea><br>
<input type="submit" name="submit_upd[<?=$id?>]" value="変更">
<input type="submit" name="submit_del[<?=$id?>]" value="削除">
</div>
<?php
	}
	//ここまでwhileループ[終了の閉じカッコ]
?>
<hr>
<?=$skillcount?>件ヒット
</form>
</body>
</html>
<?php
}
?>
