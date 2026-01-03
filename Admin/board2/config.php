<?php
//=======================================================
// 설	명 : 심플리스트 - 환경설정파일
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/04/13
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/04/13 박선민 마지막 수정
//=======================================================

$dbinfo	= array(
	// 심플리스트 제목
	'title' => '게시판2 종합관리',
	
	// table
	'table' =>	$SITE['th'].'board2info',
	
	// 스킨설정
	'skin' => 'basic',
	'html_type' => 'no', // ht, h, t, no, N
	'html_skin' => 'basic',
	'html_head' => '',
	'html_tail' => '',
	
	// 권한설정
	'bid' => 1, // 게시판 관리자 uid
	'gid' => 0, // 그룹 gid
	'priv_list' => '운영자',
	'priv_write' => '운영자',
	'priv_read' => '운영자',
	'priv_modify' => 'root',
	'priv_delete' => 'root',
	
	// 기능설정 - 게시판기본
	'pern' => 40, // 게시물 수
	'page_pern' => 5,	// 페이지블럭 수
	'cut_length' => 40, // 제목 몇byte로 자를 것인지
	
	// 기능설정 - 기타
	'enable_userid' => 'userid', // userid 필드에 userid/name/nickname 중 어떤 값을 넣을지
	'default_docu_type' => 'text', // 디폴트 본문 형식 (html,text)
	'default_title' => '',
	'default_content' => '',
	
					
	// SQL문 기본값
	'orderby' => ' db ', // order by .. . 기본값
	
	// ok 처리하고 goto
	'goto_write' => 'list.php',
	'goto_modify' => 'list.php',
	'goto_delete' => 'list.php',
); 

$table = $dbinfo['table'];
?>
