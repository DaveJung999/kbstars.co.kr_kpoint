<?php
//=======================================================
// 설	명 : 관리자페이지 첫페이지
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/12/15
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 03/12/15 박선민 마지막 수정
//=======================================================	
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
?>
<html>
<body>
 <ul>
	<li>
회원 DB가 logon 테이블에만 있고, userinfo에 없는 것</li>
 </ul>
 <table width="442" border="0">
	<tr bgcolor="#DFDFFF">
	<td width="106">ㅇ</td>
	<td width="106">&nbsp;</td>
	<td width="106">&nbsp;</td>
	<td width="106">&nbsp;</td>
	</tr>
	<tr bgcolor="#FFFFCA">
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	</tr>
 </table>
 <p>&nbsp;</p>
 <p><br>
 </p>
 <hr>
 <br>
<?php
$sql = "SELECT A.* FROM new21_logon A LEFT JOIN new21_userinfo AS B ON A.uid = B.bid
WHERE B.bid IS NULL";
$rs_list = db_query($sql);
while($list=db_array($rs_list){
	echo "$list['uid'],";
	
	
 $sql = "SELECT A.* FROM new21_userinfo A LEFT JOIN new21_logon AS B ON A.bid = B.uid
WHERE B.uid IS NULL";
$rs_list = db_query($sql);
while($list=db_array($rs_list){
	echo "$list['uid'],";
}
?>
</body>
</html>