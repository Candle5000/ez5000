<?php
//=====================================
// 管理者用 アイテムデータ 追加 更新 削除
//=====================================
require_once("../../class/mysql.php");
require_once("../../class/itemdata.php");
require_once("../../functions/form.php");
$xml = "/var/www/functions/xml/item_group.xml";
$PAGESIZE = 40;

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

	$item = new ItemData($_SESSION["user"], $_SESSION["pass"], "ezdata");
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
			$item->read_data($_POST["new_id"], $_POST["new_name"], $_POST["new_text"], isset($_POST["new_rare"]), isset($_POST["new_notrade"]), $_POST["new_price"], $_POST["new_stack"], $_POST["new_note"], isset($_POST["new_hidden"]));
			$item->add_full_data();
		}

		//--------------------------------
		// ファイルからデータを追加
		//--------------------------------
		if(isset($_POST["submit_upload"])) {
			if(is_uploaded_file($_FILES["txt"]["tmp_name"])) {
				if($fp = fopen($_FILES["txt"]["tmp_name"], "r")) {
					if(flock($fp, LOCK_SH)) {
						$registered = fgets($fp);
						while(!feof($fp)) {
							$n_id = preg_replace("/[^0-9]+/", "", fgets($fp));
							if(!strlen($n_id)) {break;}
							$n_name = rtrim(fgets($fp));
							$n_text = fgets($fp);
							if(strstr($n_text , "EOT")) {
								$n_text = rtrim(str_replace("EOT", "", $n_text));
							} else {
								$n_text = $n_text.rtrim(str_replace("EOT", "", fgets($fp)));
							}
							$n_tag = rtrim(fgets($fp));
							$n_rare = ($n_tag / 1) % 2;
							$n_notrade = ($n_tag / 2) % 2;
							$n_price = (($n_tag / 4) % 2) - 1;
							$n_stack = rtrim(fgets($fp));
							$item->read_file($n_id, $n_name, $n_text, $n_rare, $n_notrade, $n_price, $n_stack);
							$item->add_some_data();
						}
						flock($fp, LOCK_UN);
					} else {
						$form_error = "ファイルロックに失敗しました";
					}
				} else {
					$form_error = "ファイルオープンに失敗しました。";
				}
			}
		}

		//--------------------------------
		// 変更
		//--------------------------------
		if(isset($_POST["submit_upd"])) {
			$id = key($_POST["submit_upd"]);
			$item->read_data($id, $_POST["name"][$id], $_POST["text"][$id], isset($_POST["rare"][$id]), isset($_POST["notrade"][$id]), $_POST["price"][$id], $_POST["stack"][$id], $_POST["note"][$id], isset($_POST["hidden"][$id]));
			$item->update_data();
		}

		//--------------------------------
		// 削除
		//--------------------------------
		if(isset($_POST["submit_del"])) {
			$id = key($_POST["submit_del"]);
			$sql = "DELETE FROM items WHERE id=$id";
			$item->query($sql);
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
	if(strlen($item->error)) {
		$form_error = $item->error;
	}
	if(isset($item->error_list)) {
		echo '<pre style="text-align:left">';
		print_r($item->error_list);
		echo '</pre>';
	}
	if(strlen($form_error)) {
		echo "<div id=\"error\">".$form_error."</div>";
	}
	echo $n_name;
?>
<h3>* * Item List * *</h3>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="POST" enctype="multipart/form-data">
<input type="submit" name="submit_logout" value="ログアウト">
<div>
アイテムリストに新規追加<br>
<input type="text" name="new_id" value="" size="4">
<input type="text" name="new_name" value="" size="20"><br>
<textarea cols="48" rows="2" name="new_text"></textarea><br>
<input type="checkbox" name="new_rare" value="1">RARE 
<input type="checkbox" name="new_notrade" value="1">NOTRADE <br>
売却<input type="text" name="new_price" value="" size="8"> 
スタック<input type="text" name="new_stack" value="" size="4"><br>
<textarea cols="48" name="new_note"></textarea><br>
<input type="checkbox" name="new_hidden" value="1">未実装 
<input type="submit" name="submit_add" value="追加">
</div>
<hr>
database.txtから追加<br>
<input type="file" name="txt" size="40">
<input type="submit" name="submit_upload" value="送信">
<?php
	//アイテムグループの選択
	$group_id = 10000;
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
	$item->select_group($group_id, "id");
	$itemcount = $item->rows();
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
	//----------------------------------------
	// テーブルからデータを読む
	//----------------------------------------
	$item->select_groupl($group_id, "*", $page, $PAGESIZE);
	while($row = $item->fetch()){
		$id = $row["id"];
		$name = $row["name"];
		$text = $row["text"];
		$rare = $row["rare"];
		$notrade = $row["notrade"];
		$price = $row["price"];
		$stack = $row["stack"];
		$note = $row["note"];
		$hidden = $row["hidden"];
?>
<hr>
<div>
<?=$id?>:
<input type="text" name="name[<?=$id?>]" value="<?=$name?>" size="20"><br>
<textarea cols="48" rows="2" name="text[<?=$id?>]" wrap="soft"><?=$text?></textarea><br>
<input type="checkbox" name="rare[<?=$id?>]" <?=form_checked($rare)?>>RARE 
<input type="checkbox" name="notrade[<?=$id?>]" <?=form_checked($notrade)?>>NOTRADE <br>
売却<input type="text" name="price[<?=$id?>]" value="<?=$price?>" size="8"> 
スタック<input type="text" name="stack[<?=$id?>]" value="<?=$stack?>" size="4"><br>
<textarea cols="48" name="note[<?=$id?>]" wrap="soft"><?=$note?></textarea><br>
<input type="checkbox" name="hidden[<?=$id?>]" <?=form_checked($hidden)?>>未実装 
<input type="submit" name="submit_upd[<?=$id?>]" value="変更">
<input type="submit" name="submit_del[<?=$id?>]" value="削除">
</div>
<?php
	}
	//ここまでwhileループ[終了の閉じカッコ]
?>
<hr>
<?=$itemcount?>件ヒット
</form>
</body>
</html>
<?php
}
?>
