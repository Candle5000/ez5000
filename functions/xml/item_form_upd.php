<?php
function xml_item_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = htmlspecialchars(preg_replace("/[\n]/", "##br##", $r));
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" />
	<part part="input" type="text" name="name[{$row["id"]}]" value="{$row["name"]}" size="20" br="1" />
	<part part="textarea" name="text[{$row["id"]}]" cols="48" rows="2" value="{$row["text"]}" br="1" />
	<part part="input" type="checkbox" name="rare[{$row["id"]}]" value="1" checked="{$row["rare"]}" tail="RARE " />
	<part part="input" type="checkbox" name="notrade[{$row["id"]}]" value="1" checked="{$row["notrade"]}" tail="NOTRADE " br="1" />
	<part head="売却" part="input" type="text" name="price[{$row["id"]}]" value="{$row["price"]}" size="8" />
	<part head="スタック" part="input" type="text" name="stack[{$row["id"]}]" value="{$row["stack"]}" size="4" br="1" />
	<part part="textarea" name="note[{$row["id"]}]" cols="48" rows="2" value="{$row["note"]}" br="1" />
	<part part="input" type="checkbox" name="hidden[{$row["id"]}]" value="1" checked="{$row["hidden"]}" tail="未実装 " />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
