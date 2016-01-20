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
	public $image;
	public $ts;
	public $ip;
	public $ua;
	public $uid;
	public $deleted;
	public $mysql;
	public $board;
	public $thread;
	public static $imgsize = array(
		'mb' => array('width' => 100, 'size' => 16000),
		'sp' => array('width' => 160, 'size' => 64000),
		'pc' => array('width' => 240, 'size' => 96000)
	);

	//--------------------------
	// コンストラクタ
	//--------------------------
	function Message($array, $mysql_temp, $board_temp, $thread_temp) {
		$this->mid = $array["mid"];
		$this->tmid = $array["tmid"];
		$this->name = $array["name"];
		$this->comment = $array["comment"];
		$this->image = $array["image"];
		$this->ts = $array["ts"];
		$this->ip = $array["ip"];
		$this->ua = $array["ua"];
		$this->uid = $array["uid"];
		$this->deleted = $array["deleted"];
		$this->mysql = $mysql_temp;
		$this->board = $board_temp;
		$this->thread = $thread_temp;
	}

	//--------------------------
	// メッセージ出力
	//--------------------------
	public function printMessage() {
		if(!$this->deleted) {
			$limit = Message::$imgsize;
			$reply = ($this->thread->mcount > 999 || $this->thread->locked) ? "返信" : "<a href=\"./form.php?mode=reform&id={$this->board->sname}&tid={$this->thread->tid}&re={$this->tmid}\">返信</a>";
			$modify = ($this->thread->mcount > 999 || $this->thread->locked) ? "編集" : "<a href=\"./form.php?mode=modify&id={$this->board->sname}&tid={$this->thread->tid}&tmid={$this->tmid}\">編集</a>";
			if($this->image != "") {
				$file_id = "{$this->board->sname}-{$this->thread->tid}-{$this->tmid}-{$this->image}";
				$imageinfo = getimagesize("/var/www/img/bbs/$file_id");
				if($imageinfo[0] > $limit[device_info()]['width'] || $imageinfo[1] > $limit[device_info()]['width'] || filesize("/var/www/img/bbs/$file_id") > $limit[device_info()]['size']) {
					$img = "\n<a href=\"/img/bbs/$file_id\"><img src=\"outimg.php?img=$file_id&size={$limit[device_info()]['width']}\" class=\"smn\" /></a><br />\n";
				} else {
					$img = "\n<a href=\"/img/bbs/$file_id\"><img src=\"/img/bbs/$file_id\" class=\"smn\" /></a><br />\n";
				}
			} else {
				$img = "";
			}
?>
<hr class="normal">
<p>
[<?=$this->tmid?>] By <?=htmlspecialchars($this->name)?><br />
<?=$img?>
<?=$this->textConvert($this->comment)?><br />
<?=$this->ts?><br />
[<?=$reply?>] [<?=$modify?>]
</p>
<?php
		} else {
?>
<hr class="normal">
<p>
[<?=$this->tmid?>] 削除済
</p>
<?php
		}
	}

	//--------------------------
	// 検索メッセージ出力
	//--------------------------
	public function printSearchedMessage() {
		$limit = Message::$imgsize;
		$thread_link = "<a href=\"./read.php?id={$this->board->sname}&tid={$this->thread->tid}\">{$this->thread->title}</a>";
		$reply = ($this->thread->mcount > 999 || $this->thread->locked) ? "返信" : "<a href=\"./form.php?mode=reform&id={$this->board->sname}&tid={$this->thread->tid}&re={$this->tmid}\">返信</a>";
		$modify = ($this->thread->mcount > 999 || $this->thread->locked) ? "編集" : "<a href=\"./form.php?mode=modify&id={$this->board->sname}&tid={$this->thread->tid}&tmid={$this->tmid}\">編集</a>";
		if($this->image != "") {
			$file_id = "{$this->board->sname}-{$this->thread->tid}-{$this->tmid}-{$this->image}";
			$imageinfo = getimagesize("/var/www/img/bbs/$file_id");
			if($imageinfo[0] > $limit[device_info()]['width'] || $imageinfo[1] > $limit[device_info()]['width'] || filesize("/var/www/img/bbs/$file_id") > $limit[device_info()]['size']) {
				$img = "\n<a href=\"/img/bbs/$file_id\"><img src=\"outimg.php?img=$file_id&size={$limit[device_info()]['width']}\" class=\"smn\" /></a><br />\n";
			} else {
				$img = "\n<a href=\"/img/bbs/$file_id\"><img src=\"/img/bbs/$file_id\" class=\"smn\" /></a><br />\n";
			}
		} else {
			$img = "";
		}
?>
<hr class="normal">
<p>
[<?=$thread_link?>]<br />
[<?=$this->tmid?>] By <?=htmlspecialchars($this->name)?><br />
<?=$img?>
<?=$this->textConvert($this->comment)?><br />
<?=$this->ts?><br />
[<?=$reply?>] [<?=$modify?>]
</p>
<?php
	}

	//--------------------------
	// トリップ変換
	//--------------------------
	public static function tripConvert($name) {
		$name = str_replace('＃', '#', $name);
		$array = explode('#', $name, 2);
		$array[0] = str_replace('/', '', $array[0]);
		if(isset($array[1]) && $array[1] != "") $array[1] = strtoupper(substr(hash('md5', mb_convert_encoding($array[1], 'EUC-JP', 'UTF-8')), 0, 8));
		return($array);
	}

	//--------------------------
	// 本文変換
	//--------------------------
	private function textConvert($text) {
		mb_regex_encoding("UTF-8");
		$pattern = '/(https?:\/\/([0-9a-z\.\-]+)[\w\/:%#\$&\?~\.=\+\-]*)|(>>>([0-9]+)\.([0-9]+))|(>>>([0-9]+))|(>>([0-9]+))|([<>&])/';
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
			$sql1 = "SELECT 1 FROM `message` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[4]}' AND `tmid`='{$matches[5]}' AND `deleted`=FALSE";
			$sql2 = "SELECT 1 FROM `thread` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[4]}' AND `pastlog`=FALSE";
			$sql = "SELECT ($sql1) AND ($sql2) AS `bool`";
			if($this->mysql->query($sql)->fetch_object()->bool) {
				return("<a href=\"./read.php?id=".$this->board->sname."&tid={$matches[4]}&tmid={$matches[5]}\">".htmlspecialchars($matches[3])."</a>");
			} else {
				return(htmlspecialchars($matches[3]));
			}
		} else if($matches[6] != "") {
			$sql1 = "SELECT 1 FROM `message` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[7]}' AND `tmid`='1' AND `deleted`=FALSE";
			$sql2 = "SELECT 1 FROM `thread` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[7]}' AND `pastlog`=FALSE";
			$sql = "SELECT ($sql1) AND ($sql2) AS `bool`";
			if($this->mysql->query($sql)->fetch_object()->bool) {
				return("<a href=\"./read.php?id=".$this->board->sname."&tid={$matches[7]}\">".htmlspecialchars($matches[6])."</a>");
			} else {
				return(htmlspecialchars($matches[6]));
			}
		} else if($matches[8] != "") {
			$sql1 = "SELECT 1 FROM `message` WHERE `bid`='{$this->board->bid}' AND `tid`='{$this->thread->tid}' AND `tmid`='{$matches[9]}' AND `deleted`=FALSE";
			$sql2 = "SELECT 1 FROM `thread` WHERE `bid`='{$this->board->bid}' AND `tid`='{$this->thread->tid}' AND `pastlog`=FALSE";
			$sql = "SELECT ($sql1) AND ($sql2) AS `bool`";
			if($this->mysql->query($sql)->fetch_object()->bool) {
				return("<a href=\"./read.php?id={$this->board->sname}&tid={$this->thread->tid}&tmid={$matches[9]}\">".htmlspecialchars($matches[8])."</a>");
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
