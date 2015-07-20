<?php
function xml_quest_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = htmlspecialchars(preg_replace("/[\n]/", "##br##", $r));
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" />
	<part part="input" type="text" name="name[{$row["id"]}]" value="{$row["name"]}" size="32" br="1" />
	<part part="textarea" name="note[{$row["id"]}]" cols="64" rows="16" value="{$row["note"]}" br="1" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
