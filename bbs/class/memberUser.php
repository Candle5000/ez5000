<?php
//==============================
// BBS用 MemberUserクラス
//==============================
class MemberUser {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $id;
	public $last_login_at;
	public $banned;
	public $ip;
	public $hostname;
	public $ua;
	public $uid;
	private $mysql;
	private $hostname_sql;
	private $ua_sql;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function MemberUser($p_mysql, $user_name, $password, $uid = "") {

		$this->mysql = $p_mysql;
		$this->hostname_sql = $this->mysql->real_escape_string($this->hostname);
		$this->ua_sql = $this->mysql->real_escape_string($this->ua);

		// アクセス情報を取得
		$this->ip = $_SERVER["REMOTE_ADDR"];
		$this->hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$this->ua = $_SERVER["HTTP_USER_AGENT"];

		// クッキー無効の場合処理しない
		setcookie("cookiecheck", true, time() + 864000);
		if(!isset($_COOKIE["cookiecheck"])) return;

		// フィーチャーフォンの場合
		if(device_info() == "mb") {
			// ユーザー情報取得
			$sql = <<<EOT
SELECT * FROM member_user WHERE uid = '$uid'
EOT;
			$result = $this->mysql->query($sql);

			if($result->num_rows) {
				// ユーザー登録済みのとき
				$this->set_value($result->fetch_array());
				$this->update_id();
			} else {
				// ユーザー未登録のとき
				$this->uid = $uid;
				$this->create_id();
			}
		} else {
			// PC/スマホの場合
			// TODO 処理実装
		}
	}

	//--------------------------
	// ユーザーID発行
	//--------------------------
	private function create_id() {
		// トークン文字列を作成
		// TODO PC/スマホの場合のみ使用？
		$token = "";

		// 登録
		$sql = <<<EOT
INSERT INTO member_user
(token, last_login_at, ip, hostname, ua, uid, created_at, updated_at)
VALUES
(PASSWORD('$token'), NOW(), '{$this->ip}', '{$this->hostname_sql}', '{$this->ua_sql}', '{$this->uid}', NOW(), NOW())
EOT;
		$this->mysql->query($sql);

		// 取得
		$sql = "SELECT * FROM member_user WHERE id = LAST_INSERT_ID()";
		$array = $this->mysql->query($sql)->fetch_array();
		$this->set_value($array);
	}

	//--------------------------
	// ユーザーID更新
	//--------------------------
	private function update_id() {
		$sql = <<<EOT
UPDATE member_user
SET last_login_at = NOW(), ip = '{$this->ip}', hostname = '{$this->hostname_sql}', ua = '{$this->ua_sql}', updated_at = NOW()
WHERE id = '{$this->id}'
EOT;
		$this->mysql->query($sql);
	}

	//--------------------------
	// 配列から変数に代入
	//--------------------------
	private function set_value($array) {
		$this->id = $array["id"];
		$this->last_login_at = $array["last_login_at"];
		$this->banned = $array["banned"];
	}
}
?>
