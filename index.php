<?php
//=======================================================
// 설  명 : 템플릿 샘플
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/11/20
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/11/20 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		=>'', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		=>1, // DB 커넥션 사용
	'useApp'	=>1, // cut_string()
	'useSkin'	=>1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
if(!$_SESSION['seUid'])
	$url = "/sjoin/login.php";
else
	$url = "/kpoint/klist.php";
	
	go_url($url);
if($_SERVER['REMOTE_ADDR'] == '210.95.187.19'){
	print_r($_SESSION);
}

?>
<style type="text/css">
<!--
.style1 {font-size: 16px}
-->
</style>
<!--<table width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
	<td align="center"><span class="style1">K-Point 관리자 페이지입니다.<br />
	  로그인이 필요합니다.<br />
	  <br />
	  <a href="/sjoin/login.php">&lt;로그인하기&gt;</a></span></td>
  </tr>
</table>-->
