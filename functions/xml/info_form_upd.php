<?php
function xml_info_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = htmlspecialchars(preg_replace("/[\n]/", "##br##", $r));
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" />
	<part part="input" type="text" name="subject[{$row["id"]}]" value="{$row["subject"]}" size="30" br="1" />
	<part part="textarea" name="info[{$row["id"]}]" cols="48" rows="2" value="{$row["info"]}" br="1" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
