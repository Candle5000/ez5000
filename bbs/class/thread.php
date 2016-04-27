<?php
//==============================
// BBS用 Threadクラス
//==============================
class Thread {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $tid;
	public $subject;
	public $tindex;
	public $readpass;
	public $writepass;
	public $access_cnt;
	public $message_cnt;
	public $update_ts;
	public $locked;
	public $top;
	public $next_tmid;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function Thread($array) {
		$this->tid = $array["tid"];
		$this->subject = $array["subject"];
		$this->tindex = $array["tindex"];
		$this->access_cnt = $array["access_cnt"];
		$this->message_cnt = $array["message_cnt"];
		$this->update_ts = $array["update_ts"];
		$this->locked = $array["locked"];
		$this->top = $array["top"];
		$this->next_tmid = $array["next_tmid"];
	}
}
?>
