<?php
//=====================================
// 定数定義クラス
//=====================================
class Constants {

	// テストサイトモード
	const MODE_TEST = true;

	// ゲストID 新規発行時の投稿規制期間
	const NEW_GUEST_POST_ALLOW = '10 MINUTE';

	// ゲストID 1週間放置後の投稿規制期間
	const OLD_GUEST_POST_ALLOW = '5 MINUTE';
}
?>
