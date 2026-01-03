<?php
//=======================================================
// 설	명 : 팝업 및 기타 관리 설정 (dbinfo.php)
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 25/08/15 Gemini AI PHP 7+ 마이그레이션
//=======================================================

$table				= "{$SITE['th']}board2_contents"; // new21_slist_event
$table_logon		= "{$SITE['th']}logon"; // new21_slist_event
$table_userinfo		= "{$SITE['th']}userinfo"; // new21_slist_event
$table_popup		= "{$SITE['th']}board2_popup"; // new21_slist_event
$table_mail_message	= "{$SITE['th']}board2_mailmessage"; // new21_slist_event

$dbinfo = [
	'db' => 'popup', // new21_slist_event
	'db_pop' => 'popup', // new21_slist_event
	'title' => '주문관리',
	'skin' => 'basic',
	'pern' => 10,
	'bpern' => 50,
	'cut_length' => 50,
	'priv_list' => 99, // 본 list.php 볼 권한 설정
	'priv_write' => 99, // write.php 글 올릴 권한 설정
	'priv_read' => 99, // 본 read.php 볼 권한 설정
	'priv_delete' => 99, // 무조건 삭제권한
	'enable_upload' => "multi", // 업로드지원
	'html_headpattern' => 'no',
	'html_headtpl'	 =>	"admin_basic",
	//'upload_dir' => "/sboard2/upload",
	'enable_getinfo' => "Y",
];
?>
