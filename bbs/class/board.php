<?php
//==============================
// BBS用 Boardクラス
//==============================
class Board {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $bid;
	public $name;
	public $title;
	public $access_cnt;
	public $access_cnt_archive;
	public $allow_readpass;
	public $allow_writepass;
	public $default_name;
	public $name_max;
	public $subject_max;
	public $comment_max;
	public $thpost_limit;
	public $repost_limit;
	public $next_tid;
	public $next_mid;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function Board($array) {
		$this->bid = $array["bid"];
		$this->name = $array["name"];
		$this->title = $array["title"];
		$this->access_cnt = $array["access_cnt"];
		$this->access_cnt_archive = $array["access_cnt_archive"];
		$this->allow_readpass = $array["allow_readpass"];
		$this->allow_writepass = $array["allow_writepass"];
		$this->default_name = $array["default_name"];
		$this->name_max = $array["name_max"];
		$this->subject_max = $array["subject_max"];
		$this->comment_max = $array["comment_max"];
		$this->thpost_limit = $array["thpost_limit"];
		$this->repost_limit = $array["repost_limit"];
		$this->next_tid = $array["next_tid"];
		$this->next_mid = $array["next_mid"];
	}
}
?>
