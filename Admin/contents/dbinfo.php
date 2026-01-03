<?php
//=======================================================
// 설	명 : 컨텐츠 관리 2016 설정 (dbinfo.php)
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 25/08/15 Gemini AI PHP 7+ 마이그레이션
//=======================================================

$table				= "{$SITE['th']}board2_contents_2016";

$dbinfo = [
	'db' => 'contents_2016',
	'title' => '컨텐츠관리',
	'skin' => 'basic',
	'upload_dir' => "{$_SERVER['DOCUMENT_ROOT']}/images/upload",
	'pern' => 100,
	'cut_length' => 50,
	'priv_list' => 99, // list.php 볼 권한 설정
	'priv_write' => 99, // write.php 글 올릴 권한 설정
	'priv_read' => 99, // read.php 볼 권한 설정
	'priv_delete' => 99, // 무조건 삭제권한
	'enable_upload' => "multi", // 업로드지원
	'html_headpattern' => "no",
];
?>
