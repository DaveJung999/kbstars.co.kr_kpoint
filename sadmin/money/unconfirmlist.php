<?php
//=======================================================
// 설	명 : 관리자페이지 - 무통장입금처러(money/bankinput.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/14 박선민 마지막 수정
//=======================================================	
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
		usedb2	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $HTTP_HOST);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 관리자페이지 환경파일 읽어드림
	$sql = "select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC";
	$pageinfo	= db_arrayone($sql) or back("관리자페이지 환경파일을 읽을 수가 없습니다");

	// table
	$table_payment_unconfirm	= $SITE['th'] . "payment_unconfirm";

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
$result = db_query("SELECT * from {$table_payment}_unconfirm ORDER BY idate DESC");
$total = $no = db_count();
?>
<title> 미확인 입금자 리스트 </title>

<table border="0" cellspacing="1" cellpadding="3" bgcolor="#2f4f4f">
	<tr align="center" bgcolor="#CCCC99"> 
	<td width="30"><font size="2">No</font></td>
	<td width="80"><font size="2">입금날짜</font></td>
	<td><font size="2">입금인</font></td>
	<td width="60"><font size="2">금액</font></td>
	<td width="60"><font size="2">은행</font></td>
	<td><font size="2">처리</font></td>
	<td colspan="2"><font size="2">메뉴</font></td>
	</tr>
<?php
for($i=0; $i<$total; $i++)
{
	$list = db_array($result);

	
?>
		<tr bgcolor="#faf0e6"> 
		<td width="30" align="center"><font size="2"><?php echo $no ?></font></td>
		<td width="80" align="center"><font size="2"><?php echo date("Y. m. d", $list['idate']) ?></font></td>
		<td><font size="2"><?php echo $list['receiptor'] ?></font></td>
		<td bgcolor="#faf0e6" align="right" width="60"><font size="2"><?php echo number_format($list['price']) ?></font></td>
		<td bgcolor="#faf0e6" align="right" width="60"><font size="2"><a href="#" title="<?php echo $list['comment'] ?>"><?php echo $list['bank'] ?></a></font></td>
		<form action='certify.php' method='post'>
		<input type='hidden' name='mode' value='미확인처리'>
		<input type='hidden' name='unconfirm_uid' value='<?php echo $list['uid'] ?>'>
		<td> <font size="2"> 
			<input type="text" name="userid" size="10">
			<input type="submit" name="Submit" value="입금처리">
			</font></td>
		</form>
		<td><a href="/sadmin/myadmin230/tbl_change.php?primary_key=+uid+%3D+%27<?php echo {$list['uid']} ?>%27+&server=1&db={$SITE['th']}&table={$table_payment_unconfirm}&pos=0&sql_query=SELECT+%2A+FROM+{$table_payment_unconfirm}&goto=sql.php"><font size="2">수정</font></a></td>
		<td><a href="/sadmin/myadmin230/sql.php?sql_query=DELETE+FROM+{$table_payment_unconfirm}+WHERE++uid+%3D+%27<?php echo {$list['uid']} ?>%27+&server=1&db={$SITE['th']}&table={$table_payment_unconfirm}&pos=0&goto=sql.php%3Fserver%3D1%26db%3D{$table_payment_unconfirm}%26table%3Dservice_info%26pos%3D0%26sql_query%3DSELECT+%2A+FROM+{$table_payment_unconfirm}&zero_rows=%B7%B9%C4%DA%B5%E5%B0%A1+%BB%E8%C1%A6%B5%C7%BE%FA%BD%C0%B4%CF%B4%D9&goto=db_details.php"><font size="2">삭제</font></a></td>
		</tr>
<?php
	$no--;
}
?>
</table>
