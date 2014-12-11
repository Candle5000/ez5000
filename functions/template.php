<?php
//=====================================
// ページテンプレート用関数
//=====================================

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
	$url = "http://".$_SERVER["HTTP_HOST"].$_SERVER["PHP_SELF"];
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $url");
}

//----------------------------------------
// 端末種類取得
//----------------------------------------
function device_info() {
	$device = "";
	$ua = $_SERVER["HTTP_USER_AGENT"];
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
						$device_info = 'tb'; 
					}
					else {
						$device_info = 'sp';
					}
				}
				else {
//					$device_info = 'tb';
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
<title><?=$title?></title>
<?php
}

//----------------------------------------
// アクセスカウンター
//----------------------------------------
/*function counter($countfile) {
	$fp = @fopen($countfile, "r");
	if($fp) {
		flock($fp, LOCK_EX);
		$count = fgets($fp);
		flock($fp, LOCK_UN);
		fclose($fp);
	} else {
		$count = 0;
	}
	
	$fp = @fopen($countfile, "w");
	if($fp) {
		$count++;
		flock($fp, LOCK_EX);
		fputs($fp, $count);
		flock($fp, LOCK_UN);
		fclose($fp);
		return($count);
	} else {
		return(-1);
	}
}*/

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
		$count_text = "アクセス数の読み込みに失敗しました";
	} else {
		$count_text = "AccsessCount : $count";
	}
?>
<div id="footer">
<?=$count_text?><br />
Eternal Zone (C) Ateam Inc.<br />
Web Design By Candle
<div>
<?php
}
