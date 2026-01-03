<?php
//=======================================================
// 설	명 : 회원 탈퇴 폼(joinout.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/03/28
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 05/03/28 박선민 마지막 수정
//=======================================================	
$HEADER=array(
		'priv' => '회원', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2' => 1, 
		'useSkin' => 1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	$dbinfo	= array(
			'skin' => 'basic',
			'html_type' => 'ht', 
			'html_skin' => '2015_d12'
		);
	
	$form_defalut = " action=$Action_domain/smember/profileok.php method=post>
					<input type=hidden name=mode value=joinout
					";

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile=basename(__FILE__,'.php').'.html';
if( !is_file($thisPath.'skin/'.$dbinfo['skin'].'/'.$skinfile) ) $dbinfo['skin']='basic';
$tpl = new phemplate($thisPath.'skin/'.$dbinfo['skin']); // 템플릿 시작
$tpl->set_file('html',$skinfile,TPL_BLOCK);

$tpl->set_var('form_defalut', $form_defalut);
$tpl->set_var('session', $_SESSION);

// 마무리
$tpl->echoHtml($dbinfo, $SITE, $thisUrl);
?>
