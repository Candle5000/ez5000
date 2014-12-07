<?php
//==============================
//MySQLクラス
//==============================
class MySQL {
	
	//--------------------------
	//変数の宣言
	//--------------------------
	public $m_Con;
	public $m_HostName;
	public $m_UserName;
	public $m_Password;
	public $m_Database;
	public $m_Rows;
	
	//--------------------------
	//コンストラクタ
	//--------------------------
	function MySQL($userName, $password, $database) {
		
		//接続設定の読み込み
		$this->m_HostName = "localhost";
		$this->m_UserName = $userName;
		$this->m_Password = $password;
		$this->m_Database = $database;
		
		//データベースへ接続
		$this->m_Con = mysql_connect($this->m_HostName, $this->m_UserName, $this->m_Password);
		if(!$this->m_Con) {
			session_destroy();
			die("データベースへの接続に失敗しました DB:{$this->m_Database} {$this->m_Password}");
		}
		
		//データベースを選択
		if(!mysql_select_db($this->m_Database, $this->m_Con)) {
			die("データベースの選択に失敗しました");
		}
		//$sql = "SET NAMES utf8";
		//$this->query($sql);
		//mysql_set_charset("utf8");
	}
	
	//--------------------------
	//SQLクエリの処理
	//--------------------------
	function query($sql) {
		$this->m_Rows = mysql_query($sql, $this->m_Con);
		if(!$this->m_Rows) {
			die("クエリ処理に失敗しました<br /><b>{$sql}</b><br />" . mysql_errno() . ":" . mysql_error());
		}
		return($this->m_Rows);
	}
	
	//--------------------------
	//検索結果をfetch
	//--------------------------
	function fetch() {
		return(mysql_fetch_array($this->m_Rows));
	}
	
	//--------------------------
	//変更された行の数を取得
	//--------------------------
	function affected_rows() {
		return(mysql_affected_rows());
	}
	
	//--------------------------
	//結果の列数を取得
	//--------------------------
	function cols() {
		return(mysql_num_fields($this->m_Rows));
	}
	
	//--------------------------
	//結果の行数を取得
	//--------------------------
	function rows() {
		return(mysql_num_rows($this->m_Rows));
	}
	
	//--------------------------
	//検索結果のリソースを解放
	//--------------------------
	function free() {
		mysql_free_result($this->m_Rows);
	}
	
	//--------------------------
	//MySQLをクローズ
	//--------------------------
	function close() {
		mysql_close($this->m_Con);
	}
	
	//--------------------------
	//エラーメッセージ
	//--------------------------
	function errors() {
		return(mysql_errno() . ": " . mysql_error());
	}
	
	//--------------------------
	//エラーナンバー
	//--------------------------
	function errorno() {
		return(mysql_errno());
	}
	
	//--------------------------
	//権限確認
	//--------------------------
	function is_admin() {
		return ($this->m_UserName == "admin" || $this->m_UserName == "root");
	}
}

?>
