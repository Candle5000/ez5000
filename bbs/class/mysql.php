<?php
//==============================
// BBS用 MySQLクラス
//==============================
class MySQL extends mysqli {

	//--------------------------
	// 変数
	//--------------------------

	//--------------------------
	// コンストラクタ
	//--------------------------
	function MySQL($userName, $password, $database) {
		parent::__construct("localhost", $userName, $password, $database);
	}

	//--------------------------
	// SQLクエリの処理
	//--------------------------
	public function query($sql) {
		$result = parent::query($query);
		if(isset($result->num_rows)) {
			// SELECTの場合、結果を配列で返す
			return($result->fetch_array());
		} else {
			// SELECT以外の場合、成功したかをbooleanで返す
			return($result);
		}
	}

	//--------------------------
	// エラーメッセージ
	//--------------------------
	public function errors() {
		return($this->errno.":".$this->error);
	}
}
?>
