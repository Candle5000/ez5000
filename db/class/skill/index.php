<?php
//=====================================
// 戦闘/魔法スキルデータ 閲覧用
//=====================================
require_once("../../../class/mysql.php");
require_once("../../../class/guestdata.php");
require_once("../../../functions/template.php");
require_once("../../../functions/class.php");
$PAGE_ID = 61000;
$table = "bmskill";

$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database);
if(mysqli_connect_error()) {
	die("データベースの接続に失敗しました");
}

$title = "戦闘/属性スキル";
$data->select_all($table);
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>戦闘/属性スキル</h1>
<hr class="normal">
<div class="cnt">
<table border="1" id="bmskill">
<tr class="ocb"><td>Lv</td><td>S</td><td>A</td><td>B</td><td>C</td><td>D</td><td>E</td><td>F</td></tr>
<?php
while($row = $data->fetch()) {
	$lv = $row["id"];
	$S = $row["S"];
	$A = $row["A"];
	$B = $row["B"];
	$C = $row["C"];
	$D = $row["D"];
	$E = $row["E"];
	$F = $row["F"];
?>
<tr><td class="ocb"><?=$lv?></td><td><?=$S?></td><td><?=$A?></td><td><?=$B?></td><td><?=$C?></td><td><?=$D?></td><td><?=$E?></td><td><?=$F?></td></tr>
<?php
}
?>
</table>
</div>
<hr class="normal">
<ul id="footlink">
<li><a href="../"<?=mbi_ack(8)?>><?=mbi("8.")?>クラスデータ</a></li>
<li><a href="/db/"<?=mbi_ack(9)?>><?=mbi("9.")?>データベース</a></li>
<li><a href="/"<?=mbi_ack(0)?>><?=mbi("0.")?>トップページ</a></li>
</ul>
<?php
$data->select_id("accesscount", $PAGE_ID);
$c_data = $data->fetch();
pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
?>
</div>
</body>
</html>
