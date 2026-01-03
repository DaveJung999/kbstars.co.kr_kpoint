<?php
//=======================================================
// 설	 명 : 게시판 목록보기(list.php)
// 책임자 : 박선민 , 검수: 04/01/31
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/01/14 박선민 $list['enable_new'] 만듦
// 04/01/27 박선민 카테고리 개선
// 04/01/31 박선민 서치부분 개선
// 04/03/09 정대입 마지막 수정....	43L 삽입,	193L, 270L :$list['enable_new']="" 삽입
// 25/08/10 Gemini PHP 7 버전으로 수정
//=======================================================
$HEADER = array(
	'priv' => '운영자,이벤트관리자', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard2' => 1, // privAuth()
	'useApp' => 1
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");

header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");
header ("Pragma: no-cache"); // PHP 7에 맞춰 Pragma: cache 대신 no-cache로 변경
header ('Content-type: application/vnd.ms-excel; charset=UTF-8'); // MIME 타입과 UTF-8 인코딩 설정
header ("Content-Disposition: attachment; filename=KB_STARS_{$_GET['title']}_" . date("Ymdhis") . ".xls" );
header ("Content-Description: PHP/INTERBASE Generated Data" );

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= dirname(__FILE__);
$thisUrl	= "/Admin"; // 마지막 "/"이 빠져야함
?>
<html>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<body bgcolor=white>
<table width="100%" border=0 cellpadding=2 cellspacing=0>
	<tr>
		<td align="center"><strong>성명</strong></td>
		<td align="center"><strong>이메일</strong></td>
		<td align="center"><strong>카테고리</strong></td>
		<td align="center"><strong>제목</strong></td>
		<td align="center"><strong>내용</strong></td>
		<td align="center"><strong>학교</strong></td>
		<td align="center"><strong>학년</strong></td>
		<td align="center"><strong>반</strong></td>
		<td align="center"><strong>연락처</strong></td>
		<td align="center"><strong>기타</strong></td>
	</tr>
<?php

/////////////////////////////
// 게시판 맨 위에 무조건 공지글(type필드에 info인 것) 읽어오기
	$sql = "select userid, email, catetitle, title, content, data1, data2, data3, data4, data5 from new21_board2_{$_GET['db']} where 1"; //$_GET['sql']

	$rs_list_writeinfo = db_query($sql);
	$total_writeinfo = db_count($rs_list_writeinfo);

	for ($i = 0; $i < $total_writeinfo; $i++){
		$list = db_array($rs_list_writeinfo);
?>
	<tr>
		<td align="center"><?=htmlspecialchars($list['userid'])?></td>
		<td align="center"><?=htmlspecialchars($list['email'])?></td>
		<td align="center"><?=htmlspecialchars($list['catetitle'])?></td>
		<td align="center"><?=htmlspecialchars($list['title'])?></td>
		<td align="center"><?=htmlspecialchars(strip_tags($list['content']))?></td>
		<td align="center"><?=htmlspecialchars($list['data1'])?></td>
		<td align="center"><?=htmlspecialchars($list['data2'])?></td>
		<td align="center"><?=htmlspecialchars($list['data3'])?></td>
		<td align="center">&nbsp;<?=htmlspecialchars($list['data4'])?></td>
		<td align="center"><?=htmlspecialchars($list['data5'])?></td>
	</tr>
<?php
	}
?>
</table>
</body>
</html>