<?php
function xml_class_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\n]/", "##br##", $r);
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" />
	<part head="NAME" part="input" type="text" name="name[{$row["id"]}]" value="{$row["name"]}" size="20" br="1" />
	<part head="E_NAME" part="input" type="text" name="nameE[{$row["id"]}]" value="{$row["nameE"]}" size="20" />
	<part head="S_NAME" part="input" type="text" name="nameS[{$row["id"]}]" value="{$row["nameS"]}" size="4" br="1" />
	<part head="短剣" part="input" type="text" name="dagger[{$row["id"]}]" value="{$row["dagger"]}" size="1" />
	<part head="長剣" part="input" type="text" name="sword[{$row["id"]}]" value="{$row["sword"]}" size="1" />
	<part head="斧" part="input" type="text" name="axe[{$row["id"]}]" value="{$row["axe"]}" size="1" />
	<part head="槌" part="input" type="text" name="hammer[{$row["id"]}]" value="{$row["hammer"]}" size="1" />
	<part head="杖" part="input" type="text" name="wand[{$row["id"]}]" value="{$row["wand"]}" size="1" />
	<part head="弓" part="input" type="text" name="bow[{$row["id"]}]" value="{$row["bow"]}" size="1" br="1" />
	<part head="回避" part="input" type="text" name="dodge[{$row["id"]}]" value="{$row["dodge"]}" size="1" />
	<part head="盾" part="input" type="text" name="shield[{$row["id"]}]" value="{$row["shield"]}" size="1" />
	<part head="元素" part="input" type="text" name="element[{$row["id"]}]" value="{$row["element"]}" size="1" />
	<part head="光" part="input" type="text" name="light[{$row["id"]}]" value="{$row["light"]}" size="1" />
	<part head="闇" part="input" type="text" name="dark[{$row["id"]}]" value="{$row["dark"]}" size="1" br="1" />
	<part part="textarea" name="note[{$row["id"]}]" cols="48" rows="2" value="{$row["note"]}" br="1" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
