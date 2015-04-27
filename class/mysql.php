<?php
//==============================
// MySQLクラス
//==============================
class MySQL extends mysqli {

	//--------------------------
	// 変数の宣言
	//--------------------------
	private $m_HostName;
	private $m_UserName;
	private $m_Password;
	private $m_Database;
	public $m_Con;
	private $m_Rows;

	//--------------------------
	// コンストラクタ
	//--------------------------
	function MySQL($userName, $password, $database) {

		//接続設定の読み込み
		$this->m_HostName = "localhost";
		$this->m_UserName = $userName;
		$this->m_Password = $password;
		$this->m_Database = $database;

		//データベースへ接続
		$this->m_Con = mysqli_connect($this->m_HostName, $this->m_UserName, $this->m_Password, $this->m_Database);
	}

	//--------------------------
	// SQLクエリの処理
	//--------------------------
	function query($sql) {
		$this->m_Rows = mysqli_query($this->m_Con, $sql);
		if(!$this->m_Rows) {
			die("クエリ処理に失敗しました<br />".$this->errors());
		}
		return($this->m_Rows);
	}

	//--------------------------
	//検索結果をfetch
	//--------------------------
	function fetch() {
		return(mysqli_fetch_array($this->m_Rows));
	}

	//--------------------------
	//変更された行の数を取得
	//--------------------------
	function affected_rows() {
		return(mysqli_affected_rows());
	}

	//--------------------------
	//結果の列数を取得
	//--------------------------
	function cols() {
		return(mysqli_num_fields($this->m_Rows));
	}

	//--------------------------
	//結果の行数を取得
	//--------------------------
	function rows() {
		return(mysqli_num_rows($this->m_Rows));
	}

	//--------------------------
	//検索結果のリソースを解放
	//--------------------------
	function free() {
		mysqli_free_result($this->m_Rows);
	}

	//--------------------------
	//MySQLをクローズ
	//--------------------------
	function close() {
		mysqli_close($this->m_Con);
	}

	//--------------------------
	//エラーメッセージ
	//--------------------------
	function errors() {
		return(mysqli_errno().":".mysqli_error());
	}

	//--------------------------
	//エラーナンバー
	//--------------------------
	function errorno() {
		return(mysqli_errno());
	}

	//--------------------------
	//権限確認
	//--------------------------
	function is_admin() {
		return ($this->m_UserName == "admin" || $this->m_UserName == "root");
	}
}

?>
