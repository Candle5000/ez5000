<?php
//==============================
//GuestDataクラス
//==============================
class GuestData extends MySQL {
	
	//--------------------------
	//変数の宣言
	//--------------------------
	
	//--------------------------
	//コンストラクタ
	//--------------------------
	function GuestData($userName, $password, $database) {
		parent::MySQL($userName, $password, $database);
	}
	
	//--------------------------
	// 全件選択
	//--------------------------
	function select_all($table) {
		$this->sql = "SELECT * FROM $table";
		$this->query($this->sql);
		return($this->rows());
	}
	
	//--------------------------
	// 全件から指定数取得
	//--------------------------
	function select_all_l($data, $table, $start, $limit, $key, $order) {
		$s_id = preg_match("/[A-Z]+/", $table) ? "lv" : "id";
		$this->sql = "SELECT $s_id FROM $table";
		$this->query($this->sql);
		$r = $this->rows();
		$this->sql = "SELECT $data FROM $table ORDER BY $key $order LIMIT $start,$limit";
		$this->query($this->sql);
		return($r);
	}

	//--------------------------
	//id検索
	//--------------------------
	function select_id($table, $s_id) {
		$this->sql = "SELECT * FROM $table WHERE id='$s_id'";
		$this->query($this->sql);
	}
	
	//--------------------------
	// 任意のカラム条件を検索
	//--------------------------
	function select_column($data, $table, $column, $value) {
		if(is_array($column) && is_array($value)) {
			foreach($column as $key => $col) {
				$match[] = preg_match("/^BETWEEN [0-9]+ AND [0-9]+/", $value[$key]) ? $col." ".$value[$key] : $col."='".$value[$key]."'";
				echo "<!--{$value[$key]}-->\n";
			}
			$match = implode(" AND ", $match);
		} else {
			$match = preg_match("/^BETWEEN [0-9]+ AND [0-9]+/", $value) ? $column." ".$value : $column."='".$value."'";
		}
		$this->sql = "SELECT $data FROM $table WHERE $match";
		$this->query($this->sql);
	}

	//--------------------------
	// 任意の条件を検索
	//--------------------------
	function select_column_a($data, $table, $match) {
		$this->sql = "SELECT $data FROM $table WHERE $match";
		$this->query($this->sql);
	}

	//--------------------------
	// 任意の条件を検索Plus
	//--------------------------
	function select_column_p($data, $table, $match, $start, $limit, $order) {
		$t = preg_replace("/,.+/", "", $table);
		$id = preg_match("/[A-Z]{3}/", $t) ? "lv" : "id";
		$this->sql = "SELECT $t.$id FROM $table WHERE $match";
		$this->query($this->sql);
		$count = $this->rows();
		$l = ($limit > 0) ? "LIMIT $start,$limit" : "";
		$this->sql = "SELECT $data FROM $table WHERE $match ORDER BY $order $l";
		$this->query($this->sql);
		return($count);
	}

	//--------------------------
	// 制限つきカラム条件検索
	//--------------------------
	function select_column_l($data, $table, $column, $value, $limit_start, $limit) {
		if(is_array($column) && is_array($value)) {
			foreach($column as $key => $col) {
				$match[] = $col."='".$value[$key]."'";
			}
			$match = implode(" AND ", $match);
		} else {
			$match = $column."='".$value."'";
		}
		$this->sql = "SELECT $data FROM $table WHERE $match LIMIT $limit_start,$limit";
		$this->query($this->sql);
	}

	//--------------------------
	// グループ化検索
	//--------------------------
	function select_group_by($data, $table, $where, $group, $having) {
		$this->sql = "SELECT $data FROM $table $where GROUP BY $group $having";
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
					$tmp1[] = " ".$cl." LIKE '%".mb_ereg_replace('_', '\\\\_', mb_ereg_replace('%', '\\\\%', mysqli_real_escape_string($this->m_Con, $kw)))."%' ";
				}
				$tmp0[] = implode("OR", $tmp1);
			}
		}

		if(count($tmp0) > 0) {
			// AND なり OR で連結してWHERE を作成
			$h = ($table == "items") ? " AND hidden=0" : "";
			$this->sql = "SELECT id FROM ".$table." WHERE ((".implode(") ".$mode." (",$tmp0)."))".$h;
			$this->query($this->sql);
			$count = $this->rows();
			$this->sql = "SELECT id,name FROM ".$table." WHERE ((".implode(") ".$mode." (",$tmp0)."))$h ORDER BY id LIMIT ".$start.", 50";
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
		if($table == "monster") {
			$column = array("zone", "id");
			$value = array(floor($s_id / 10000), $s_id % 10000);
			$this->select_column("id", $table, $column, $value);
		} else {
			$this->select_id($table, $s_id);
		}
		$result = $this->rows();
		$this->free();
		return($result);
	}
	
	//----------------------------------------
	// データリンク変換
	//----------------------------------------
	function data_link($str) {
		$pattern = "/##([cimsq][0-9]+[^0-9#]*)##(pri[0-9]+##)?/";
		$str = preg_replace("/##(get|use)/", "", $str);
		while(preg_match($pattern, $str, $match)) {
			preg_match("/([cimsq])/", $match[1], $tbl);
			preg_match("/([0-9]+)/", $match[1], $id);
			$name_str = preg_replace("/[cimsq0-9#]+/", "", $match[1]);
			$col = "id";
			$val = $id[1];

			// データ種別
			switch($tbl[1]) {
				case 'c':
					$table = "class";
					$link_name = "class";
					break;
				case 'i':
					$table = "items";
					$link_name = "item";
					break;
				case 'm':
					$table = "monster";
					$link_name = "monster";
					$col = array("zone", "id");
					$val = array(floor($id[1] / 10000), $id[1] % 10000);
					break;
				case 'q':
					$table = "quest";
					$link_name = "quest";
					break;
				case 's':
					$table = "skill";
					$link_name = "skill";
					break;
			}

			// 値段
			if(isset($match[2])) {
				preg_match("/^pri([0-9]+)##$/", $match[2], $price);
				$price_pattern = "pri".$price[1]."##";
				$price_text = "(".$price[1]." B)";
			} else {
				$price_pattern = "";
				$price_text = "";
			}

			// 置換パターン設定
			if(strlen($name_str)) {
				$link_text = $name_str;
				$replace_pattern = "/##".$tbl[1].$id[1].$name_str."##".$price_pattern."/";
			} else {
				$s_data = ($table != "monster") ? "name" : "name,nm";
				$this->select_column($s_data, $table, $col, $val);
				$row = $this->fetch();
				$link_text = ($table == "monster" && $row["nm"] == 1) ? "<span class=\"nm\">".$row["name"]."</span>" : $row["name"];
				$replace_pattern = "/##".$tbl[1].$id[1]."##".$price_pattern."/";
			}

			// 置換
			$replacement = '<a href="/db/'.$link_name.'/data/?id='.$id[1].'">'.$link_text.'</a>'.$price_text;
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
			if($table == "monster") {
				$zone = floor($id / 10000);
				$id = $id % 10000;
				$this->sql = "update $table set count=$count where zone=$zone and id=$id";
			} else {
				$this->sql = "update $table set count=$count where id=$id";
			}
			$this->query($this->sql);
			return($count);
		} else {
			return(-1);
		}
	}
}
?>
