<?php
//=======================================================
// 설	명 : 게시판 목록보기(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/01/31
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 04/01/14 박선민 $list['enable_new'] 만듦
// 04/01/27 박선민 카테고리 개선
// 04/01/31 박선민 서치부분 개선
// 04/03/09 정대입 마지막 수정....	43L 삽입,	193L, 270L :$list['enable_new']="" 삽입
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard2' => 1, // privAuth()
	'useApp' => 1
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
/*header( "Content-type: application/vnd.ms-excel" ); 
header( "Content-Disposition: attachment; filename=airlee.xls" ); 
header( "Content-Description: PHP4 Generated Data" ); 
*/

header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header ("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
header ("Cache-Control: no-cache, must-revalidate");	
header ("Pragma: cache");	
header ('Content-type: application/x-msexcel');
header ("Content-Disposition: attachment; filename=airlee.xls" ); 
header ("Content-Description: PHP/INTERBASE Generated Data" );

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
$thisPath	= dirname(__FILE__);
$thisUrl	= "/Admin"; // 마지막 "/"이 빠져야함; ?>
<html> 
<body bgcolor=white> 
<table cellspacing=0 cellpadding=2 border=0> 
<?php
/////////////////////////////
// 게시판 맨 위에 무조건 공지글(type필드에 info인 것) 읽어오기
	$sql = "select userid, data1, data2, email, content from new21_board2_regtelemail";//$_GET['sql']
	$rs_list_writeinfo = db_query($sql);
	$total_writeinfo=db_count($rs_list_writeinfo);
	for($i=0;$i<$total_writeinfo;$i++){
		$list		= db_array($rs_list_writeinfo);
		$list_count = count($list);
		for($j=0;$j<$list_count;$j++){ 
?>
 
		<td bgcolor="#FFFF80">row<?php print $y; ?> col <?php print $x; ?> </td> 
<?php
		}
	} // end if 
?>
</table> 
</body> 
</html> 