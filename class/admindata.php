<?php
//==============================
//AdminDataクラス
//==============================
class AdminData extends GuestData {

	//--------------------------
	//コンストラクタ
	//--------------------------
	function AdminData($userName, $password, $database) {
		parent::GuestData($userName, $password, $database, 0);
		if($userName != "admin" || $userName != "root") {
			die("ERROR:管理者権限がありません\n");
			session_destroy();
		}
	}

	//--------------------------
	//データ挿入
	//--------------------------
	function insert_data($table, $cols, $values) {
		$sql = "INSERT INTO ".$table." (".$cols.") VALUES (".$values.")";
		$this->query($sql);
	}
}

	//--------------------------
	//データ更新
	//--------------------------
	function update_data($table, $cols, $values, $target) {
		$date = date("Y-m-d");
		foreach($cols as $col) {
			foreach($values as $val) {
				$set[] = $col."='".$val."'";
			}
		}
		$sql = "UPDATE ".$table." SET ".implode(",", $set)." WHERE ".$target;
		$this->query($sql);
	}

	//--------------------------
	//更新日付を記録
	//--------------------------
	function timestamp($table, $target) {
		$sql = "UPDATE ".$table." SET updated='".date("Y-m-d")."' WHERE ".$target;
		$this->query($sql);
	}
?>
