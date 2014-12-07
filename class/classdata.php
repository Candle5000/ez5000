<?php
//==============================
// ClassDataクラス
//==============================
class ClassData extends MySQL {
	
	//--------------------------
	// 変数の宣言
	//--------------------------
	public $error;
	public $id;
	public $name;
	public $nameE;
	public $nameS;
	public $dagger;
	public $sword;
	public $axe;
	public $hammer;
	public $wand;
	public $bow;
	public $dodge;
	public $shield;
	public $element;
	public $light;
	public $dark;
	public $note;
	public $sql;
	
	//--------------------------
	// コンストラクタ
	//--------------------------
	function ClassData($userName, $password, $database) {
		parent::MySQL($userName, $password, $database);
		$this->reset();
	}
	
	//--------------------------
	// 入力の読み込み
	//--------------------------
	function read_data($r_id, $r_name, $r_nameE, $r_nameS, $r_dagger, $r_sword, $r_axe, $r_hammer, $r_wand, $r_bow, $r_dodge, $r_shield, $r_element, $r_light, $r_dark, $r_note) {
		$this->reset();
		$this->id = htmlspecialchars($r_id, ENT_QUOTES);
		$this->name = htmlspecialchars($r_name, ENT_QUOTES);
		$this->nameE = htmlspecialchars($r_nameE, ENT_QUOTES);
		$this->nameS = htmlspecialchars($r_nameS, ENT_QUOTES);
		$this->dagger = $this->skill_check(htmlspecialchars($r_dagger, ENT_QUOTES));
		$this->sword = $this->skill_check(htmlspecialchars($r_sword, ENT_QUOTES));
		$this->axe = $this->skill_check(htmlspecialchars($r_axe, ENT_QUOTES));
		$this->hammer = $this->skill_check(htmlspecialchars($r_hammer, ENT_QUOTES));
		$this->wand = $this->skill_check(htmlspecialchars($r_wand, ENT_QUOTES));
		$this->bow = $this->skill_check(htmlspecialchars($r_bow, ENT_QUOTES));
		$this->dodge = $this->skill_check(htmlspecialchars($r_dodge, ENT_QUOTES));
		$this->shield = $this->skill_check(htmlspecialchars($r_shield, ENT_QUOTES));
		$this->element = $this->skill_check(htmlspecialchars($r_element, ENT_QUOTES));
		$this->light = $this->skill_check(htmlspecialchars($r_light, ENT_QUOTES));
		$this->dark = $this->skill_check(htmlspecialchars($r_dark, ENT_QUOTES));
		$this->note = htmlspecialchars($r_note, ENT_QUOTES);
		$this->error = $this->id_check() + $this->name_check() + $this->nameE_check() + $this->nameS_check() + $this->admin_check();
	}
	
	//----------------------------------------
	// ID入力エラーチェック
	//----------------------------------------
	function id_check() {
		if(preg_match("/[1-9]{1}[0-9]{2}/", $this->id)) {
			return(0);
		} else {
			return(1);
		}
	}
	
	//----------------------------------------
	// 名前入力エラーチェック
	//----------------------------------------
	function name_check() {
		if(preg_match("/[ァ-ー]{2,30}/u", $this->name)) {
			return(0);
		} else {
			return(2);
		}
	}
	
	//----------------------------------------
	// 英名入力エラーチェック
	//----------------------------------------
	function nameE_check() {
		if(preg_match("/[A-Z]{2,20}/", $this->nameE)) {
			return(0);
		} else {
			return(4);
		}
	}
	
	//----------------------------------------
	// 略名入力エラーチェック
	//----------------------------------------
	function nameS_check() {
		if(preg_match("/[A-Z]{3}/", $this->nameS)) {
			return(0);
		} else {
			return(8);
		}
	}
	
	//----------------------------------------
	// 権限エラーチェック
	//----------------------------------------
	function admin_check() {
		if($this->m_UserName == "admin") {
			return(0);
		} else {
			return(16);
		}
	}
	
	//----------------------------------------
	// エラー出力
	//----------------------------------------
	function print_error() {
		if(($this->error /  1) % 2) {echo "ERROR: IDが不適切です<br />\n";}
		if(($this->error /  2) % 2) {echo "ERROR: 名前が不適切です<br />\n";}
		if(($this->error /  4) % 2) {echo "ERROR: nameEが不適切です<br />\n";}
		if(($this->error /  8) % 2) {echo "ERROR: nameSが不適切です<br />\n";}
		if(($this->error / 16) % 2) {echo "ERROR: UserNameが不適切です<br />\n";}
	}
	
	//----------------------------------------
	// スキル値入力チェック
	//----------------------------------------
	function skill_check($skill) {
		if(preg_match("/[A-FSX]{1}/", $skill)) {
			return($skill);
		} else {
			return("X");
		}
	}
	
	//--------------------------
	// 全件表示
	//--------------------------
	function select_all() {
		$this->sql = "SELECT * FROM class";
		$this->query($this->sql);
	}
	
	//--------------------------
	// id検索
	//--------------------------
	function search_id($s_id) {
		$this->sql = "SELECT * FROM class WHERE id='$s_id'";
		$this->query($this->sql);
	}
	
	//--------------------------
	// idの存在を確認
	//--------------------------
	function is_added($data) {
		$this->sql = "SELECT id FROM class WHERE id='$data'";
		$this->query($this->sql);
		$result = $this->rows();
		$this->free();
		return($result);
	}
	
	//--------------------------
	// データ追加
	//--------------------------
	function add_data() {
		if($this->is_added($this->id)) {
			$this->error = 1;
		}
		if($this->error == 0) {
			$date = date("Y-m-d");
			$this->sql = "INSERT INTO class VALUES($this->id,'$this->name','$this->nameE','$this->nameS','$this->dagger','$this->sword','$this->axe','$this->hammer','$this->wand','$this->bow','$this->dodge','$this->shield','$this->element','$this->light','$this->dark','$this->note','$date','0')";
			$this->query($this->sql);
		}
	}
	
	//--------------------------
	// データ変更
	//--------------------------
	function update_data() {
		if(!$this->is_added($this->id)) {
			$this->error = 1;
		}
		if($this->error == 0) {
			$date = date("Y-m-d");
			$this->sql = "UPDATE class SET name='$this->name',nameE='$this->nameE',nameS='$this->nameS',dagger='$this->dagger',sword='$this->sword',axe='$this->axe',hammer='$this->hammer',wand='$this->wand',bow='$this->bow',dodge='$this->dodge',shield='$this->shield',element='$this->element',light='$this->light',dark='$this->dark',note='$this->note',updated='$date' WHERE id='$this->id'";
			$this->query($this->sql);
		}
	}
	
	//--------------------------
	// データ削除
	//--------------------------
	function delete_data($d_id) {
		$this->id = $d_id;
		$this->error = $this->id_check();
		if(!$this->is_added($this->id)) {
			$this->error = 1;
		}
		if($this->error == 0) {
			$sql = "DELETE FROM class WHERE id=$d_id";
			$this->query($sql);
		}
	}
	
	//--------------------------
	// 変数リセット
	//--------------------------
	function reset() {
		$this->error = 0;
		$this->id = 0;
		$this->name = "";
		$this->nameE = "";
		$this->nameS = "";
		$this->dagger = "";
		$this->sword = "";
		$this->axe = "";
		$this->hammer = "";
		$this->wand = "";
		$this->bow = "";
		$this->dodge = "";
		$this->shield = "";
		$this->element = "";
		$this->light = "";
		$this->dark = "";
		$this->note = "";
		$this->sql = "";
	}
}
?>
