<?php
//=======================================================
// 설  명 : 
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/14 박선민 마지막 수정
// 25/11/10 Gemini AI PHP 7+ 호환성 수정 (mysql_* -> db_*, 변수 중괄호 {}, 탭 변환, Short Tag)
//=======================================================	
$HEADER=array(
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
		'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']); // [!] FIX: $HTTP_HOST -> $_SERVER['HTTP_HOST']

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
	// [!] FIX: {$SITE['th']} -> {$SITE['th']}
	$sql = "select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC";
	$pageinfo	= db_arrayone($sql) or back("관리자페이지 환경파일을 읽을 수가 없습니다");

	// table
	$table_logon	= $SITE['th'] . "logon";
	$table_userinfo	= $SITE['th'] . "userinfo";
	$table_payment	= $SITE['th'] . "payment";

	// startdate와 enddate가 없다면
	if($_GET['startdate']=="") {
		$_GET['startdate']=$_GET['enddate']=date("Y-m-d");
	}

	if($_GET['sc_column']) {
		// [!] FIX: $_GET['sc_column'] 변수 중괄호 {} 적용
		$sc_column_s["{$_GET['sc_column']}"]="selected";
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<html>
<head>
<meta http-equiv="Content-Language" content="ko">
<meta http-equiv="Content-Type" content="text/html; charset=ks_c_5601-1987">
<title>청구 조회</title>
</head>
<script LANGUAGE="JavaScript" src="/scommon/js/inputcalendar.js" type="Text/JavaScript"></script>
<body>
	<table border="0" width="542" height="48">
	<tr bgcolor='#33CCFF'>
		<td width="542" height="22">
		<p align="center">청구 조회</td> 
	</tr> 
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="get"> 
	<input type=hidden name=mode value=input_ok>
	<tr bgcolor='#FBFCEF'>
		<td width="542" height="14"	align=center>
		<font size=2>
			청구기간 <INPUT TYPE=text name="startdate" id="startdate" ONCLICK="Calendar(this);" VALUE="<?php echo $_GET['startdate']; ?>" size='10' readonly> ~ 
			<INPUT TYPE=text name="enddate" id="enddate" ONCLICK="Calendar(this);" VALUE="<?php echo $_GET['enddate']; ?>" size='10' readonly><br>
			<select size="1" name="sc_column">
				<option value="bank" <?php echo $sc_column_s['bank']; ?>>입금방법</option>
				<option value="uid" <?php echo $sc_column_s['uid']; ?>>주문고유번호</option>
				<option value="bid" <?php echo $sc_column_s['bid']; ?>>회원고유번호</option>
				<option value="userid" <?php echo $sc_column_s['userid']; ?>>userid</option>
				<option value="ordertable" <?php echo $sc_column_s['ordertable']; ?>>ordertable</option>
				<option value="title" <?php echo $sc_column_s['title']; ?>>title</option>
				<option value="re_name" <?php echo $sc_column_s['re_name']; ?>>re_name</option>
				<option value="re_tel" <?php echo $sc_column_s['re_tel']; ?>>re_tel</option>
				<option value="re_address" <?php echo $sc_column_s['re_address']; ?>>re_address</option>
				<option value="comment" <?php echo $sc_column_s['comment']; ?>>comment</option>
			</select>
			검색단어<input type=text name='sc_string' value="<?php echo $_GET['sc_string']; ?>" size=15>
			<input type="submit" value="조회">(와이드카드 % 사용가능)
		</font>
		</td> 
	</tr> 
	</form> 
	</table> 

<?php

if($mode=="input_ok"){
	// [!] FIX: $table_payment, $_GET 변수 중괄호 {} 적용
	if( preg_match("/%/",$_GET['sc_string']) ) {
		if($_GET['sc_string']=="%" or $_GET['sc_string']=="%%") $sql_where = " 1 ";
		$sql_where	= " (`{$_GET['sc_column']}` like '{$_GET['sc_string']}') ";
	}
	else $sql_where	= " (`{$_GET['sc_column']}` = '{$_GET['sc_string']}') ";

	$sql="SELECT * from {$table_payment} WHERE {$sql_where} and (from_unixtime(rdate,'%Y-%m-%d')>='{$_GET['startdate']}' and from_unixtime(rdate,'%Y-%m-%d')<='{$_GET['enddate']}') ORDER BY rdate DESC";

	$rs=db_query($sql);
	$total=db_count($rs); // [!] FIX: mysql_num_rows -> db_count
	
	echo("
			<table border='0' >
			<tr bgcolor='#B6F5B9'>
				<td><font size=2>청구(입금)일자</td>
				<td><font size=2>결재방법</td>
				<td><font size=2>주문번호</td>
				<td><font size=2>금액</td>
				<td><font size=2>내용</td>
				<td><font size=2>회원명</td>
				<td><font size=2>회원휴대폰</td>
				<td>&nbsp;</td>
			</tr>
		");
	if($total) {
		$total_money=0;
		for($i=0;$i<$total;$i++) {
			$list=db_array($rs);
			if($list['rdate']) $list['rdate']	= date("y-m-d",$list['rdate']);
			$list['idate']	= ($list['idate']) ? date("y-m-d",$list['idate']) : "미입금";
			// [!] FIX: $table_logon, $table_userinfo, $list 변수 중괄호 {} 적용
			$list['logon']	= db_arrayone("SELECT * from {$table_logon} where uid='{$list['bid']}'");
			$list['userinfo']	= db_arrayone("select hp from {$table_userinfo} where bid='{$list['bid']}'");
			$total_money=$total_money + $list['price'];

			$list['price']	= number_format($list['price']);
	
			$goto = urlencode($_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']);
			// [!] FIX: $list, $table_payment 변수 중괄호 {} 적용 및 문자열 연결 오류 수정
			echo ("
				<tr bgcolor='#EEEEFD'>
				<td nowrap><font size=2>{$list['rdate']}({$list['idate']})</td>
				<td nowrap><font size=2>{$list['bank']}</td>
				<td nowrap><font size=2>{$list['uid']}</td>
				<td nowrap align=right><font size=2>{$list['price']}</td>
				<td nowrap><font size=2>[{$list['ordertable']}]<b>{$list['title']}</b></font></td>
				<td nowrap><font size=2>{$list['logon']['userid']}({$list['logon']['name']})</font></td>
				<td nowrap><font size=2><b>{$list['userinfo']['hp']}</b></font></td>
				<td nowrap><font size=2><A HREF='/sadmin/myadmin224/tbl_change.php?lang=ko&server=1&table={$table_payment}&pos=0&session_max_rows=30&disp_direction=horizontal&repeat_cells=100&dontlimitchars=&primary_key=+%60uid%60+%3D+%27{$list['uid']}%27&goto={$goto}'>수정</A> <A HREF='/sadmin/myadmin224/sql.php?sql_query=DELETE+FROM+{$table_payment}+WHERE+uid+%3D+%27{$list['uid']}%27+&server=1&db=ADMIN&table={$table_payment}&goto={$goto}' onclick=\"javascript: return confirm('정말 삭제하시겠습니다.');\">삭제</A> (<a href='../member/search.php?mode=payment&sc_column=logon.uid&sc_string={$list['bid']} target=_blank>{$list['userid']}</a>)</td>
				</tr>
			");
		}
		echo("<tr><td colspan=8>총 금액 : ". number_format($total_money) ."</td></tr>");
	}
	else {
		echo("<tr><td colspan=8>내역이 없습니다</td></tr>");
	}
	echo("
			</table>
	");
}
?>
</body>
</html>