<?php
function xml_zone_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\n]/", "##br##", $r);
	$row["id"] = str_pad($row["id"], 3, "0", STR_PAD_LEFT);
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" />
	<part head="NAME" part="input" type="text" name="name[{$row["id"]}]" value="{$row["name"]}" size="20" br="1" />
	<part head="E_NAME" part="input" type="text" name="nameE[{$row["id"]}]" value="{$row["nameE"]}" size="20" />
	<part head="S_NAME" part="input" type="text" name="nameS[{$row["id"]}]" value="{$row["nameS"]}" size="10" br="1" />
	<part part="input" type="checkbox" name="event[{$row["id"]}]" value="1" checked="{$row["event"]}" tail="イベント" br="1" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
