<?php
//=====================================
// フォーム操作用関数
//=====================================

//----------------------------------------
// チェックボックスを選択済みにする
//----------------------------------------
function form_checked($value) {
	if($value) {
		return("value=\"1\" checked");
	} else {
		return("value=\"1\"");
	}
}

//----------------------------------------
// ラジオボタンを選択済みにする
//----------------------------------------
function form_radio_checked($value, $selected) {
	if($value == $selected) {
		return("value=\"".$value."\" checked");
	} else {
		return("value=\"".$value."\"");
	}
}

//----------------------------------------	
// 選択リストを選択済みにする
//----------------------------------------	
function form_selected($value, $selected) {
	if($value == $selected) {
		return("value=\"$value\" selected");
	} else {
		return("value=\"$value\"");
	}
}

?>
