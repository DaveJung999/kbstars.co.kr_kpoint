<?php
//=======================================================
// 설	명 : 메인 첫 페이지 샘플(/index_basic.php)
// 책임자 : 박선민 (), 검수: 04/07/19
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/07/19 박선민 마지막 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환, session_register() → $_SESSION[] 직접 할당
//=======================================================
$HEADER=array(
	'priv'	=>"운영자,사진관리자,경기관리자,이벤트관리자,뉴스관리자,주니어관리자,포인트관리자", // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
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
		$_SESSION['seHTTP_REFERER']=$_SERVER['HTTP_REFERER'];
	}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
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
	<td align="center"><strong><?php echo $_SESSION['seNickname'];?></strong>님 반갑습니다.</td>
	</tr>
	<tr>
	<td height="40" align="center"><a href="/sjoin/logout.php" target="_top"><img src="images/logout.gif" width="73" height="22" border="0"></a></td>
	</tr>
</table>
<table width="200" border="0" cellspacing="0" cellpadding="0">
	<tr> 
	<td width="182" height="9" valign="top"><img src="images/left_01.gif" width="200" height="5"></td>
	</tr>
<?php
	if($_SESSION['seUserid'] == 'kbgame' ) {
?>
	<tr> 
	<td height="30"> <table width="200" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="A19B8A">
		<tr> 
			<td height="22" bgcolor="#B0C5E4"><img src="images/menu_dot.gif" width="9" height="9" hspace="5" align="absmiddle">경기기록관리</td>
		</tr>
		</table></td>
	</tr>
	<tr> 
	<td align="center"><table width="200" border="0" align="center" cellpadding="1" cellspacing="0" bgcolor="#D6E1F0"><tr>
		<td>&nbsp;&nbsp;- <a href="/Admin_basketball/team/list.php" target="mainFrame">팀정보</a> </td>
		</tr>
		<tr>
		<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr>
		<td>&nbsp;&nbsp;- <a href="/Admin_basketball/season/list.php" target="mainFrame">시즌정보</a> </td>
		</tr>
		<tr>
		<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr>
		<td>&nbsp;&nbsp;- <a href="/Admin_basketball/player/list.php?team=<?php echo $GAMEINFO['tid']?>" target="mainFrame">선수정보</a> </td>
		</tr>
		<tr>
		<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr>
		<td>&nbsp;&nbsp;- <a href="/Admin_basketball/player_record/plist.php" target="mainFrame">선수종합기록</a> </td>
		</tr>
		<tr>
		<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin_basketball/game/list.php" target="mainFrame">경기정보</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin_basketball/record/list.php" target="mainFrame">한경기종합기록</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin_basketball/totalgame_result/list.php" target="mainFrame">레드윙스 종합기록</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr>
		<tr> 
			<td>&nbsp;&nbsp;- <a href="/Admin_basketball/rank/list.php" target="mainFrame">시즌팀순위</a></td>
		</tr>
		<tr> 
			<td height='1' bgcolor='#cecece'></td>
		</tr></table></td>
	</tr>
<?php
 }
?>
</table>
