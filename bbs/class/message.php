<?php
//==============================
// BBS用 Messageクラス
//==============================
class Message {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $mid;
	public $tmid;
	public $name;
	public $comment;
	public $ts;
	public $ip;
	public $ua;
	public $uid;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function Message($array) {
		$this->mid = $array["mid"];
		$this->tmid = $array["tmid"];
		$this->name = $array["name"];
		$this->comment = $array["comment"];
		$this->ts = $array["ts"];
		$this->ip = $array["ip"];
		$this->ua = $array["ua"];
		$this->uid = $array["uid"];
	}
}
?>
