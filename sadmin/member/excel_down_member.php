<?php
//=======================================================
// 설	명 : 심플리스트
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 05/01/25 박선민 마지막 수정
//=======================================================
$HEADER=array(
	'priv'		 => '쇼핑몰관리', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2'		 => 1, // DB 커넥션 사용
	'useApp'	 => 1, // cut_string()
	'useBoard2'	 => 1, // board2Count()
	'useSkin'	 => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');

header( "Content-type: application/vnd.ms-excel" ); 
header( "Content-Disposition: attachment; filename=mulkang_member.xls" ); 
header( "Content-Description: PHP4 Generated Data" );
?>
<HTML>
<HEAD>
<META http-equiv="Content-Type" content="text/html; charset=UTF-8">
<STYLE type="text/css">
body,table,tr,td { font-size: 9pt; font-family: gullim; line-height: 1.0;}
</STYLE>

<BODY LEFTMARGIN=0 TOPMARGIN=0>
<?php
print "<TABLE BORDER=1 CELLSPACING=1 CELLPADDING=1 WIDTH=100%>\r\n";
print "<TR ALIGN=CENTER>\r\n";
print "<TD width=80 height=16 BGCOLOR=GOLD><B>회원명</B></TD>\r\n";
print "<TD width=120 height=16 BGCOLOR=GOLD><B>아이디</B></TD>\r\n";
print "<TD width=120 height=16 BGCOLOR=GOLD><B>전화</B></TD>\r\n";
print "<TD width=120 height=16 BGCOLOR=GOLD><B>휴대폰</B></TD>\r\n";
print "<TD width=300 height=16 BGCOLOR=GOLD><B>주소</B></TD>\r\n";
print "<TD width=120 height=16 BGCOLOR=GOLD><B>누적금액</B></TD>\r\n";
print "</TR>\r\n";
$query = "SELECT * FROM new21_logon ORDER BY rdate DESC";
$result = db_query($query);
$total = db_count($result);
for($i=0; $i<$total; $i++)
{
	$list = db_array($result);

	// 포인트 가져오기
		$sql = "SELECT balance FROM new21_accountinfo where bid='{$list['uid']}'";
		$list['balance'] = db_resultone($sql,0,'balance');

	print "<TR>\r\n";
	print "<TD width=80 height=16>".stripslashes($list['name']) . "</TD>\r\n";
	print "<TD width=120 height=16>".$list['userid']."</TD>\r\n";
	print "<TD width=120 height=16>".stripslashes($list['tel']) . "</TD>\r\n";
	print "<TD width=120 height=16>".$list['hp']."</TD>\r\n";
	print "<TD width=300 height=16>".stripslashes($list['address']) . "</TD>\r\n";
	print "<TD width=120 height=16>".stripslashes($list['balance']) . "</TD>\r\n";
	print "</TR>\r\n";

}
print "</TABLE>\r\n";
print "</HTML>";
?>
</BODY>
</HTML>
<?php
exit;
?>