<?php
//==============================
//formクラス
//==============================
class Form {

	//--------------------------
	//変数の宣言
	//--------------------------
	private $action;
	private $method;
	private $enctype;

	//--------------------------
	//コンストラクタ
	//--------------------------
	function Form($act, $mth, $enc) {
		$this->action = $act;
		$this->method = $mth;
		$this->enctype = $enc;
	}

	//--------------------------
	//フォーム開始
	//--------------------------
	function start() {
		return("<form action=\"".$this->action."\" method=\"".$this->method."\" enctype=\"".$this->enctype."\">\n");
	}

	//--------------------------
	//フォーム終了
	//--------------------------
	function close() {
		return("</form>\n");
	}

	//--------------------------
	//SUBMITフォーム作成
	//--------------------------
	function submit($name, $value) {
		$part = array("part" => "input", "type" => "submit", "name" => "submit_$name", "value" => $value);
		return($this->build($part));
	}
	
	//--------------------------
	//XMLファイルからフォームを作成
	//--------------------------
	function load_xml_file($xml) {
		$object = simplexml_load_file($xml);
		$form = "";
		foreach($object->part as $part) {
			$form .= $this->build($part);
		}
		return($form);
	}

	//--------------------------
	//XML文字列からフォームを作成
	//--------------------------
	function load_xml_string($xml) {
		$string = simplexml_load_string($xml);
		$form = "";
		foreach($string->part as $part) {
			$form .= $this->build($part);
		}
		return($form);
	}

	//--------------------------
	//グループSELECTフォームを作成
	//--------------------------
	function build_select_group($xml, $selected) {
		$form = "";
		$categories = simplexml_load_file($xml);
		$part = array('part' => 'select', 'name' => 'group', 'selected' => $selected);
		foreach($categories->category as $category) {
			foreach($category->group as $group) {
				$id = $group["id"];
				$part["option"]["$id"] = $category["name"]." ".$group["name"];
			}
		}
		$form .= $this->build($part);
		$form .= $this->submit("group", "表示");
		return($form);
	}

	//--------------------------
	//ページSELECTフォームを作成
	//--------------------------
	function build_select_page($count, $size, $selected) {
		$form = "";
		$part = array('part' => 'select', 'name' => 'page', 'selected' => $selected);
		for($page = 0; $page < $count; $page += $size) {
			$part["option"]["$page"] = ($page + 1)."-".($page + $size);
		}
		$form .= $this->build($part);
		$form .= $this->submit("page", "表示");
		return($form);
	}

	//--------------------------
	//配列からパーツを分類して作成
	//--------------------------
	function build($array) {
		$part = "";

		//先頭テキストの付加
		if(isset($array["head"])) $part .= $array["head"];

		//フォームパーツ
		switch($array["part"]) {

			case "input":
				$part .= '<input type="'.$array["type"].'"';
				if(isset($array["name"])) $part .= ' name="'.$array["name"].'"';
				if(isset($array["value"])) $part .= ' value="'.$array["value"].'"';
				if(isset($array["size"])) $part .= ' size="'.$array["size"].'"';
				if(isset($array["checked"]) && ("{$array["checked"]}" == "{$array["value"]}")) $part .= " checked";
				$part .= ">";
				break;

			case "textarea":
				$part .= '<textarea name="'.$array["name"].'"';
				if(isset($array["cols"])) $part .= ' cols="'.$array["cols"].'"';
				if(isset($array["rows"])) $part .= ' rows="'.$array["rows"].'"';
				$part .= " wrap=\"soft\">";
				if(isset($array["value"])) $part .= preg_replace("/##br##/", "\n", $array["value"]);
				$part .= "</textarea>";
				break;

			case "select":
				$part .= '<select name="'.$array["name"]."\">\n";
				foreach($array["option"] as $key=>$option) {
					$part .= '<option value="'.$key.'"';
					if(isset($array["selected"]) && ($key == $array["selected"])) $part .= " selected";
					$part .= ">".$option."</option>\n";
				}
				$part .= "</select>";
				break;

			case "text":
				break;
		}

		//末尾テキストの付加
		if(isset($array["tail"])) $part .= $array["tail"];

		//改行の付加
		if(isset($array["br"])) {
			$part .= "<br />";
			for($i = 1; $i < $array["br"]; $i++) {
				$part .= "\n<br />";
			}
		}

		return($part."\n");
	}
}
