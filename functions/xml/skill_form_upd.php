<?php
function xml_skill_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\n]/", "##br##", $r);
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" />
	<part head="Name" part="input" type="text" name="name[{$row["id"]}]" value="{$row["name"]}" size="20" />
	<part head="Category" part="input" type="text" name="category[{$row["id"]}]" value="{$row["category"]}" size="3" br="1" />
	<part head="Learning" part="textarea" name="learning[{$row["id"]}]" cols="48" rows="2" value="{$row["learning"]}" br="1" />
	<part head="Cost" part="input" type="text" name="cost[{$row["id"]}]" value="{$row["cost"]}" size="3" />
	<part head="Recast" part="input" type="text" name="recast[{$row["id"]}]" value="{$row["recast"]}" size="6" />
	<part head="Cast" part="input" type="text" name="cast[{$row["id"]}]" value="{$row["cast"]}" size="6" br="1" />
	<part head="Text" part="textarea" name="text[{$row["id"]}]" cols="48" rows="2" value="{$row["text"]}" br="1" />
	<part head="Note" part="textarea" name="note[{$row["id"]}]" cols="48" rows="2" value="{$row["note"]}" br="1" />
	<part head="Enhance : EP" part="input" type="text" name="ep[{$row["id"]}]" value="{$row["ep"]}" size="3" br="1" />
	<part part="textarea" name="enhance[{$row["id"]}]" cols="48" rows="2" value="{$row["enhance"]}" br="1" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>

