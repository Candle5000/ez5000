<?php
//==============================
//ItemDataクラス
//==============================
class ItemData extends MySQL {
	
	//--------------------------
	//変数の宣言
	//--------------------------
	public $error;
	public $error_list;
	public $id;
	public $name;
	public $text;
	public $rare;
	public $notrade;
	public $price;
	public $stack;
	public $note;
	public $hidden;
	public $sql;
	
	//--------------------------
	//コンストラクタ
	//--------------------------
	function ItemData($userName, $password, $database) {
		parent::MySQL($userName, $password, $database);
		$this->reset();
	}
	
	//--------------------------
	//入力の読み込み
	//--------------------------
	function read_data($r_id, $r_name, $r_text, $r_rare, $r_notrade, $r_price, $r_stack, $r_note, $r_hidden) {
		$this->reset();
		$this->id = htmlspecialchars($r_id, ENT_QUOTES);
		$this->name = htmlspecialchars($r_name, ENT_QUOTES);
		$this->text = htmlspecialchars($r_text, ENT_QUOTES);
		$this->rare = $r_rare;
		$this->notrade = $r_notrade;
		$this->price = htmlspecialchars($r_price, ENT_QUOTES);
		$this->stack = htmlspecialchars($r_stack, ENT_QUOTES);
		if(strlen($this->note)) {$this->note = htmlspecialchars($r_note, ENT_QUOTES);}
		$this->hidden = $r_hidden;
	}
		
	//--------------------------
	//ファイル入力の読み込み
	//--------------------------
	function read_file($r_id, $r_name, $r_text, $r_rare, $r_notrade, $r_price, $r_stack) {
		$this->reset();
		$this->id = htmlspecialchars($r_id, ENT_QUOTES);
		$this->name = htmlspecialchars($r_name, ENT_QUOTES);
		$this->text = htmlspecialchars($r_text, ENT_QUOTES);
		$this->rare = $r_rare;
		$this->notrade = $r_notrade;
		$this->price = htmlspecialchars($r_price, ENT_QUOTES);
		$this->stack = htmlspecialchars($r_stack, ENT_QUOTES);
	}
	
	//--------------------------
	//id検索
	//--------------------------
	function search_id($s_id) {
		$this->sql = "SELECT * FROM items WHERE id='$s_id'";
		$this->query($this->sql);
	}
	
	//--------------------------
	//未実装データを隠す
	//--------------------------
	function hide_data() {
		if($this->is_admin()) {
			return("");
		} else {
			return("AND hidden='0'");
		}
	}
	
	//--------------------------
	//idの存在を確認
	//--------------------------
	function is_added($data) {
		$hidden_text = $this->hide_data();
		$this->sql = "SELECT id FROM items WHERE id='$data' $hidden_text";
		$this->query($this->sql);
		$result = $this->rows();
		$this->free();
		return($result);
	}
	
	//--------------------------
	// 種類別一覧取得
	//--------------------------
	function select_group($start, $data) {
		$hidden_text = $this->hide_data();
		if($start < 13000) {
			$end = $start + 99;
		} else {
			$end = $start + 1000;
		}
		$start += 1;
		$this->sql = "SELECT $data FROM items WHERE id BETWEEN '$start' AND '$end' $hidden_text ORDER BY id";
		$this->query($this->sql);
	}
	
	//--------------------------
	// 種類別指定数取得
	//--------------------------
	function select_groupl($start, $data, $page, $limit) {
		$hidden_text = $this->hide_data();
		if($start < 13000) {
			$end = $start + 99;
		} else {
			$end = $start + 1000;
		}
		$start += 1;
		$this->sql = "SELECT $data FROM items WHERE id BETWEEN '$start' AND '$end' $hidden_text ORDER BY id LIMIT $page,$limit";
		$this->query($this->sql);
	}
	
	//--------------------------
	//データ全項目追加
	//--------------------------
	function add_full_data() {
		if(!$this->is_admin()) {
			$this->error = "新規登録の権限がありません<br />";
		}
		if(((preg_match("/^[0-9]{5}$/", $this->id)) && (!$this->is_added($this->id))) && ($this->error=="")) {
			$date = date("Y-m-d");
			$this->sql = "INSERT INTO items (id, name, text, rare, notrade, price, stack, note, hidden, updated) VALUES($this->id,'$this->name','$this->text','$this->rare','$this->notrade','$this->price','$this->stack','$this->note','$this->hidden','$date')";
			$this->query($this->sql);
		} else {
			$this->error_list[$this->id] = "登録に失敗しました。";
		}
	}
	
	//--------------------------
	//データ一部追加
	//--------------------------
	function add_some_data() {
		if(!$this->is_admin()) {
			$this->error = "新規登録の権限がありません<br />";
		} else if(!preg_match("/^[0-9]{5}$/", $this->id)){
			$this->error = "新規番号[$this->id]に誤りがあります<br />";
		} else {
			if($this->is_added($this->id)) {
				$this->error = "新規番号[$this->id]は既に存在しています<br />";
			}
		}	if ($this->error==""){
			$date = date("Y-m-d");
			$this->sql = "INSERT INTO items (id, name, text, rare, notrade, price, stack, updated) VALUES($this->id,'$this->name','$this->text','$this->rare','$this->notrade','$this->price','$this->stack','$date')";
			$this->query($this->sql);
		}
	}
	
	//--------------------------
	//データ変更
	//--------------------------
	function update_data() {
		if(!$this->is_admin()) {
			$this->error = "新規登録の権限がありません<br />";
		} else if(!$this->is_added($this->id)) {
			die("ID:$this->id が存在しません");
		} else {
			$date = date("Y-m-d");
			$this->sql = "UPDATE items SET name='$this->name',text='$this->text',rare='$this->rare',notrade='$this->notrade',price='$this->price',stack='$this->stack',note='$this->note',hidden='$this->hidden',updated='$date' WHERE id='$this->id'";
			$this->query($this->sql);
		}
	}
	
	//--------------------------
	//変数リセット
	//--------------------------
	function reset() {
		$this->error = "";
		$this->id = "";
		$this->name = "";
		$this->text = "";
		$this->rare = 0;
		$this->notrade = 0;
		$this->price = "-1";
		$this->stack = "1";
		$this->note = "特になし";
		$this->hidden = 0;
		$this->sql = "";
	}
}
?>
