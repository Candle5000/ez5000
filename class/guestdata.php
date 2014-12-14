<?php
//==============================
//GuestDataクラス
//==============================
class GuestData extends MySQL {
	
	//--------------------------
	//変数の宣言
	//--------------------------
	public $i_hidden;
	
	//--------------------------
	//コンストラクタ
	//--------------------------
	function GuestData($userName, $password, $database, $hidden) {
		parent::MySQL($userName, $password, $database);
		$this->i_hidden = $hidden;
	}
	
	//--------------------------
	// 全件選択
	//--------------------------
	function select_all($table) {
		$this->sql = "SELECT * FROM $table";
		$this->query($this->sql);
	}
	
	//--------------------------
	// 全件から指定数取得
	//--------------------------
	function select_all_l($data, $table, $start, $limit, $key, $order) {
		$this->sql = "SELECT $data FROM $table ORDER BY $key $order LIMIT $start,$limit";
		$this->query($this->sql);
	}

	//--------------------------
	//id検索
	//--------------------------
	function select_id($table, $s_id) {
		$hidden_text = $this->hide_data($table);
		$this->sql = "SELECT * FROM $table WHERE id='$s_id'".$hidden_text;
		$this->query($this->sql);
	}
	
	//--------------------------
	//範囲指定id検索
	//--------------------------
	function select_group($data, $table, $start, $end) {
		$hidden_text = $this->hide_data($table);
		if(preg_match("/[A-Z]+/", $table)) {
			$s_id = "lv";
		} else {
			$s_id = "id";
		}
		$this->sql = "SELECT $data FROM $table WHERE $s_id BETWEEN '$start' AND '$end'".$hidden_text." ORDER BY $s_id";
		$this->query($this->sql);
	}
	
	//--------------------------
	//制限つき範囲指定id検索
	//--------------------------
	function select_group_l($data, $table, $start, $end, $limit_start, $limit) {
		$hidden_text = $this->hide_data($table);
		if(preg_match("/[A-Z]+/", $table)) {
			$s_id = "lv";
		} else {
			$s_id = "id";
		}
		$this->sql = "SELECT $data FROM $table WHERE $s_id BETWEEN '$start' AND '$end'".$hidden_text." ORDER BY $s_id LIMIT $limit_start,$limit";
		$this->query($this->sql);
	}
	
	//--------------------------
	// 任意のカラム条件を検索
	//--------------------------
	function select_column($data, $table, $column, $value) {
		$this->sql = "SELECT $data FROM $table WHERE $column='$value'";
		$this->query($this->sql);
	}

	//--------------------------
	// 制限つきカラム条件検索
	//--------------------------
	function select_column_l($data, $table, $column, $value, $limit_start, $limit) {
		$this->sql = "SELECT $data FROM $table WHERE $column='$value' LIMIT $limit_start,$limit";
		$this->query($this->sql);
	}

	//--------------------------
	// 自由入力検索
	//--------------------------
	function search_words($input, $table, $mode, $start) {
		// 空白と英数を半角、カタカナと波線を全角に変換 
		$input = preg_replace("/~/", "～", mb_convert_kana($input,"asKV"));

		// 連続する空白文字で分割
		$keywords = preg_split("/[\s]+/", $input);

		if($table == "items") {
			$columns = array('name','text');
		} else {
			$columns = array('name');
		}

		if(($mode != 'AND') && ($mode != 'OR')) {
			$mode = 'AND';
		}

		// LIKE を作成して配列に格納する
		// クオートやエスケープもする
		$tmp0 = array();
		foreach($keywords as $kw) {
			if($kw == "") {
				// 空っぽなら無視
			} else {
				$tmp1 = array();
				foreach($columns as $cl) {
					$tmp1[] = " ".$cl." LIKE '%".mb_ereg_replace('_', '\\\\_', mb_ereg_replace('%', '\\\\%', mysql_real_escape_string($kw)))."%' ";
				}
				$tmp0[] = implode("OR", $tmp1);
			}
		}

		if(count($tmp0) > 0) {
			// AND なり OR で連結してWHERE を作成
			$this->sql = "SELECT id FROM ".$table." WHERE (".implode(") ".$mode." (",$tmp0).")".$this->hide_data($table);
			$this->query($this->sql);
			$count = $this->rows();
			$this->sql = "SELECT id,name FROM ".$table." WHERE ((".implode(") ".$mode." (",$tmp0)."))".$this->hide_data($table)." ORDER BY id LIMIT ".$start.", 50";
			$this->query($this->sql);
			return($count);
		} else {
			return(0);
		}
	}

