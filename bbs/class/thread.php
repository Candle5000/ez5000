<?php
//==============================
// BBS用 Threadクラス
//==============================
class Thread {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $tid;
	public $title;
	public $tindex;
	public $readpass;
	public $writepass;
	public $acount;
	public $mcount;
	public $locked;
	public $top;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function Thread($array) {
		$this->tid = $array["tid"];
		$this->title = $array["title"];
		$this->tindex = $array["tindex"];
		$this->acount = $array["acount"];
		$this->mcount = $array["mcount"];
		$this->locked = $array["locked"];
		$this->top = $array["top"];
	}
}
?>
