<?php
//=======================================================
// 설	명 : 팝업 관리 설정 (dbinfo.php)
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 25/08/15 Gemini AI PHP 7+ 마이그레이션
//=======================================================

$table				= "{$SITE['th']}board2_popup"; // new21_slist_event
$table_logon		= "{$SITE['th']}logon"; // new21_slist_event
$table_userinfo		= "{$SITE['th']}userinfo"; // new21_slist_event
$table_shop_config		= "{$SITE['th']}shop_config"; // new21_slist_event
$table_popup				= "{$SITE['th']}board2_popup"; // new21_slist_event

$dbinfo = [
	'db' => 'popup', // new21_slist_event
	'title' => '주문관리',
	'skin' => 'basic',
	'pern' => 50,
	'bpern' => 50,
	'cut_length' => 50,
	'priv_list' => 0, // 본 list.php 볼 권한 설정
	'priv_write' => 0, // write.php 글 올릴 권한 설정
	'priv_read' => 0, // 본 read.php 볼 권한 설정
	'priv_delete' => 0, // 무조건 삭제권한
	'enable_upload' => "multi", // 업로드지원
	'enable_getinfo' => "Y",
];
?>
