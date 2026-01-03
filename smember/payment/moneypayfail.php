<?php
//=======================================================
// 설	명 : 결제 실패
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/10/25 박선민 마지막 수정
//=======================================================
/*
$HEADER=array(
		'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
		'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);
*/
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
echo ("
<script language = 'JavaScript'>
	window.alert(\"결제에 실패하였습니다.\\n인터넷요금결제페이지로 이동합니다.\\n다시 시도하시기 바랍니다.\");
	if(opener)
	{
		opener.location='./';	
		//opener.location.reload();
		self.close();
	} else {
		parent.window.location='./';
	}
</script>
");

//go_url("./",0,"결제에 실패하였습니다.\\n인터넷요금결제페이지로 이동합니다.\\n다시 시도하시기 바랍니다."); ?>
