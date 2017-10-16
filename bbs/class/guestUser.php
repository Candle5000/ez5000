<?php
//==============================
// BBS用 MySQLクラス
//==============================
class GuestUser {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $id;
	public $last_login_at;
	public $allow_post;
	public $banned;
	public $ip;
	public $hostname;
	public $ua;
	private $mysql;
	private $hostname_sql;
	private $ua_sql;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function GuestUser($p_mysql) {

		// アクセス情報を取得
		$this->ip = $_SERVER["REMOTE_ADDR"];
		$this->hostname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		$this->ua = $_SERVER["HTTP_USER_AGENT"];

		// フィーチャーフォンの場合処理しない
		if(device_info() == "mb") return;

		// クッキー無効の場合処理しない
		setcookie("cookiecheck", true, time() + 864000);
		if(!isset($_COOKIE["cookiecheck"])) return;

		$this->mysql = $p_mysql;
		$this->hostname_sql = $this->mysql->real_escape_string($this->hostname);
		$this->ua_sql = $this->mysql->real_escape_string($this->ua);

		if(isset($_SESSION["guest_id"])) {
			// ログイン済みのとき
			$this->id = $_SESSION["guest_id"];
			$sql = <<<EOT
SELECT * FROM guest_user WHERE id = '$this->id'
EOT;
			$this->set_value($this->mysql->query($sql)->fetch_array());
			$this->update_id("");
		} else if(isset($_COOKIE["ez5000bbsguest"])) {
			// 未ログイン トークンあり
			$token = $_COOKIE["ez5000bbsguest"];
			$sql = <<<EOT
SELECT * FROM guest_user WHERE token = PASSWORD('$token')
EOT;
			$result = $this->mysql->query($sql);
			if($result->num_rows == 1) {
				// 自動ログイン
				$this->set_value($result->fetch_array());
				$this->login();
			} else {
				// ゲストID再発行
				$this->create_id();
			}
		} else if(isset($_COOKIE["cookiecheck"])) {
			// ゲストID新規発行
			$this->create_id();
		}
	}

	//--------------------------
	// ゲストID発行
	//--------------------------
	private function create_id() {
		// 定数を読み込み
		$allow_post_interval = Constants::NEW_GUEST_POST_ALLOW;

		// トークン文字列を作成
		$token = $this->create_token();

		// 登録
		$sql = <<<EOT
INSERT INTO guest_user
(token, last_login_at, allow_post, ip, hostname, ua, created_at, updated_at)
VALUES
(PASSWORD('$token'), NOW(), NOW() + INTERVAL $allow_post_interval, '{$this->ip}', '{$this->hostname_sql}', '{$this->ua_sql}', NOW(), NOW())
EOT;
		$this->mysql->query($sql);

		// 取得
		$sql = "SELECT * FROM guest_user WHERE id = LAST_INSERT_ID()";
		$array = $this->mysql->query($sql)->fetch_array();
		$this->set_value($array);

		// ログイン履歴を登録
		$this->insert_history();

		// セッション・クッキーを設定
		$_SESSION["guest_id"] = $this->id;
		setcookie("ez5000bbsguest", $token, time() + 2592000);
	}

	//--------------------------
	// ゲストID更新
	//--------------------------
	private function update_id($token) {
		// 定数を読み込み
		$allow_post_interval = Constants::OLD_GUEST_POST_ALLOW;

		$token = (strlen($token) == 0) ? "token" : "PASSWORD('$token')";
		$allow_post = (strtotime($this->last_login_at." +7 day") < time()) ? "NOW() + INTERVAL $allow_post_interval" : "allow_post";
		$sql = <<<EOT
UPDATE guest_user
SET token = $token, last_login_at = NOW(), allow_post = $allow_post, ip = '{$this->ip}', hostname = '{$this->hostname_sql}', ua = '{$this->ua_sql}', updated_at = NOW()
WHERE id = '{$this->id}'
EOT;
		$this->mysql->query($sql);
	}

	//--------------------------
	// ログイン
	//--------------------------
	private function login() {
		// トークン文字列を作成
		$token = $this->create_token();

		// DB更新
		$this->update_id($token);

		// ログイン履歴を登録
		$this->insert_history();

		// セッション・クッキーを設定
		$_SESSION["guest_id"] = $this->id;
		setcookie("ez5000bbsguest", $token, time() + 2592000);
	}

	//--------------------------
	// トークン文字列を作成
	//--------------------------
	private function create_token() {
		$source = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
		$token = "";
		for($i = 0; $i < 16; $i++) {
			$token .= $source[rand(0, count($source) - 1)];
		}

		return($token);
	}

	//--------------------------
	// 配列から変数に代入
	//--------------------------
	private function set_value($array) {
		$this->id = $array["id"];
		$this->last_login_at = $array["last_login_at"];
		$this->allow_post = $array["allow_post"];
		$this->banned = $array["banned"];
	}

	//--------------------------
	// ログイン履歴を登録
	//--------------------------
	private function insert_history() {
		$sql = <<<EOT
INSERT INTO guest_login_history
(guest_user_id, login_at, ip, hostname, ua)
VALUES
('{$this->id}', NOW(), '{$this->ip}', '{$this->hostname_sql}', '{$this->ua_sql}')
EOT;
		$this->mysql->query($sql);
	}
}
?>
