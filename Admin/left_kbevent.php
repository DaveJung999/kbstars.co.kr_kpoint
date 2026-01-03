<?php
//=======================================================
// 설	명 : 메인 첫 페이지 샘플(/index_basic.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/07/19
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/07/19 박선민 마지막 수정
//=======================================================
$HEADER=array(
//	'class'	=>"root", // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'priv'	=>"운영자,이벤트관리자", // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	=>1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'html_echo'	=>0, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
	'html_skin'	=>"" // html header 파일(/stpl/basic/index_$HEADER['html'].php 파일을 읽음)
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
	// $seHTTP_REFERER는 어디서 링크하여 왔는지 저장하고, 로그인하면서 로그에 남기고 삭제된다.
	if( !$_SESSION['seUserid'] && !$_SESSION['seHTTP_REFERER'] && $_SERVER['HTTP_REFERER'] && strpos($_SERVER['HTTP_REFERER'],$_SERVER["HTTP_HOST"])==false ) {
		$seHTTP_REFERER=$_SERVER['HTTP_REFERER'];
		session_register(seHTTP_REFERER);
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
//print_r($_SESSION);
?>
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
	background-color:#F2F3FA;
}
-->
</style>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<body leftmargin="0" topmargin="0">
<table width="200" border="0" cellpadding="1" cellspacing="1">
	<tr>
	<td height="5"></td>
	</tr>
	<tr>
	<td align="center"><strong><?=$_SESSION['seNickname'];?></strong>님 반갑습니다.</td>
	</tr>
	<tr>
	<td height="40" align="center"><a href="/sjoin/logout.php" target="_top"><img src="images/logout.gif" width="73" height="22" border="0"></a></td>
	</tr>
</table>
<table width="200" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td width="182" height="9" valign="top"><img src="images/left_01.gif" width="200" height="5"></td>
	</tr>
	<tr>
	<td height="30"><table width="200" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="A19B8A">
		<tr>
		<td height="22" bgcolor="#B0C5E4"><img src="images/menu_dot.gif" width="9" height="9" hspace="5" align="absmiddle">KB국민은행 서포터즈</td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td><table width="200" border="0" align="center" cellpadding="1" cellspacing="0" bgcolor="#D6E1F0">
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin/contents/list.php?db=supporters2011&skin=supporters_1&cateuid=1" target="mainFrame">어린이 서포터즈 </a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin/contents/list.php?db=supporters2011&skin=supporters_2&cateuid=2" target="mainFrame">학생 서포터즈 </a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin/contents/list.php?db=supporters2011&skin=supporters_3&cateuid=3" target="mainFrame">일반 서포터즈 </a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td height="30"><table width="200" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="A19B8A">
		<tr>
		<td height="22" bgcolor="#B0C5E4"><img src="images/menu_dot.gif" width="9" height="9" hspace="5" align="absmiddle">어린이 서포터즈 스포츠 캠프</td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td><table width="200" border="0" align="center" cellpadding="1" cellspacing="0" bgcolor="#D6E1F0">
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin/contents/list.php?db=2011kidscamp&skin=admin_kidscamp" target="mainFrame">어린이 서포터즈 스포츠 캠프</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td height="30"><table width="200" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="A19B8A">
		<tr>
		<td height="22" bgcolor="#B0C5E4"><img src="images/menu_dot.gif" width="9" height="9" hspace="5" align="absmiddle">3on3 길거리 농구대회</td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td><table width="200" border="0" align="center" cellpadding="1" cellspacing="0" bgcolor="#D6E1F0">
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin/contents/list.php?db=3on3&skin=admin_3on3&cateuid=1" target="mainFrame">초등부</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td><table width="200" border="0" align="center" cellpadding="1" cellspacing="0" bgcolor="#D6E1F0">
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin/contents/list.php?db=3on3&skin=admin_3on3&cateuid=2" target="mainFrame">중등부</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td height="30"><table width="200" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="A19B8A">
		<tr>
		<td height="22" bgcolor="#B0C5E4"><img src="images/menu_dot.gif" width="9" height="9" hspace="5" align="absmiddle">KB스타즈 농구단 팬미팅</td>
		</tr>
	</table></td>
	</tr>
	<tr>
	<td><table width="200" border="0" align="center" cellpadding="1" cellspacing="0" bgcolor="#D6E1F0">
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin/contents/list.php?db=2011fanmeet&skin=admin_fanmeet" target="mainFrame">KB스타즈 농구단 팬미팅</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
	</table></td>
	</tr>
</table>
