<?php
//=====================================
// ページテンプレート用関数
//=====================================

//----------------------------------------
// 定数定義クラスを読み込み
//----------------------------------------
require_once("/var/www/class/constants.php");

//----------------------------------------
// トップページにリダイレクト
//----------------------------------------
function toppage() {
	$url = "http://".$_SERVER["HTTP_HOST"];
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $url");
}

//----------------------------------------
// 同一ページにリダイレクト
//----------------------------------------
function selfpage() {
	$http = "http";
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') $http .= "s";
	$url = "$http://".$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"];
	header("HTTP/1.1 301 Moved Permanently");
	header("Pragma: no-cache");
	header("Location: $url");
	exit;
}

//----------------------------------------
// エラーメッセージ出力
//----------------------------------------
function print_error($errorMessage) {
	return('<div style="color:#F00;">'.$errorMessage.'</div>');
}

//----------------------------------------
// 端末種類取得
//----------------------------------------
function device_info() {
	$device = "";
	$ua = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "";
	$sphs = array(
		'iPhone',
		'iPod',
		'Android',
		'dream',
		'CUPCAKE',
		'blackberry',
		'webOS',
		'incognito',
		'webmate'
	);
	$tabs = array(
		'iPad',
		'Android'
	);
	$mbls = array(
		'DoCoMo',
		'KDDI',
		'DDIPOKET',
		'UP.Browser',
		'J-PHONE',
		'Vodafone',
		'SoftBank'
	);

	if(empty($device_info)) {
		foreach($tabs as $tab) {
			$str = "/".$tab."/i";
			if (preg_match($str,$ua)) {
				if ($str === '/Android/i') {
					if (!preg_match("/Mobile/i", $ua)) {
						//$device_info = 'tb'; 
						$device_info = 'sp';
					}
					else {
						$device_info = 'sp';
					}
				}
				else {
					//$device_info = 'tb';
					$device_info = 'sp';
				}
			}
		}
	}

	if(empty($device_info)) {
		foreach($sphs as $sp) {
			$str = "/".$sp."/i";
			if (preg_match($str,$ua)) {
				$device_info = 'sp';
			}
		}
	}

	if(empty($device_info)) {
		foreach($mbls as $mb) {
			$str = "/".$mb."/i";
			if (preg_match($str,$ua)) {
				$device_info = 'mb';
			}
		}
	}

	if(empty($device_info)) {
		$device_info = 'pc';
	} else if($device_info == 'mb') {
		// IPチェック
		$this_ip = $_SERVER["REMOTE_ADDR"];
		$is_mobile = false;
		include("mobile_ip_list.php");

		foreach($mobile_ip_list as $permit_ip){
			list($p_ip, $mask_bit) = explode("/", $permit_ip);
			$ip_long = ip2long($p_ip) >> (32 - $mask_bit);
			$p_ip_long = ip2long($this_ip) >> (32 - $mask_bit);
			if ($ip_long == $p_ip_long) {
				$is_mobile = true;
				break;
			}
		}

		if(!$is_mobile) $device_info = 'pc';
	}
	return $device_info;
}

//----------------------------------------
// auガラケー判定
//----------------------------------------
function is_au() {
	$ua = $_SERVER["HTTP_USER_AGENT"];
	if(device_info() == 'mb') {
		if(preg_match("/KDDI/i", $ua)) {
			return(1);
		}
	}
	return(0);
}

//----------------------------------------
// ヘッダー
//----------------------------------------
function pagehead($title) {
?>
<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="content-language" content="ja" />
<?php
if(device_info() == "sp") {
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<?php
}
?>
<link rel="stylesheet" href="/main.css" type="text/css">
<link rel="stylesheet" href="/main_<?=device_info()?>.css" type="text/css">
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-144743342-2"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'UA-144743342-2');
</script>
<script data-ad-client="ca-pub-5908052326802473" async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<title><?=$title?></title>
<?php
}

//----------------------------------------
// 管理ページヘッダー
//----------------------------------------
function admin_pagehead() {
?>
<meta http-equiv="Content-type" content="text/html; charset=utf-8">
<meta http-equiv="content-language" content="ja" />
<?php
if(device_info() == "sp") {
?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<?php
}
?>
<title>管理者用</title>
<?php
}

//----------------------------------------
// フィーチャーフォン用アクセスキー
//----------------------------------------
function mbi_ack($key) {
	if(device_info() == 'mb') {
		return(" accesskey=\"".$key."\"");
	}
}

//----------------------------------------
// フィーチャーフォン汎用表示
//----------------------------------------
function mbi($str) {
	if(device_info() == 'mb') {
		return($str);
	}
}

//----------------------------------------
// フッター
//----------------------------------------
function pagefoot($count) {
	if($count == -1) {
		$count_text = "アクセス数の読み込みに失敗しました<br />";
	} else if($count == 0) {
		$count_text = "";
	} else {
		$count_text = "AccsessCount : $count<br />";
	}
?>
<div id="footer">
<?=$count_text?>
Eternal Zone (C) Ateam Inc.<br />
Web Design By Candle
</div>
<?php
}

//----------------------------------------
// 管理用 JSでPOST送信して遷移
//----------------------------------------
function jsPostSend($target, $postArray) {
?>
<html>
<head>
<?=admin_pagehead()?>
</head>
<body>
<script type="text/javascript">
var form = document.createElement('form');
document.body.appendChild(form);
<?php
	foreach($postArray as $key => $post) {
?>
var input<?=$key?> = document.createElement('input');
input<?=$key?>.setAttribute('type', 'hidden');
input<?=$key?>.setAttribute('name', '<?=$post["name"]?>');
input<?=$key?>.setAttribute('value', '<?=$post["value"]?>');
form.appendChild(input<?=$key?>);
<?php
	}
?>
form.setAttribute('action', '<?=$target?>');
form.setAttribute('method', 'post');
form.submit();
</script>
</body>
</html>
<?php
}
