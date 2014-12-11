<?php
//=====================================
// トップページ
//=====================================
require_once("./class/mysql.php");
require_once("./class/guestdata.php");
require_once("./functions/template.php");
require_once("./functions/item.php");

$PAGE_ID = 10000;
$title = "EZ5000テストサイト";
//$user_file = "../../../../etc/mysql-user/user5000.ini";
$user_file = "/etc/mysql-user/user5000.ini";
if($fp_user = fopen($user_file, "r")) {
	$userName = rtrim(fgets($fp_user));
	$password = rtrim(fgets($fp_user));
	$database = rtrim(fgets($fp_user));
} else {
	die("接続設定の読み込みに失敗しました");
}
$data = new GuestData($userName, $password, $database, 0);
$count = $data->top_count();
?>
<html>
<head>
<?pagehead($title)?>
<meta name="robots" content="index" />
<meta name="Keywords" content="オンラインRPG,MMORPG,エターナルゾーン,攻略情報,データベース,蛭注意,EZ5000,5分戦闘,五千" />
<meta name="description" content="【オンラインRPG】エターナルゾーンの攻略サイト開発スペース。テスト公開中。" />
<meta name="author" content="Candle" />
</head>
<body>
<div id="all">
<h1>EZ5000テストサイト</h1>
<hr class="normal">
<ul id="linklist">
<li><a href="./about/"<?=mbi_ack(1)?>><?=mbi("1.")?>このサイトについて</a></li>
<li><a href="./database/"<?=mbi_ack(2)?>><?=mbi("2.")?>データベース</a></li>
</ul>
<hr class="normal">
<div class="cnt">
<table id="topcount">
<tr><td class="lft" width="60%">今日の冒険者数</td><td class="rgt" width="40%"><?=$count['t']?> 人</td></tr>
<tr><td class="lft">昨日の冒険者数</td><td class="rgt"><?=$count['y']?> 人</td></tr>
<tr><td class="lft">今月の冒険者数</td><td class="rgt"><?=$count['m']?> 人</td></tr>
</table>
</div>
<hr class="normal">
<div id="infobox">
	<div id="date">2014/11/10</div>
	<ul id="info">
		<li id="boxtitle">更新のお知らせ</li>
		<li>スキルデータをテスト公開</li>
		<li>中身は順次更新していきます。</li>
	</ul>
</div>
<div id="infobox">
	<div id="date">2014/10/29</div>
	<ul id="info">
		<li id="boxtitle">検索機能の不具合修正</li>
		<li>auフィーチャーフォンでアイテムデータの検索機能が正常に使用できない不具合を修正</li>
		<li>一部ページデザインを修正</li>
	</ul>
</div>
<div id="infobox">
	<div id="date">2014/10/26</div>
	<ul id="info">
		<li id="boxtitle">新機能のテスト実装</li>
		<li>アイテムデータに検索フォームをテスト実装</li>
		<li>スマートフォン閲覧時のリンクの幅を調整</li>
	</ul>
</div>
<div id="infobox">
	<div id="date">2014/10/23</div>
	<ul id="info">
		<li id="boxtitle">更新とお知らせ</li>
		<li>トップページの日毎のアクセスカウンターを実装</li>
		<li><span class="nm">10/24 11:00～11:30</span>の間、<span class="nm">サーバーメンテナンス</span>を行います。メンテナンス中はサイトへのアクセスができません。</li>
		<li>追記:10/24 11:15 メンテナンスは終了しました。ご協力いただきありがとうございました。</li>
	</ul>
</div>
<div id="infobox">
	<div id="date">2014/10/20</div>
	<ul id="info">
		<li id="boxtitle">フィーチャーフォン向け更新</li>
		<li>フィーチャーフォン向けのデザインを変更</li>
		<li>アクセスキー対応化</li>
		<li>全体のデザインの不備を修正</li>
		<li>その他細かい点の修正</li>
	</ul>
</div>
<div id="infobox">
	<div id="date">2014/10/17</div>
	<ul id="info">
		<li id="boxtitle">デザインの更新</li>
		<li>スマートフォンとタブレット向けのデザインを変更</li>
		<li>その他細かいデザインを修正</li>
	</ul>
</div>
<div id="infobox">
	<div id="date">2014/09/18</div>
	<ul id="info">
		<li id="boxtitle">色々更新</li>
		<li>クラスデータをテスト公開</li>
		<li>全ページのアクセスカウントをデータベースに変更</li>
		<li>トップページとデータベースのトップを追加</li>
		<li>テストサイトの情報は今後こちらに掲載します</li>
	</ul>
</div>
<ul id="footlink">
<li><a href="http://5000.sameha.org/">本家5000に帰る</a></li>
</ul>
<?
$data->select_id("accesscount", $PAGE_ID);
$c_data = $data->fetch();
pagefoot($data->access_count("accesscount", $PAGE_ID, $c_data["count"]));
?>
</div>
</body>
</html>

