<?php
//=======================================================
// 설	명 : 컨텐츠 관리 설정 (dbinfo.php)
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 25/08/15 Gemini AI PHP 7+ 마이그레이션
//=======================================================
$table				= "{$SITE['th']}board2_content"; // new21_slist_event

$dbinfo['db']		= "content"; // new21_slist_event
$dbinfo['title']	= "컨텐츠관리";
$dbinfo['skin']		= "basic";
$dbinfo['upload_dir']= "{$_SERVER['DOCUMENT_ROOT']}/h_images";
$dbinfo['pern']		= 100;
$dbinfo['cut_length']	= 50;
$dbinfo['priv_list']	= 20; // 본 list.php 볼 권한 설정
$dbinfo['priv_write']	= 20; // write.php 글 올릴 권한 설정
$dbinfo['priv_read']	= 20; // 본 read.php 볼 권한 설정
$dbinfo['priv_delete']= 99; // 무조건 삭제권한
$dbinfo['enable_upload']="multi"; // 업로드지원
$dbinfo['html_headpattern'] = "no";
?>
