<?php
//==============================
// StatusDataクラス
//==============================
class StatusData extends MySQL {
	
	//--------------------------
	// 変数の宣言
	//--------------------------
	public $error;
	public $table;
	public $lv;
	public $hp;
	public $sp;
	public $str;
	public $vit;
	public $dex;
	public $agi;
	public $wis;
	public $wil;
	public $sql;
	
	//--------------------------
	// コンストラクタ
	//--------------------------
	function StatusData($userName, $password, $database, $r_table) {
		parent::MySQL($userName, $password, $database);
		$this->table = $r_table;
		$this->reset();
	}
	
	//--------------------------
	// 入力の読み込み
	//--------------------------
	function read_data($r_lv, $r_hp, $r_sp, $r_str, $r_vit, $r_dex, $r_agi, $r_wis, $r_wil) {
		$this->reset();
		$this->lv = htmlspecialchars($r_lv, ENT_QUOTES);
		$this->hp = $this->num_check(htmlspecialchars($r_hp, ENT_QUOTES));
		$this->sp = $this->num_check(htmlspecialchars($r_sp, ENT_QUOTES));
		$this->str = $this->num_check(htmlspecialchars($r_str, ENT_QUOTES));
		$this->vit = $this->num_check(htmlspecialchars($r_vit, ENT_QUOTES));
		$this->dex = $this->num_check(htmlspecialchars($r_dex, ENT_QUOTES));
		$this->agi = $this->num_check(htmlspecialchars($r_agi, ENT_QUOTES));
		$this->wis = $this->num_check(htmlspecialchars($r_wis, ENT_QUOTES));
		$this->wil = $this->num_check(htmlspecialchars($r_wil, ENT_QUOTES));
		$this->error = $this->lv_check() + $this->admin_check();
	}
	
	//----------------------------------------
	// Lv入力エラーチェック
	//----------------------------------------
	function lv_check() {
		if(preg_match("/[0-9]{1,2}/", $this->lv)) {
			return(0);
		} else {
			return(1);
		}
	}
	
	//----------------------------------------
	// 権限エラーチェック
	//----------------------------------------
	function admin_check() {
		if($this->m_UserName == "admin") {
			return(0);
		} else {
			return(2);
		}
	}
	
	//----------------------------------------
	// エラー出力
	//----------------------------------------
	function print_error() {
		if(($this->error /  1) % 2) {echo "ERROR: Lvが不適切です<br />\n";}
		if(($this->error /  2) % 2) {echo "ERROR: UserNameが不適切です<br />\n";}
	}
	
	//----------------------------------------
	// 数値入力チェック
	//----------------------------------------
	function num_check($status) {
		if(preg_match("/[0-9]{1,4}/", $status)) {
			return($status);
		} else {
			return(0);
		}
	}
	
	//--------------------------
	// 全件表示
	//--------------------------
	function select_all() {
		$this->sql = "SELECT * FROM $this->table";
		$this->query($this->sql);
	}
	
	//--------------------------
	// Lv検索
	//--------------------------
	function search_lv($s_lv) {
		$this->sql = "SELECT * FROM $this->table WHERE lv='$s_lv'";
		$this->query($this->sql);
	}
	
	//--------------------------
	// lvの存在を確認
	//--------------------------
	function is_added($data) {
		$this->sql = "SELECT lv FROM $this->table WHERE lv='$data'";
		$this->query($this->sql);
		$result = $this->rows();
		$this->free();
		return($result);
	}
	
	//--------------------------
	// 更新日付を記録
	//--------------------------
	function update() {
		$date = date("Y-m-d");
		$this->sql = "UPDATE class SET updated='$date' WHERE nameS='$this->table'";
		$this->query($this->sql);
	}
	
	//--------------------------
	// デフォルトデータを指定範囲で追加
	//--------------------------
	function add_empty($start, $end) {
		if(!preg_match("/[0-9]{1,2}/", $start) || !preg_match("/[0-9]{1,2}/", $end) || ($start > $end)) {
			$this->error = 1;
		}
		if($this->error == 0) {
			for($i = $start; $i <= $end; $i++) {
				if(!$this->is_added($i)) {
					$this->sql = "INSERT INTO $this->table (lv) VALUES($i)";
					$this->query($this->sql);
				}
			}
			$this->update();
		}
	}
	
	//--------------------------
	// データ追加
	//--------------------------
	function add_data() {
		if($this->is_added($this->lv)) {
			$this->error = 1;
		}
		if($this->error == 0) {
			$this->sql = "INSERT INTO $this->table VALUES($this->lv,'$this->hp','$this->sp','$this->str','$this->vit','$this->dex','$this->agi','$this->wis','$this->wil')";
			$this->query($this->sql);
			$this->update();
		}
	}
	
	//--------------------------
	// データ変更
	//--------------------------
	function update_data() {
		if(!$this->is_added($this->lv)) {
			$this->error = 1;
		}
		if($this->error == 0) {
			$this->sql = "UPDATE $this->table SET hp='$this->hp',sp='$this->sp',str='$this->str',vit='$this->vit',dex='$this->dex',agi='$this->agi',wis='$this->wis',wil='$this->wil' WHERE lv='$this->lv'";
			$this->query($this->sql);
			$this->update();
		}
	}
	
	//--------------------------
	// データ削除
	//--------------------------
	function delete_data($d_lv) {
		$this->lv = $d_lv;
		$this->error = $this->lv_check();
		if(!$this->is_added($this->lv)) {
			$this->error = 1;
		}
		if($this->error == 0) {
			$sql = "DELETE FROM $this->table WHERE lv=$d_lv";
			$this->query($sql);
			$this->update();
		}
	}
	
	//--------------------------
	// 変数リセット
	//--------------------------
	function reset() {
		$this->error = 0;
		$this->lv = 0;
		$this->hp = 0;
		$this->sp = 0;
		$this->str = 0;
		$this->vit = 0;
		$this->dex = 0;
		$this->agi = 0;
		$this->wis = 0;
		$this->wil = 0;
		$this->sql = 0;
	}
}
?>
