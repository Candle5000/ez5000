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
	// エラーメッセージ
	//--------------------------
	public function errors() {
		return($this->errno.":".$this->error);
	}
}
?>
