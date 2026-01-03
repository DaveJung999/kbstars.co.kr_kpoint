<?php
//=======================================================
// 설	명 : 결제 성공
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/05/07
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/12/03 박선민 마지막 수정
// 04/05/07 박선민 14L auth=0으로 바꿈
//=======================================================

$HEADER=array(
		'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);
//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 관리자에 입금 사실 휴대폰으로 알림
	/*
	if(strlen($_SESSION['seUserid'])>0 and strlen($SITE['hp'])>0){
		//include_once($_SERVER['DOCUMENT_ROOT'] . "/ssms/userfunctions.php");
		$qs_sms = array(
			callback =>  "", // 보내는사람 휴대폰(빈값이여도 발송됨)
			destination =>  $SITE['hp'], // 받는사람 휴대폰, 여러명이면 콤머로 구분
			body =>  "[결제알림]{$_SESSION['seUserid']}회원이 {$_REQUEST['bank']} {$_REQUEST['price']} 결제하였습니다", // 문자메세지
			reserve_mode =>  0, // 예약 전송이라면 1로
			reserve_date =>  "" // 예약 시간 : YYYY-MM-DD HH:MM:SS
			);
		userAdminSmsSend_ok($qs_sms,1);
	}
	*/

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
?>
<html>
<title></title>
<head>
<script language = 'JavaScript'>
	window.alert("결제가 성공적으로 끝났습니다.\n입금확인 페이지로 이동합니다.\n감사합니다 . 행복한 삶되시기 바랍니다.");
	if(opener)
	{
		opener.location='./inquiry.php';	
		//opener.parent.frames[0].location='./inquiry.php';
		//opener.location.reload();
		self.close();
	} else {
		parent.window.location='./inquiry.php';
	}
</script>
</head>
<body></body>
<?php
//go_url("./inquiry.php",0,"결제가 성공적으로 끝났습니다.\\n입금확인 페이지로 이동합니다.\\n감사합니다 . 행복삶되시기 바랍니다."); ?></html>