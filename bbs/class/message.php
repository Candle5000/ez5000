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
	public function printMessage($mysql, $boad, $thread) {
		$reply = ($thread->mcount > 999) ? "返信" : "<a href=\"./form.php?mode=reform&id=".$boad->sname."&tid=".$thread->tid."&re=".$this->tmid."\">返信</a>";
?>
<hr class="normal">
<p>
[<?=$this->tmid?>] By <?=htmlspecialchars($this->name)?><br />
<?=$this->textConvert($mysql, $boad, $thread, $this->comment)?><br />
<?=$this->ts?><br />
[<?=$reply?>] [<a href="./form.php?mode=modify&id=<?=$boad->sname?>&tid=<?=$thread->tid?>&tmid=<?=$this->tmid?>">編集</a>]
</p>
<?php
	}

	//--------------------------
	// 本文変換
	//--------------------------
	public function textConvert($mysql, $boad, $thread, $text) {
		$id = $boad->sname;
		$tid = $thread->tid;
		$text = nl2br(htmlspecialchars($text));
		$text = preg_replace("/&gt;&gt;&gt;(([0-9]+)(\.[0-9]+)?)/", ">>>$1", $text);
		$text = preg_replace("/&gt;&gt;([0-9]+)/", ">>$1", $text);

		// URL変換
		$pattern = '/https?:\/\/([0-9a-z\.\-]+)[\w\/:%#\$&\?\(\)~\.=\+\-]+/';
		$replace = '<a href="$0" target="_blank">$1</a>';
		$text = preg_replace($pattern, $replace, $text);

		// アンカーのパターン
		$pattern = "/>>>([0-9]+)\.([0-9]+)/";

		// メッセージアンカー
		while(preg_match($pattern, $text, $match)) {
			$sql = "SELECT 1 FROM `{$id}_m` WHERE `tid`='{$match["1"]}' AND `tmid`='{$match["2"]}'";
			$result = $mysql->query($sql);
			$link_text = "&gt;&gt;&gt;{$match[1]}.{$match[2]}";
			if($result->num_rows) {
				$replace = "<a href=\"/bbs/u/read.php?id=$id&tid={$match[1]}&tmid={$match[2]}\">$link_text</a>";
			} else {
				$replace = $link_text;
			}
			$search = ">>>{$match[1]}.{$match[2]}";
			$text = str_replace($search, $replace, $text);
		}

		// スレッドアンカー
		$pattern = "/>>>([0-9]+)/";
		while(preg_match($pattern, $text, $match)) {
			$sql = "SELECT 1 FROM `{$id}_t` WHERE `tid`='{$match["1"]}'";
			$result = $mysql->query($sql);
			$link_text = "&gt;&gt;&gt;".$match[1];
			if($result->num_rows) {
				$replace = "<a href=\"/bbs/u/read.php?id=$id&tid={$match[1]}\">$link_text</a>";
			} else {
				$replace = $link_text;
			}
			$search = ">>>{$match[1]}";
			$text = str_replace($search, $replace, $text);
		}

		// スレ内アンカー
		$pattern = "/>>([0-9]+)/";
		while(preg_match($pattern, $text, $match)) {
			$sql = "SELECT 1 FROM `{$id}_m` WHERE `tid`='$tid' AND `tmid`='{$match["1"]}'";
			$result = $mysql->query($sql);
			$link_text = "&gt;&gt;".$match[1];
			if($result->num_rows) {
				$replace = "<a href=\"/bbs/u/read.php?id=$id&tid=$tid&tmid={$match[1]}\">$link_text</a>";
			} else {
				$replace = $link_text;
			}
			$search = ">>{$match[1]}";
			$text = str_replace($search, $replace, $text);
		}

		return($text);
	}
}
?>
