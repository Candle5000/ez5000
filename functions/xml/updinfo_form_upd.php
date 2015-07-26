<?php
function xml_updinfo_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = htmlspecialchars(preg_replace("/[\n]/", "##br##", $r));
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" br="1" />
	<part part="textarea" name="detail[{$row["id"]}]" cols="48" rows="16" value="{$row["detail"]}" br="1" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>

