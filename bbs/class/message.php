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
	public $post_ts;
	public $update_ts;
	public $update_cnt;
	public $ip;
	public $hostname;
	public $ua;
	public $uid;
	public $user_id;
	public $guest_id;
	public $display_id;
	public $mysql;
	public $board;
	public $thread;
	public $mode;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function Message($array, $mysql_temp, $board_temp, $thread_temp) {
		$this->mid = $array["mid"];
		$this->tmid = $array["tmid"];
		$this->name = $array["name"];
		$this->comment = $array["comment"];
		$this->image = $array["image"];
		$this->post_ts = $array["post_ts"];
		$this->update_ts = $array["update_ts"];
		$this->update_cnt = $array["update_cnt"];
		$this->ip = isset($array["ip"]) ? $array["ip"] : "";
		$this->hostname = isset($array["hostname"]) ? $array["hostname"] : "";
		$this->ua = isset($array["ua"]) ? $array["ua"] : "";
		$this->uid = isset($array["uid"]) ? $array["uid"] : "";
		$this->user_id = isset($array["user_id"]) ? $array["user_id"] : "";
		$this->guest_id = isset($array["guest_id"]) ? $array["guest_id"] : "";
		$this->display_id = isset($array["display_id"]) ? $array["display_id"] : "";
		$this->mode = 0;
		$this->mysql = $mysql_temp;
		$this->board = $board_temp;
		$this->thread = $thread_temp;
	}

	//--------------------------
	// メッセージ出力
	//--------------------------
	public function printMessage() {
		$thread_link = ($this->mode == 1) ? "[<a href=\"./read.php?id={$this->board->name}&tid={$this->thread->tid}\">{$this->thread->subject}</a>]<br />" : "";
		$reply = ($this->thread->message_cnt > 999 || $this->thread->locked || $this->mode == 2) ? "返信" : "<a href=\"./form.php?mode=reform&id={$this->board->name}&tid={$this->thread->tid}&re={$this->tmid}\">返信</a>";
		$modify = ($this->thread->message_cnt > 999 || $this->thread->locked || $this->mode == 2) ? "編集" : "<a href=\"./form.php?mode=modify&id={$this->board->name}&tid={$this->thread->tid}&tmid={$this->tmid}\">編集</a>";
		$report = ($this->mode == 1 || $this->mode == 2) ? "" : "[<a href=\"./report.php?id={$this->board->name}&tid={$this->thread->tid}&tmid={$this->tmid}\">報告</a>]";
		$updinfo = ($this->update_cnt > 0) ? "最終更新:{$this->update_ts}<br />" : "";
		if($this->image != "") {
			$file_id = "{$this->board->name}-{$this->thread->tid}-{$this->tmid}-{$this->image}";
			if(file_exists("/var/www/img/bbs/$file_id")) {
				$imageinfo = @getimagesize("/var/www/img/bbs/$file_id");
				$imagesize = ceil(filesize("/var/www/img/bbs/$file_id") / 1024);
				if(!$imageinfo || !$imageinfo[0]) {
					$img = "<span class=\"error block cnt\">[画像の読み込みに失敗しました]</span>";
				} else if(file_exists("/var/www/img/bbs/$file_id.png")) {
					$img = "<a href=\"/img/bbs/$file_id\"><img src=\"/img/bbs/$file_id.png\" class=\"smn\" /><span class=\"block cnt\">[$imagesize KB]</span></a>\n";
				} else {
					$img = "<a href=\"/img/bbs/$file_id\"><span class=\"block cnt\">[サムネイルがありません]<br />\n[$imagesize KB]</span></a>\n";
				}
			} else {
				$img = "<span class=\"error block cnt\">[画像が存在しません]</span>\n";
			}
		} else {
			$img = "";
		}
?>
<p>
<?=$thread_link?>
[<?=$this->tmid?>] By <?=htmlspecialchars($this->name)?> ID:<?=$this->display_id?><br />
<?=$img?>
<?=$this->textConvert($this->comment)?><br />
<?=$this->post_ts?><br />
<?=$updinfo?>
[<?=$reply?>] [<?=$modify?>] <?=$report?>
</p>
<?php
	}

	//--------------------------
	// 検索メッセージ出力
	//--------------------------
	public function printSearchedMessage() {
		$this->mode = 1;
		$this->printMessage();
	}

	//--------------------------
	// 過去ログメッセージ出力
	//--------------------------
	public function printArchiveMessage() {
		$this->mode = 2;
		$this->printMessage();
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
			$sql1 = "SELECT 1 FROM `message` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[4]}' AND `tmid`='{$matches[5]}'";
			$sql2 = "SELECT 1 FROM `thread` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[4]}'";
			$sql = "SELECT ($sql1) AND ($sql2) AS `bool`";
			if($this->mode != 2 && $this->mysql->query($sql)->fetch_object()->bool) {
				return("<a href=\"./read.php?id=".$this->board->name."&tid={$matches[4]}&tmid={$matches[5]}\">".htmlspecialchars($matches[3])."</a>");
			} else {
				return(htmlspecialchars($matches[3]));
			}
		} else if($matches[6] != "") {
			$sql1 = "SELECT 1 FROM `message` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[7]}' AND `tmid`='1'";
			$sql2 = "SELECT 1 FROM `thread` WHERE `bid`='{$this->board->bid}' AND `tid`='{$matches[7]}'";
			$sql = "SELECT ($sql1) AND ($sql2) AS `bool`";
			if($this->mode != 2 && $this->mysql->query($sql)->fetch_object()->bool) {
				return("<a href=\"./read.php?id=".$this->board->name."&tid={$matches[7]}\">".htmlspecialchars($matches[6])."</a>");
			} else {
				return(htmlspecialchars($matches[6]));
			}
		} else if($matches[8] != "") {
			$sql1 = "SELECT 1 FROM `message` WHERE `bid`='{$this->board->bid}' AND `tid`='{$this->thread->tid}' AND `tmid`='{$matches[9]}'";
			$sql2 = "SELECT 1 FROM `thread` WHERE `bid`='{$this->board->bid}' AND `tid`='{$this->thread->tid}'";
			$sql = "SELECT ($sql1) AND ($sql2) AS `bool`";
			if($this->mode != 2 && $this->mysql->query($sql)->fetch_object()->bool) {
				return("<a href=\"./read.php?id={$this->board->name}&tid={$this->thread->tid}&tmid={$matches[9]}\">".htmlspecialchars($matches[8])."</a>");
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