	//--------------------------
	//idの存在を確認
	//--------------------------
	function is_added($table, $s_id) {
		$hidden_text = $this->hide_data($table);
		$this->sql = "SELECT id FROM $table WHERE id='$s_id'".$hidden_text;
		$this->query($this->sql);
		$result = $this->rows();
		$this->free();
		return($result);
	}
	
	//--------------------------
	//未実装データを隠す
	//--------------------------
	function hide_data($table) {
		if($table == "items" && $this->i_hidden) {
			return(" AND hidden='0'");
		} else {
			return("");
		}
	}
	
	//----------------------------------------
	// データリンク変換
	//----------------------------------------
	function data_link($str) {
		$pattern = "/##([cis][0-9]+[^0-9#]*)##/";
		while(preg_match($pattern, $str, $match)) {
			preg_match("/([cis])/", $match[1], $tbl);
			preg_match("/([0-9]+)/", $match[1], $id);
			$name_str = preg_replace("/[cis0-9#]+/", "", $match[1]);
			switch($tbl[1]) {
				case 'c':
					$table = "class";
					$link_name = "class";
					break;
				case 'i':
					$table = "items";
					$link_name = "item";
					break;
				case 's':
					$table = "skill";
					$link_name = "skill";
					break;
			}
			if(strlen($name_str)) {
				$link_text = $name_str;
				$replace_pattern = "/##".$tbl[1].$id[1].$name_str."##/";
			} else {
				$this->select_column("name", $table, "id", $id[1]);
				$row = $this->fetch();
				$link_text = $row["name"];
				$replace_pattern = "/##".$tbl[1].$id[1]."##/";
			}
			$replacement = '<a href="/db/'.$link_name.'/data/?id='.$id[1].'">'.$link_text.'</a>';
			$str = preg_replace($replace_pattern, $replacement, $str);
		}
		return($str);
	}

	//--------------------------
	// トップページ アクセスカウント
	//--------------------------
	function top_count() {
		$date = date("Y-m-d");
		$yest = date("Y-m-d", strtotime("-1 day"));
		$mnth = date("Y-m-");

		if($this->is_added("topcount", $date)) {
			$this->sql = "SELECT count FROM topcount WHERE id='$date'";
			$this->query($this->sql);
			$c = $this->fetch();
			$count['t'] = $c['count'] + 1;
			$this->sql = "UPDATE topcount SET count={$count['t']} WHERE id='$date'";
			$this->query($this->sql);
		} else {
			$this->sql = "INSERT INTO topcount (id) VALUE ('$date')";
			$this->query($this->sql);
			$count['t'] = 1;
		}

		if($this->is_added("topcount", $yest)) {
			$this->sql = "SELECT count FROM topcount WHERE id='$yest'";
			$this->query($this->sql);
			$c = $this->fetch();
			$count['y'] = $c['count'];
		} else {
			$count['y'] = 0;
		}

		$this->sql = "SELECT sum(count) FROM topcount WHERE id LIKE '$mnth%'";
		$this->query($this->sql);
		$c = $this->fetch();
		$count['m'] = $c['sum(count)'];

		return($count);
	}

	//--------------------------
	// データページ アクセスカウント
	//--------------------------
	function access_count($table, $id, $count) {
		if($this->is_added($table, $id)) {
			$count++;
			$this->sql = "UPDATE $table SET count=$count WHERE id=$id";
			$this->query($this->sql);
			return($count);
		} else {
			return(-1);
		}
	}
}
?>
