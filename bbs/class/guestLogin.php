<?php
//==============================
// BBS用 MySQLクラス
//==============================
class GuestLogin {

	//--------------------------
	// 変数の宣言
	//--------------------------
	public $id;
	public $last_login_at;
	public $allow_post;
	public $mysql;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function GuestLogin($p_mysql) {
		$this->mysql = $p_mysql;

		if(isset($_SESSION["guest_id"])) {
			// ログイン済みのとき
			$this->id = $_SESSION["guest_id"];
			$sql = <<<EOT
SELECT * FROM guest_login WHERE id = '$this->id'
EOT;
			$this->set_value($this->mysql->query($sql)->fetch_array());
			$this->update_id("");
		} else if(isset($_COOKIE["ez5000bbsguest"])) {
			// 未ログイン トークンあり
			$token = $_COOKIE["ez5000bbsguest"];
			$sql = <<<EOT
SELECT * FROM guest_login WHERE token = PASSWORD('$token')
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
		// トークン文字列を作成
		$token = $this->create_token();

		// アクセス情報を取得
		$ip = $_SERVER["REMOTE_ADDR"];
		$hostname = $this->mysql->real_escape_string(gethostbyaddr($_SERVER['REMOTE_ADDR']));
		$ua = $this->mysql->real_escape_string($_SERVER["HTTP_USER_AGENT"]);

		// 登録
		$sql = <<<EOT
INSERT INTO guest_login
(token, last_login_at, allow_post, ip, hostname, ua, created_at, updated_at)
VALUES
(PASSWORD('$token'), NOW(), NOW() + INTERVAL 2 DAY, '$ip', '$hostname', '$ua', NOW(), NOW())
EOT;
		$this->mysql->query($sql);

		// 取得
		$sql = "SELECT * FROM guest_login WHERE id = LAST_INSERT_ID()";
		$array = $this->mysql->query($sql)->fetch_array();
		$this->set_value($array);

		// セッション・クッキーを設定
		$_SESSION["guest_id"] = $this->id;
		setcookie("ez5000bbsguest", $token, time() + 2592000);
	}

	//--------------------------
	// ゲストID更新
	//--------------------------
	private function update_id($token) {
		$ip = $_SERVER["REMOTE_ADDR"];
		$token = (strlen($token) == 0) ? "token" : "PASSWORD('$token')";
		$allow_post = (strtotime($this->last_login_at." +7 day") < time()) ? "NOW() + INTERVAL 1 DAY" : "allow_post";
		$hostname = $this->mysql->real_escape_string(gethostbyaddr($_SERVER['REMOTE_ADDR']));
		$ua = $this->mysql->real_escape_string($_SERVER["HTTP_USER_AGENT"]);
		$sql = <<<EOT
UPDATE guest_login
SET token = $token, last_login_at = NOW(), allow_post = $allow_post, ip = '$ip', hostname = '$hostname', ua = '$ua', updated_at = NOW()
WHERE id = '{$this->id}'
EOT;
	}

	//--------------------------
	// ログイン
	//--------------------------
	private function login() {
		// トークン文字列を作成
		$token = $this->create_token();

		// DB更新
		$this->update_id($token);

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
	}
}
?>
