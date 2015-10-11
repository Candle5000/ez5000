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

	//--------------------------
	// メッセージ出力
	//--------------------------
	public function printMessage() {
?>
<hr class="normal">
<p>
[<?=$this->tmid?>] By <?=htmlspecialchars($this->name)?><br />
<?=nl2br(htmlspecialchars($this->comment))?><br />
<?=$this->ts?><br />
[<a href="./form.php?mode=reform&id=<?=$boad->sname?>&tid=<?=$tid?>&re=<?=$this->tmid?>">返信</a>] [<a href="./form.php?mode=modify&id=<?=$boad->sname?>&tid=<?=$tid?>&tmid=<?=$this->tmid?>">編集</a>]
</p>
<?php
	}
}
?>
