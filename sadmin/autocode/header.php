<?php
//=======================================================
// 설	명 :	
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/12/21 
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/12/21	처음 제작 
//=======================================================
$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//=================================================== 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>무제 문서</title>
<style type="text/css">
<!--
input {
	height: 12px;
	line-height: 12px;
}
body {
	font-size: 12px;
}
-->
</style>
</head>

<body>
<form method="post">
	
 <p>해더파일</p>
	<p>&lt;?<br>
	//=======================================================<br>
	// 설 명 : <input name="input[]" type="text" id="input[]" size="40">
	<br>
	// 책임자 : <input name="input[]" type="text" id="input[]" value="박선민 (sponsor@new21.com)" size="30">
	// 검수: <input name="input[]" type="text" id="input[]" value="<?php echo date('y/m/d'); ?>" size="8">
	<br>
	// Project: sitePHPbasic<br>
	// ChangeLog<br>
	// DATE 수정인 수정 내용<br>
	// -------- ------ --------------------------------------<br>
	// 
	<input name="input[]" type="text" id="input[]" value="<?php echo date('y/m/d'); ?>" size="8"> 
	<input name="input[]" type="text" id="input[]" value="<?php echo $_SESSION['seName'] ; ?>" size="8"> 
	<input name="input[]" type="text" id="input[]" value="처음 제작" size="30">
	<br>
	//=======================================================<br>
$HEADER=array(<br>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="private" type="checkbox" id="private" value="checkbox">
	'private'=&gt; 1, // HTTP 브라우저 cache<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="priv" type="checkbox" id="priv" value="checkbox" checked>
	'priv' =&gt;
	'<input name="priv_value" type="text" id="priv_value" value="회원" size="8">'
	, // 인증유무 (비회원,회원,운영자,서버관리자)
	<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	<input name="usedb" type="checkbox" id="usedb" value="checkbox" checked>
	'usedb2' =&gt;1, // DB 커넥션 사용 (0:미사용, 1:사용)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	<input name="useSkin" type="checkbox" id="useSkin" value="checkbox">
	'useSkin'=&gt; 1, // 템플릿 사용 <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input name="useApp" type="checkbox" id="useApp" value="checkbox">	
'useApp' =&gt;1, // <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;	<input name="useBoard2" type="checkbox" id="useBoard2" value="checkbox">
	'useBoard2'=&gt;1, // <br>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="useCheck" type="checkbox" id="useCheck" value="checkbox">
	'useCheck' =&gt;1, // <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="useImage" type="checkbox" id="useImage" value="checkbox">
	'useImage'=&gt; 1, // <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="useClassSendmail" type="checkbox" id="useClassSendmail" value="checkbox">
	'useClassSendmail' =&gt;1, // <br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="usedbLong" type="checkbox" id="usedbLong" value="checkbox">
	'usedbLong'=&gt;1, // <br>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<input name="header" type="checkbox" id="header" value="checkbox">
'html_echo'=&gt; 
<input name="header_value" type="text" id="header_value" value="1" size="3">
, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input name="html" type="checkbox" id="html" value="checkbox">
'html_skin' =&gt;'
<input name="html_value" type="text" id="html_value" value="basic" size="8">
', // html header 파일(/sjoin/skin/index_$HEADER['html'].php 파일을 읽음)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input name="log" type="checkbox" id="log" value="checkbox">
'log' =&gt;'
<input name="log_value" type="text" id="log_value" value="page" size="8">
', // log_site 테이블에 지정한 키워드로 로그 남김<br>
	);<br>
require(&quot;$_SERVER['DOCUMENT_ROOT']/sinc/header.php&quot;);<br>
	//page_security(&quot;&quot;, $_SERVER['HTTP_HOST']);</p>
	<p>
	<input type="submit" name="Submit" value="코드 생성" style="height:auto ">
	</p>
</form>
<hr>
<textarea cols="80" rows="20" wrap="OFF">&lt;?
//=======================================================
// 설명 : <?php echo $_POST['input'][0] . "\n" ; ?>
// 책임자 : <?php echo $_POST['input'][1] ; ?>
// 검수: <?php echo $_POST['input'][2] . "\n" ; ?>
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
//<?php echo $_POST['input'][3] ; 
 echo $_POST['input'][4] ; 
 echo $_POST['input'][5] ; ?>
 
//=======================================================
$HEADER=array(
<?php
if(isset($_POST['usedbLong'])) 
	echo "	'usedbLong'\t => 1, // \n";
if(isset($_POST['private'])) 
	echo "	'private'\t => 1, // HTTP 브라우저 cache\n";
if(isset($_POST['priv'])) 
	echo "	'priv'\t\t => '{$_POST['priv_value']}', // 인증유무 (비회원,회원,운영자,서버관리자)\n";
if(isset($_POST['usedb2'])) 
	echo "	'usedb2'\t\t => 1, // DB 커넥션 사용 (0:미사용, 1:사용)\n";
if(isset($_POST['useApp'])) 
	echo "	'useApp'\t => 1, // \n";
if(isset($_POST['useCheck'])) 
	echo "	'useCheck'\t => 1, // \n";
if(isset($_POST['useBoard2'])) 
	echo "	'useBoard2'\t => 1, // \n";
if(isset($_POST['useImage'])) 
	echo "	'useImage'\t => 1, // \n";
if(isset($_POST['useClassSendmail'])) 
	echo "	'useClassSendmail' => 1, // \n";
if(isset($_POST['useSkin'])) 
	echo "	'useSkin' => 1, // 템플릿 사용\n";
if(isset($_POST['html_echo'])) 
	echo "	'html_echo'\t => {$_POST['html_echo_value']}, // html header, tail 삽입(tail은 파일 마지막에 echo \{$SITE['tail']})\n";
if(isset($_POST['html_skin'])) 
	echo "	'html_skin'\t\t => '{$_POST['html_skin_value']}', // html header 파일(/sjoin/skin/index_{$HEADER['html']}.php 파일을 읽음)\n";
if(isset($_POST['log'])) 
	echo "	'log'\t\t => '{$_POST['log_value']}', // log_site 테이블에 지정한 키워드로 로그 남김\n";
?>
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
//page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================
?&gt;</textarea>
</body>
</html>
