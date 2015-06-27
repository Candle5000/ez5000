<?php
function xml_bmskill_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\n]/", "##br##", $r);
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="Lv{$row["id"]}:" />
	<part head="S" part="input" type="text" name="S[{$row["id"]}]" value="{$row["S"]}" size="3" />
	<part head="A" part="input" type="text" name="A[{$row["id"]}]" value="{$row["A"]}" size="3" />
	<part head="B" part="input" type="text" name="B[{$row["id"]}]" value="{$row["B"]}" size="3" />
	<part head="C" part="input" type="text" name="C[{$row["id"]}]" value="{$row["C"]}" size="3" />
	<part head="D" part="input" type="text" name="D[{$row["id"]}]" value="{$row["D"]}" size="3" />
	<part head="E" part="input" type="text" name="E[{$row["id"]}]" value="{$row["E"]}" size="3" />
	<part head="F" part="input" type="text" name="F[{$row["id"]}]" value="{$row["F"]}" size="3" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
