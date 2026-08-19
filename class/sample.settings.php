<?php
//=====================================
// 設定値用定数定義クラス
//=====================================
class Settings {

	// ゲストID 新規発行時の投稿規制期間
	const NEW_GUEST_POST_ALLOW = '1 DAY';

	// ゲストID 1週間放置後の投稿規制期間
	const OLD_GUEST_POST_ALLOW = '4 HOUR';

	// Google Analytics ID
	const GOOGLE_ANALYTICS_ID = '';

	// Discord連携用 Webhook URL
	const WEBHOOK_URL = '';

	// Discord連携用 通知先ユーザーID
	const DISCORD_USER_ID = '';
}
?>
