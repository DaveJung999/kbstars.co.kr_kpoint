<?php
//=======================================================
// 설  명 : 템플릿 샘플
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 06/02/27 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <? → <?php 변환
//=======================================================
$HEADER = array(
		'useSkin' => 1
	);

require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 넘오온값 체크
	$dbinfo = array( 
		html_type => 'ht',
		html_skin => 'join'
	);
	
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file('skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate('skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

// 템플릿 마무리 할당
$tpl->set_var('get',	$_GET);

// 마무리
$tpl->echoHtml($dbinfo, $SITE);
?>