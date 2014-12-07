<?php
//==============================
// SkillDataクラス
//==============================
class SkillData extends MySQL {
	
	//--------------------------
	// 変数の宣言
	//--------------------------
	public $error;
	public $id;
	public $name;
	public $category;
	public $learning;
	public $cost;
	public $recast;
	public $cast;
	public $text;
	public $note;
	public $ep;
	public $enhance;
	public $rankA;
	public $rankB;
	public $rankC;
	public $rankD;
	public $rankE;
	public $rankF;
	public $sql;
	
	//--------------------------
	// コンストラクタ
	//--------------------------
	function SkillData($userName, $password, $database) {
		parent::MySQL($userName, $password, $database);
		$this->reset();
	}
	
	//--------------------------
	// 入力の読み込み
	//--------------------------
	function read_data($r_id, $r_name, $r_category, $r_learning, $r_cost, $r_recast, $r_cast, $r_text, $r_note, $r_ep, $r_enhance) {
		$this->reset();
		$this->id = $r_id;
		$this->name = $r_name;
		$this->category = $r_category;
		$this->learning = $r_learning;
		$this->cost = $r_cost;
		$this->recast = $r_recast;
		$this->cast = $r_cast;
		$this->text = $r_text;
		$this->note = $r_note;
		$this->ep = $r_ep;
		$this->enhance = $r_enhance;
		$this->error = $this->admin_check();
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
		if(($this->error /  1) % 2) {echo "ERROR: が不適切です<br />\n";}
		if(($this->error /  2) % 2) {echo "ERROR: が不適切です<br />\n";}
		if(($this->error /  4) % 2) {echo "ERROR: が不適切です<br />\n";}
		if(($this->error /  8) % 2) {echo "ERROR: が不適切です<br />\n";}
		if(($this->error / 16) % 2) {echo "ERROR: UserNameが不適切です<br />\n";}
	}
	
	//--------------------------
	// 全件表示
	//--------------------------
	function select_all() {
		$this->sql = "SELECT * FROM skill";
		$this->query($this->sql);
	}
	
	//--------------------------
	// id検索
	//--------------------------
	function search_id($s_id) {
		$this->sql = "SELECT * FROM skill WHERE id='$s_id'";
		$this->query($this->sql);
	}
	
	//--------------------------
	// category検索
	//--------------------------
	function search_category($c_id) {
		$this->sql = "SELECT * FROM skill WHERE category='$c_id' ORDER BY id";
		$this->query($this->sql);
	}

	//--------------------------
	// LIMIT付きcategory検索
	//--------------------------
	function search_categoryl($c_id, $page, $size) {
		$this->sql = "SELECT * FROM skill WHERE category='$c_id' ORDER BY id LIMIT $page,$size";
		$this->query($this->sql);
	}

	//--------------------------
	// idの存在を確認
	//--------------------------
	function is_added($data) {
		$this->sql = "SELECT id FROM skill WHERE id='$data'";
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
			$this->sql = "INSERT INTO skill VALUES($this->id,'$this->name','$this->category','$this->learning','$this->cost','$this->recast','$this->cast','$this->text','$this->note','$this->ep','$this->enhance','$date','0')";
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
			$this->sql = "UPDATE skill SET name='$this->name',category='$this->category',learning='$this->learning',cost='$this->cost',recast='$this->recast',cast='$this->cast',text='$this->text',note='$this->note',ep='$this->ep',enhance='$this->enhance',updated='$date' WHERE id='$this->id'";
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
			$sql = "DELETE FROM skill WHERE id=$d_id";
			$this->query($sql);
		}
	}
	
	//--------------------------
	// 変数リセット
	//--------------------------
	function reset() {
		$error = 0;
		$id = 0;
		$name = "";
		$category = 0;
		$learning = 0;
		$cost = 0;
		$recast = -1;
		$cast = -8;
		$text = "";
		$note = "";
		$ep = 0;
		$enhance = "";
		$sql = "";
	}
}
?>
