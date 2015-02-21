<?php
function xml_monster_form_upd($row) {
	foreach($row as $key=>$r) $row[$key] = preg_replace("/[\r][\n]/", "##br##", $r);
	foreach($row as $key=>$r) $row[$key] = htmlspecialchars(preg_replace("/[\n]/", "##br##", $r));
	$string = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<form>
	<part part="text" head="{$row["id"]}:" />
	<part part="input" type="text" name="name[{$row["id"]}]" value="{$row["name"]}" size="20" br="1" />
	<part part="input" type="checkbox" name="nm[{$row["id"]}]" value="1" checked="{$row["nm"]}" tail="NM" />
	<part head="種族" part="input" type="text" name="category[{$row["id"]}]" value="{$row["category"]}" size="4" />
	<part head="画像" part="input" type="text" name="image[{$row["id"]}]" value="{$row["image"]}" size="4" br="1" />
	<part head="移動" part="input" type="text" name="walkspeed[{$row["id"]}]" value="{$row["walkspeed"]}" size="4" />
	<part head="攻速" part="input" type="text" name="delay[{$row["id"]}]" value="{$row["delay"]}" size="4" />
	<part head="索敵" part="input" type="text" name="search[{$row["id"]}]" value="{$row["search"]}" size="4" br="1" />
	<part part="text" head="追従/リンク" br="1" />
	<part part="textarea" name="follow[{$row["id"]}]" value="{$row["follow"]}" cols="48" rows="1" br="1" />
	<part part="textarea" name="link[{$row["id"]}]" value="{$row["link"]}" cols="48" rows="2" br="1" />
	<part head="Lv" part="input" type="text" name="minlevel[{$row["id"]}]" value="{$row["minlevel"]}" size="3" />
	<part head="～" part="input" type="text" name="maxlevel[{$row["id"]}]" value="{$row["maxlevel"]}" size="3" br="1" />
	<part part="text" head="再出現" br="1" />
	<part part="textarea" name="repop[{$row["id"]}]" value="{$row["repop"]}" cols="48" rows="2" br="1" />
	<part head="最大出現数" part="input" type="text" name="maxpop[{$row["id"]}]" value="{$row["maxpop"]}" size="4" br="1" />
	<part part="text" head="使用スキル" br="1" />
	<part part="textarea" name="skill[{$row["id"]}]" value="{$row["skill"]}" cols="48" rows="2" br="1" />
	<part part="text" head="ドロップアイテム" br="1" />
	<part part="textarea" name="dropitem[{$row["id"]}]" value="{$row["dropitem"]}" cols="48" rows="2" br="1" />
	<part part="text" head="備考" br="1" />
	<part part="textarea" name="note[{$row["id"]}]" value="{$row["note"]}" cols="48" rows="2" br="1" />
	<part part="input" type="checkbox" name="event[{$row["id"]}]" value="1" checked="{$row["event"]}" tail="イベント" />
	<part part="input" type="submit" name="submit_upd[{$row["id"]}]" value="変更" />
</form>
XML;
	return($string);
}
?>
