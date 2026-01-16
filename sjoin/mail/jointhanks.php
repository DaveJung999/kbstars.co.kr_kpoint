<?php
//=======================================================
// 설  명 : 메일 발송 : 회원 가입시(join_thankyou.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <? → <?php 변환
//=======================================================
$HEADER=array(
		'priv'		=>'', // 인증유무 (비회원,회원,운영자,서버관리자)
		'usedb2'		=>1, // DB 커넥션 사용
		'useClassSendmail'=>1, // mime_mail
		'useSkin'=>1, // 템플릿 사용
	);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
$thisPath	= dirname(__FILE__) .'/'; // 마지막이 '/'으로 끝나야함
$thisUrl	= '/sjoin/mail/'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
if( $qs['email'] && is_file($thisPath.'skin/basic/jointhanks.php') ) {
	$tpl = new phemplate('','remove_nonjs'); // 인클루드 실행이라 basic을 직접 입력함
	$tpl->set_file('html', $thisPath.'skin/basic/jointhanks.php');
	
	// 템플릿 마무리 할당
	$tpl->tie_var('post',$_POST);
	$tpl->tie_var('site',$SITE);
	$tpl->set_var('site.url',$_SERVER['HTTP_HOST']);

	$mail = new mime_mail;

	$mail->from		= $SITE['webmaster'];
	$mail->name		= $_SERVER['HTTP_HOST'];
	$mail->to		= $qs['email'];
	$mail->subject	= '회원 가입 메일';
	$val='\\1'.$thisUrl.'skin/'.$dbinfo['skin'].'/images/';
	$mail->body		= preg_replace('~([="\'])images/~', "\\1" . $val, $tpl->process('', 'html', TPL_OPTIONAL));
	$mail->html		= 1;
	$mail->send();
} // end if
?>