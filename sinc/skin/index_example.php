<?
//=======================================================
// 설  명 : 사이트의 HTML 해더와 테일부분 예시(index_example.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/01/29
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 05/01/29 박선민 마지막 수정
//=======================================================
/*
<사이트 전체스킨 만드는 법>
1. 사이트의 반복되는 해더와 테일 부분을 html로 만든 이후,
본문에 들어갈 자리에 {{BODY}} 를 넣습니다.
2. 스킨의 맨 위부분에 ob_start();인 php 소스 한줄을 넣습니다.
3. 스킨의 맨 아래에 $body=ob_get_contents();부터 시작한 10줄의 php 소스를 넣습니다.
4. /skin 드렉토리 밑에 index_????.php 형태로 저장합니다..
????는 영문자로시작하여 영문자숫자로 구성되어야 하며, ????이 앞으로 사용할 사이트 스킨 이름입니다.
*/
ob_start(); // 버퍼링 시작
// 여기까지 복사하여 제작한 HTML의 맨 위에 넣으면 됨
?>
<html>
<head>
<title>
<?=$SITE['title']?>
</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="/scommon/basic.css" rel="stylesheet" type="text/css">
</head>
<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<table width="750" border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td height="50" valign="top" background="/scommon/images/main/header_bg.gif">
		<table width="750" border="0" cellspacing="0" cellpadding="0">
		  <tr>
			<td width="178" height="44">&nbsp;</td>
			<td width="572" valign="bottom"><table width="440" border="0" align="right" cellpadding="0" cellspacing="0">
				<tr>
				  <td width="106"><a href="/sboard2/list.php?db=gonggi"><img src="/scommon/images/main/menu_notice.gif" width="53" height="16" border="0"></a></td>
				  <td width="80"><a href="/sboard2/list.php?db=bug"><img src="/scommon/images/main/menu_board.gif" width="40" height="16" border="0"></a></td>
				  <td width="78"><a href="/sshop2/list.php?db=gift"><img src="/scommon/images/main/menu_mall.gif" width="39" height="16" border="0"></a></td>
				  <td width="131"><a href="/sboard2/list.php?db=bug"><img src="/scommon/images/main/menu_bugreport.gif" width="95" height="16" border="0"></a></td>
				  <td width="65"><a href="/sjoin/"><img src="/scommon/images/main/menu_join.gif" width="56" height="16" border="0"></a></td>
				</tr>
			  </table></td>
		  </tr>
		  <tr>
			<td><a href="/"><img src="/scommon/images/main/sitephp.gif" width="178" height="24" border="0"></a></td>
			<td>
				<!-- START: 인증부분 -->
				<? if($_SESSION['seUid'] && $_SESSION['seUserid']) { //로그인이 되어 있으면 ?>
					  <font size=2 color=#3399FF>
					  <?=$_SESSION['seName']?>
					  님 방갑습니다 부~자 되세요 (<a href="/sjoin/logout.php">로그아웃</a>) </font>
				<? } else { //로그인이 되어있지 않으면 ?>
						<script language="JavaScript">
						<!--
						function headerlogin(theform) {
							theform.goto.value = document.URL;
							theform.submit();
						}
						-->
						</script>			
					  <form action="/sjoin/login.php" onSubmit="headerlogin(this)" method="post"  style='border:0px;margin:0px;padding:0px'>
						<input type="hidden" name="mode" value="login">
						<input type="hidden" name="goto" value="">
						<table width="340" border="0" align="right" cellpadding="0" cellspacing="0">
						  <tr>
							<td width="38"><img src="/scommon/images/main/header_id.gif" width="36" height="15"></td>
							<td width="111"><input type="text" name="userid" size="15"></td>
							<td width="51"><img src="/scommon/images/main/header_pw.gif" width="47" height="15"></td>
							<td width="112"><input type="password" name="passwd" size="15"></td>
							<td width="2">&nbsp;</td>
							<td width="26"><input type="image" src="/scommon/images/main/header_go.gif" width="22" height="21"></td>
						  </tr>
						</table>
					  </form>
				<? } // end if ?>
				<!-- END: 인증부분 -->
			</td>
		  </tr>
		</table>
	 </td>
  </tr>
</table>
<table width="750" border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td height="600" valign="top">
<!-- START: BODY -->
{{BODY}}
<!-- END: BODY -->
	</td>
  </tr>
</table>
<table width="750" border="0" cellspacing="0" cellpadding="0">
  <tr>
	<td><div align="center"> <font size="2"> <img src="/scommon/images/main/tale.gif" width="750" height="30" border="0" usemap="#spbmaintail"></font></div></td>
  </tr>
</table>
<map name="spbmaintail" id="spbmaintail">
  <area shape="rect" coords="623,4,743,25" href="mailto:sponsor@new21.com">
</map>
</body>
</html>
<?
	// 여기부터 끝까지 복사하여 제작한 사이트 스킨 마지막에 넣으면 됨
	$body=ob_get_contents(); // 버퍼링된 내용을 변수로 받음
	ob_end_clean(); // 버퍼링비움
	$aBody = explode('{{BODY}}',$body,2);
	if($HEADER['html_echo']==1) echo $aBody[0];
	else $SITE['head'] = $aBody[0];
	$SITE['tail'] = $aBody[1];
	unset($body);unset($aBody);
?>