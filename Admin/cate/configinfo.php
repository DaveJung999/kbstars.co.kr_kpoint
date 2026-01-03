<?php
//=======================================================
// 설	명 : 게시판 정보 설정 (dbinfo.php)
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 25/08/15 Gemini AI PHP 7+ 마이그레이션
//=======================================================
$dbinfo = [
	'table' => "{$SITE['th']}board2info",
	'skin' => 'basic',	// default: basic
	'pern' => -1,		// default: 15
	'page_pern' => 5,		// default: 5
	'cut_length' => 40,		// default: 40
	'bid' => 0,		// default: 0
	'gid' => 0,		// default: 0
	'priv_list' => 1,		// default: 1
	'priv_write' => 1,		// default: 1
	'priv_read' => 1,		// default: 1
	'priv_delete' => 99,		// default: 99
	'goto_write' => 'list.php',		// default: list.php
	'goto_modify' => 'list.php',		// 빈값은 수정후 보기(read.php) default: list.php
	'goto_delete' => 'list.php',		// default: list.php
	'enable_upload' => 'N',				// default: list.php
	'html_headpattern' => 'N',		// ht, h, t, N, no
	'html_headtpl' => 'basic'		// default: list.php
];
?>
