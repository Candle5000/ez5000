<?php
//==============================
//formクラス
//==============================
class Form {

	//--------------------------
	//変数の宣言
	//--------------------------

	//--------------------------
	//コンストラクタ
	//--------------------------
	/*
	function Form() {
	}
	*/

	//--------------------------
	//フォーム開始
	//--------------------------
	function start($action, $method, $enctype) {
		return("<form action=\"$action\" method=\"$method\" enctype=\"$enctype\">");
	}

	//--------------------------
	//フォーム終了
	//--------------------------
	function close() {
		return("</form>");
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
				if(isset($array["checked"]) && $array["value"] == $array["checked"]) $part .= " checked";
				$part .= ">";
				break;

			case "textarea":
				$part .= '<textarea name="'.$array["name"].'"';
				if(isset($array["cols"])) $part .= ' cols="'.$array["cols"].'"';
				if(isset($array["rows"])) $part .= ' rows="'.$array["rows"].'"';
				$part .= ">";
				if(isset($array["value"])) $part .= $array["value"];
				$part .= "</textarea>";
				break;

			case "select":
				$part .= '<select name="'.$array["name"].">\n";
				foreach($array["option"] as $option) {
					$part .= '<option value="'.key($array["option"]).'"';
					if(isset($array["selected"]) && $array["value"] == $array["selected"]) $part .= " selected";
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

		return($part);
	}
}
