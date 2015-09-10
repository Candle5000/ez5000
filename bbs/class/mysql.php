<?php
//==============================
// BBS用 MySQLクラス
//==============================
class MySQL extends mysqli {

	//--------------------------
	// 変数の宣言
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
		$result = parent::query($sql);
		return($result);
	}

	//--------------------------
	// エラーメッセージ
	//--------------------------
	public function errors() {
		return($this->errno.":".$this->error);
	}
}
?>
