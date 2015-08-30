<?php
//==============================
//AdminDataクラス
//==============================
class AdminData extends GuestData {
	
	//--------------------------
	// 変数の宣言
	//--------------------------
	public $is_admin;

	//--------------------------
	//コンストラクタ
	//--------------------------
	function AdminData($userName, $password, $database) {
		parent::GuestData($userName, $password, $database);
		$this->is_admin = ($userName == "admin" || $userName == "subb");
	}

	//--------------------------
	//データ挿入
	//--------------------------
	function insert_data($table, $cols, $values) {
		if(!$this->is_admin) return(-1);
		$sql = "INSERT INTO ".$table." (".$cols.") VALUES (".$values.")";
		$this->query($sql);
	}

	//--------------------------
	//データ更新
	//--------------------------
	function update_data($table, $cols, $values, $target) {
		if(!$this->is_admin) return(-1);
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
		if(!$this->is_admin) return(-1);
		$date = date("Y-m-d");
		$sql = "UPDATE ".$table." SET updated='".$date."' WHERE ".$target;
		$this->query($sql);
	}
}
?>
