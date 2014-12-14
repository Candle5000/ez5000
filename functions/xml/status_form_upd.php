<?php
function xml_status_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\n]/", "##br##", $r);
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["lv"]}:" />
	<part head="HP" part="input" type="text" name="hp[{$row["lv"]}]" value="{$row["hp"]}" size="2" />
	<part head="SP" part="input" type="text" name="sp[{$row["lv"]}]" value="{$row["sp"]}" size="2" />
	<part head="STR" part="input" type="text" name="str[{$row["lv"]}]" value="{$row["str"]}" size="1" />
	<part head="VIT" part="input" type="text" name="vit[{$row["lv"]}]" value="{$row["vit"]}" size="1" />
	<part head="DEX" part="input" type="text" name="dex[{$row["lv"]}]" value="{$row["dex"]}" size="1" />
	<part head="AGI" part="input" type="text" name="agi[{$row["lv"]}]" value="{$row["agi"]}" size="1" />
	<part head="WIS" part="input" type="text" name="wis[{$row["lv"]}]" value="{$row["wis"]}" size="1" />
	<part head="WIL" part="input" type="text" name="wil[{$row["lv"]}]" value="{$row["wil"]}" size="1" />
	<part part="input" type="submit" name="submit_upd[{$row["lv"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
