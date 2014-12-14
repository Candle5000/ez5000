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
		if($userName != "admin") {
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

	//--------------------------
	//データ更新
	//--------------------------
	function update_data($table, $cols, $values, $target) {
		foreach($cols as $key=>$col) {
			$val = $values[$key];
			$set[] = $col."='".$val."'";
		}
		$sql = "UPDATE ".$table." SET ".implode(",", $set)." WHERE ".$target;
		$this->query($sql);
	}

	//--------------------------
	//更新日付を記録
	//--------------------------
	function timestamp($table, $target) {
		$date = date("Y-m-d");
		$sql = "UPDATE ".$table." SET updated='".$date."' WHERE ".$target;
		$this->query($sql);
	}
}
?>
