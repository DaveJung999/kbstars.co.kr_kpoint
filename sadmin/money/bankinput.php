<?php
//=======================================================
// 설	명 : 관리자페이지 - 무통장입금처러(money/bankinput.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/10/14
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 03/10/14 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환
//=======================================================	
$HEADER=array(
	'priv'	 => 10, // 인증유무 (0:모두에게 허용, 숫자가 높을 수록 레벨업)
	'priv'		 => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
		usedb2	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
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
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// 관리자페이지 환경파일 읽어드림
	$sql = "select * from {$SITE['th']}admin_config where skin={$SITE['th']} or skin='basic' order by uid DESC";
	$pageinfo	= db_arrayone($sql) or back("관리자페이지 환경파일을 읽을 수가 없습니다");

	$list['bank']			= $_GET['bank'];
	$list['inputdate']	= $_GET['inputdate'] ? $_GET['inputdate'] : date('Y-m-d');
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<title> 통장입금 처리 </title>
<script LANGUAGE="JavaScript" src="/scommon/js/inputcalendar.js" type="Text/JavaScript"></script>
<form name="board" method="post" action="./bankcertify.php">
<table width="500" border=0 cellspacing='<?php echo $pageinfo['table_cellspacing']?>' cellpadding='<?php echo $pageinfo['table_cellpadding']?>' bgcolor='<?php echo $pageinfo['table_linecolor']?>'>
	<tr> 
		<td bgcolor='<?php echo $pageinfo['table_thcolor']?>' width="150"><b><font size="2" color="#333333">&nbsp;&nbsp;아이디 <font color="#FF6600">*</font></font></b></td>
		<td bgcolor='<?php echo $pageinfo['table_tdcolor']?>'><font size="2"> 
			<input type="text" name="userid"><br>
			혹은 이름 <input type="text" name="name"><br>
			혹은 휴대폰 <input type="text" name="hp">
			</font>
	 </td>
	</tr>
	<tr> 
		<td bgcolor="<?php echo $pageinfo['table_thcolor']?>" width="156"><b><font size="2" color="#333333">&nbsp;&nbsp;입금인 
		<font color="#FF6600">*</font> </font></b></td>
		<td width="329" bgcolor="<?php echo $pageinfo['table_tdcolor']?>"> <font size="2"> 
		<input type="text" name="receiptor">
		</font></td>
	</tr>
	<tr> 
		<td bgcolor="<?php echo $pageinfo['table_thcolor']?>" width="156"><b><font color="#333333">&nbsp;&nbsp;<font size="2">금액 
		</font></font><font size="2" color="#333333"><font color="#FF6600">*</font></font><font color="#333333"><font size="2"> 
		</font></font></b></td>
		<td width="329" bgcolor="<?php echo $pageinfo['table_tdcolor']?>"> <font size="2"> 
		<input type="text" name="price" size=15> 원</font></td>
	</tr>
	<tr> 
		<td bgcolor="<?php echo $pageinfo['table_thcolor']?>" width="156"><b><font color="#333333">&nbsp;&nbsp;<font size="2">입금방법
		</font></font><font size="2" color="#333333"><font color="#FF6600">*</font></font><font color="#333333"><font size="2"> 
		</font></font></b></td>
		<td width="329" bgcolor="<?php echo $pageinfo['table_tdcolor']?>"> <font size="2"> 
		<input type=text name='bank' size=20 value='<?php echo $list['bank']?>'> 예) 카드결제, 농협, 현금
		</font></td>
	</tr>
	<tr> 
		<td bgcolor="<?php echo $pageinfo['table_thcolor']?>" width="156"><b><font color="#333333">&nbsp;&nbsp;<font size="2">입금날짜 
		</font></font><font size="2" color="#333333"><font color="#FF6600">*</font></font><font color="#333333"><font size="2"> 
		</font></font></b></td>
		<td width="329" bgcolor="<?php echo $pageinfo['table_tdcolor']?>">
			<INPUT TYPE=text name="inputdate" id="inputdate" ONCLICK="Calendar(this);" VALUE="<?php echo $list['inputdate']?>" size='10' readonly>

		</td>
	</tr>
	<tr> 
		<td bgcolor="<?php echo $pageinfo['table_thcolor']?>" width="156">&nbsp;</td>
		<td width="329" bgcolor="<?php echo $pageinfo['table_tdcolor']?>"> 
		<input type="submit" name="mode" value="입금처리">
		</font> </td>
	</tr>
	</table>
</form>
