<?php
//=====================================
// ABOUTページ
//=====================================
require_once("/var/www/class/mysql.php");
require_once("/var/www/class/guestdata.php");
require_once("/var/www/functions/template.php");
require_once("/var/www/functions/item.php");

$PAGE_ID = 10100;
$title = "このサイトについて";
//$user_file = "../../../../etc/mysql-user/user5000.ini";
$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database);
?>
<html>
<head>
<?=pagehead($title)?>
</head>
<body>
<div id="all">
<h1>このサイトについて</h1>
<hr class="normal">
<p>
このサイトは<a href="http://eternalzone.com">オンラインRPG エターナルゾーン</a>の攻略・交流サイトの開発版サイトです。現行サイトへはトップページから移動できます。<br />
<br />
このサイトは管理人の自宅に設置されたRaspberry Piで動いています。PHPのプログラムから自作していますので、機能の追加など、ある程度の要望には対応できます。非力ですので高い負荷をかける行為は勘弁してください。<br />
固定IPのサーバーではないため、たまに接続できない時間帯が発生します。ご了承ください。<br />
<br />
不具合等が見つかった場合には、お手数ですが下記までご連絡をお願いします。<br />
<br />
管理人：蝋燭<br />
連絡先：<a href="http://mbbs.tv/u/?id=kanrininda">私書箱</a><br />
</p>
<hr class="normal" />
<ul id="footlink">
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

