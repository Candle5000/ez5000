<?php
//==============================
// BBS用 匿名IDクラス
//==============================
class AnonymousId {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $guest;
	public $user_id;
	public $display_id;
	private $mysql;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function AnonymousId($is_guest, $user_id, $p_mysql) {

		$this->guest = $is_guest;
		$this->user_id = $user_id;
		$this->mysql = $p_mysql;

		$sql_guest = $this->guest ? 'TRUE' : 'FALSE';
		$sql_user_id = is_numeric($this->user_id) ? $this->user_id : 0;

		// 匿名IDを取得
		$sql = "SELECT * FROM anonymous_id WHERE guest = $sql_guest AND user_id = $sql_user_id";
		$result = $this->mysql->query($sql);
		$array = $result->fetch_array();

		if($result->num_rows == 0) {
			// IDが存在しない場合、新規発行する
			$this->insert_id();
		} else if(empty($array['expiration_date'])) {
			// 期限が設定されていない場合、再発行する
			$this->update_id();
		} else {
			// 有効期限を確認する
			$expiration_date = new DateTime($array['expiration_date']);
			$now = new DateTime();
			if($expiration_date < $now) {
				// 期限切れの場合、再発行する
				$this->update_id();
			} else {
				// 有効な場合、変数にセットする
				$this->display_id = $array['display_id'];
			}
		}
	}

	//--------------------------
	// 匿名ID文字列を作成
	//--------------------------
	private function create_id() {
		$source = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'), array('!', '#', '$', '%', '&', '-', '=', '+', '*', '/'));
		$date = $this->get_next_tuesday();
		do {
			$id = "";
			for($i = 0; $i < 8; $i++) {
				$id .= $source[rand(0, count($source) - 1)];
			}
			$sql = "SELECT display_id FROM anonymous_id WHERE expiration_date = '$date' AND display_id = '$id'";
			$result = $this->mysql->query($sql);
		} while($result->num_rows > 0);

		return($id);
	}

	//--------------------------
	// 次の火曜日の0時0分を取得
	//--------------------------
	private function get_next_tuesday() {
		// 現在日付と曜日を求める
		$date = new DateTime();
		$week = $date->format('w');

		// 次の火曜日までの日数を計算
		$interval = ((int)$week < 2) ? 2 - (int)$week : 9 - (int)$week;

		return($date->modify("+$interval day")->format('Y-m-d').' 00:00:00');
	}

	//--------------------------
	// IDを更新
	//--------------------------
	private function update_id() {
		$this->display_id = $this->create_id();
		$date = $this->get_next_tuesday();
		$sql_guest = $this->guest ? 'TRUE' : 'FALSE';
		$sql = "UPDATE anonymous_id SET display_id = '{$this->display_id}', expiration_date = '$date'";
		$sql .= " WHERE guest = $sql_guest AND user_id = {$this->user_id}";
		$this->mysql->query($sql);
	}

	//--------------------------
	// IDを登録
	//--------------------------
	private function insert_id() {
		$this->display_id = $this->create_id();
		$date = $this->get_next_tuesday();
		$sql_guest = $this->guest ? 'TRUE' : 'FALSE';
		$sql = "INSERT anonymous_id (guest, user_id, display_id, expiration_date)";
		$sql .= " VALUES ($sql_guest, {$this->user_id}, '{$this->display_id}', '$date')";
		$this->mysql->query($sql);
	}
}
?>
