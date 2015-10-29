<?php
require_once("/var/www/functions/template.php");

if(!isset($_GET["img"]) || !isset($_GET["size"])) toppage();

$url = "/var/www/img/bbs/".$_GET["img"];

// 出力する画像サイズの指定
$width = $_GET["size"];
if($width > 320) $width = 320;

// 元画像のファイルサイズを取得
try {
	$imageinfo = @getimagesize($url);
} catch(RuntimeException $e) {
	toppage();
}
$image_w = $imageinfo[0];
$image_h = $imageinfo[1];

//元画像の比率を計算し、高さを設定
$proportion = $image_w / $image_h;
$height = $width / $proportion;

//高さが幅より大きい場合は、高さを幅に合わせ、横幅を縮小
if($proportion < 1){
	$height = $width;
	$width = $width * $proportion;
}

// 画像インスタンスを生成
switch($imageinfo[2]) {
	case 1:
		$image = imagecreatefromgif($url);
		break;
	case 2:
		$image = imagecreatefromjpeg($url);
		break;
	case 3:
		$image = imagecreatefrompng($url);
		break;
	default:
		toppage();
		break;
}

// サイズを指定して、背景用画像を生成
$canvas = imagecreatetruecolor($width, $height);

// 背景画像に、画像をコピーする
imagecopyresampled($canvas,  // 背景画像
	$image,   // コピー元画像
	0,        // 背景画像の x 座標
	0,        // 背景画像の y 座標
	0,        // コピー元の x 座標
	0,        // コピー元の y 座標
	$width,   // 背景画像の幅
	$height,  // 背景画像の高さ
	$image_w, // コピー元画像ファイルの幅
	$image_h  // コピー元画像ファイルの高さ
);

// 画像を出力する
header('Content-type: image/png');
imagepng($canvas, null, 0, PNG_NO_FILTER);

// メモリを開放する
imagedestroy($canvas);

?>
