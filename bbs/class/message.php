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
	public $mysql;
	public $boad;
	public $thread;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function Message($array, $mysql_temp, $boad_temp, $thread_temp) {
		$this->mid = $array["mid"];
		$this->tmid = $array["tmid"];
		$this->name = $array["name"];
		$this->comment = $array["comment"];
		$this->ts = $array["ts"];
		$this->ip = $array["ip"];
		$this->ua = $array["ua"];
		$this->uid = $array["uid"];
		$this->mysql = $mysql_temp;
		$this->boad = $boad_temp;
		$this->thread = $thread_temp;
	}

	//--------------------------
	// メッセージ出力
	//--------------------------
	public function printMessage() {
		$reply = ($this->thread->mcount > 999) ? "返信" : "<a href=\"./form.php?mode=reform&id=".$this->boad->sname."&tid=".$this->thread->tid."&re=".$this->tmid."\">返信</a>";
?>
<hr class="normal">
<p>
[<?=$this->tmid?>] By <?=htmlspecialchars($this->name)?><br />
<?=$this->textConvert($this->comment)?><br />
<?=$this->ts?><br />
[<?=$reply?>] [<a href="./form.php?mode=modify&id=<?=$this->boad->sname?>&tid=<?=$this->thread->tid?>&tmid=<?=$this->tmid?>">編集</a>]
</p>
<?php
	}

	//--------------------------
	// 本文変換
	//--------------------------
	private function textConvert($text) {
		mb_regex_encoding("UTF-8");
		$pattern = '/(https?:\/\/([0-9a-z\.\-]+)[\w\/:%#\$&\?~\.=\+\-]+)|(>>>([0-9]+)\.([0-9]+))|(>>>([0-9]+))|(>>([0-9]+))|([<>&])/';
		$text = preg_replace_callback($pattern, array($this, 'textReplace'), $text);
		return(nl2br($text));
	}

	//--------------------------
	// 検索置換
	//--------------------------
	private function textReplace($matches) {
		if($matches[1] != "") {
			return("<a href=\"{$matches[1]}\" target=\"blank\">".htmlspecialchars($matches[2])."</a>");
		} else if($matches[3] != "") {
			$sql = "SELECT 1 FROM `{$this->boad->sname}_m` WHERE `tid`='{$matches[4]}' AND `tmid`='{$matches[5]}'";
			if($this->mysql->query($sql)->num_rows) {
				return("<a href=\"./read.php?id=".$this->boad->sname."&tid={$matches[4]}&tmid={$matches[5]}\">".htmlspecialchars($matches[3])."</a>");
			} else {
				return(htmlspecialchars($matches[3]));
			}
		} else if($matches[6] != "") {
			$sql = "SELECT 1 FROM `{$this->boad->sname}_t` WHERE `tid`='{$matches[7]}'";
			if($this->mysql->query($sql)->num_rows) {
				return("<a href=\"./read.php?id=".$this->boad->sname."&tid={$matches[7]}\">".htmlspecialchars($matches[6])."</a>");
			} else {
				return(htmlspecialchars($matches[6]));
			}
		} else if($matches[8] != "") {
			$sql = "SELECT 1 FROM `{$this->boad->sname}_m` WHERE `tid`='{$this->thread->tid}' AND `tmid`='{$matches[9]}'";
			if($this->mysql->query($sql)->num_rows) {
				return("<a href=\"./read.php?id={$this->boad->sname}&tid={$this->thread->tid}&tmid={$matches[9]}\">".htmlspecialchars($matches[8])."</a>");
			} else {
				return(htmlspecialchars($matches[8]));
			}
		} else if($matches[10] != "") {
			return(htmlspecialchars($matches[10]));
		} else {
			return("");
		}
	}
}
?>
