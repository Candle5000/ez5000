<?php
/*
 * Image Authentication
 * Version 1.01
 * 
 * License : MIT License
 * http://studio-key.com/
 * Copyright(c) STUDIO KEY Allright reserved.
 * 
 */

session_start();

/*
 * 画像認証に使う文字列
 * ひらがなと英数字
 */
  //$font_Text = 'あいうえおかきくけこさしすせとたちつてとなにぬねのはひふへほまみむめもやゆよわん';
  //$font_Text = 'あいうえおかきくけこさしすせとたちつてとなにぬねのはひふへほまみむめもやゆよわんABCDEFGHIJKLMNOPQRSTUVWXYZGabcdefghijklmnopqrstuvwxyz';
  $font_Text = 'あいうえおかきくけこさしすせとたちつてとなにぬねのはひふへほまみむめもやゆよわをんがぎぐげござじずぜぞだぢづでどばびぶべぼぱぴぷぺぽ';
  //$font_Text = 'ABCDEFGHIJKLMNOPQRSTUVWXYZGabcdefghijklmnopqrstuvwxyz0123456789';

/*
 * 何文字表示するか
 */
  $font_Len  = 3;
  
/*aaaa
 * 画像の縦横サイズ *文字数に応じて調整
 */
  $img_Width  = 120;
  $img_Height = 50;
  
/*
 * ラインを何本引くか
 * 最大50本程度。あまり多いと読めなくなります。
 */
  $line_Len = 25;
  
/*
 * 点を何個描写するか
 * 1万～3万程度。あまり多いと読めなくなります。
 */
  $ten_Len = 10000; 
  
  
// 設定ここまで *************************************************************
  
  
/*
 * 文字列を配列にする
 */
  $result    = preg_split("//u", $font_Text, -1, PREG_SPLIT_NO_EMPTY);
  $count     = count($result)-1;
  
  $bg1  = mt_rand(150, 255);
  $bg2  = mt_rand(150, 255);
  $bg3  = mt_rand(150, 255);

  $img        = ImageCreate($img_Width,$img_Height);
  $back_Color = ImageColorAllocate($img, $bg1, $bg2, $bg3);
  ImageFilledRectangle($img, 0,0, 300,100, $back_Color);

  $font_Path  = 'font/KodomoRounded.otf';
  $font_Color = ImageColorAllocate($img, 0, 0, 0);

  $red = imagecolorallocate($img, 0, 0, 0);
  
/*
 * 表示文字分だけランダムに文字を得る
 */
  $x = 10;
  $Texts = '';
  $_SESSION['ImageAuthentication'] = array();
  for($i=0; $i<$font_Len; $i++){
      
      $angle = mt_rand(-15, 20); //文字をランダムで斜めにする
      $size  = mt_rand(-4, 9); //フォントサイズをランダムに
    
      $key        = mt_rand(0, $count);
      $text       = mb_convert_encoding($result[$key], 'UTF-8', 'auto');
      $font_Size  = 24+$size;
      $font_Angle = $angle;
      $font_X     = $x;
      $font_Y     = 42;
      $x         += 30; //文字の左からの距離
      
      ImageTTFText($img, $font_Size, $font_Angle, $font_X, $font_Y, $font_Color, $font_Path, $text);
      $Texts .= $text; //テキストをくっつける
  }
  
  $_SESSION['ImageAuthentication'] = $Texts;

/*
 * ラインを描写
 */
  $y1        = -5; //開始Y座標は画像の少し上
  $y2        = $img_Height+20;
  $lineColor = imagecolorallocate($img, 0, 0, 0);
  
  for($i=0; $i<$line_Len; $i++){
    $x1 = mt_rand(10, 300);
    $x2 = mt_rand(10, 300);      
    imageline($img, $x1, $y1, $x2, $y2, $lineColor); //ラインの色
  }
  
/*
 * 点を描写
 */
  $tenColor = imagecolorallocate($img, 0, 0, 0);
  for($i=0; $i<$ten_Len; $i++){
    $ten_X = mt_rand(0, 300);
    $ten_Y = mt_rand(0, 300);
    imagesetpixel($img, $ten_X,$ten_Y, $tenColor);
  }

header('Content-type: image/png');
header('Content-disposition: attachment; filename=cap.png');
ImagePNG($img);

?>
