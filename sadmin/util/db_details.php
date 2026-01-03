<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ko" lang="ko" dir="ltr">

<head>
<title>phpMyAdmin</title>
<style type="text/css">
<!--
body		  {font-family: 굴림, Arial, sans-serif; font-size: small; color: #000000}
pre, tt		{font-size: small}
th			{font-family: 굴림, Arial, sans-serif; font-size: small; font-weight: bold; background-color: #D3DCE3}
td			{font-family: 굴림, Arial, sans-serif; font-size: small}
form		  {font-family: 굴림, Arial, sans-serif; font-size: small}
input		 {font-family: 굴림, Arial, sans-serif; font-size: small; color: #000000}
select		{font-family: 굴림, Arial, sans-serif; font-size: small; color: #000000}
textarea	  {font-family: 굴림, Arial, sans-serif; font-size: small; color: #000000}
h1			{font-family: 굴림, Arial, sans-serif; font-size: 12pt; font-weight: bold}
A:link		{font-family: 굴림, Arial, sans-serif; font-size: small; text-decoration: none; color: #0000ff}
A:visited	 {font-family: 굴림, Arial, sans-serif; font-size: small; text-decoration: none; color: #0000ff}
A:hover		{font-family: 굴림, Arial, sans-serif; font-size: small; text-decoration: underline; color: #FF0000}
A:link.nav	{font-family: 굴림, Arial, sans-serif; color: #000000}
A:visited.nav {font-family: 굴림, Arial, sans-serif; color: #000000}
A:hover.nav	{font-family: 굴림, Arial, sans-serif; color: #FF0000}
.nav		  {font-family: 굴림, Arial, sans-serif; color: #000000}
//-->
</style>

<script type="text/javascript" language="javascript">
<!--
// Updates the title of the frameset if possible (ns4 does not allow this)
if (typeof(parent.document.title) == 'string') {
	parent.document.title = 'user_cs 가 실행중입니다. localhost - phpMyAdmin 2.2.4';
}

// js form validation stuff
var errorMsg0	= 'Missing value in the form !';
var errorMsg1	= 'This is not a number!';
var errorMsg2	= ' is not a valid row number!';
var noDropDbMsg = '"DROP DATABASE" statements are disabled.';
var confirmMsg  = '정말로 다음을 실행하시겠습니까? ';
//-->
</script>
<script src="libraries/functions.js" type="text/javascript" language="javascript"></script>
	
</head>


<body bgcolor="#F5F5F5" background="images/bkg.gif">


<!-- DATABASE WORK -->
<ul>
	<!-- Printable view of a table -->
	<li>
		<div style="margin-bottom: 10px"><a href="db_printview.php?lang=ko&amp;server=1&amp;db=user_cs&amp;goto=db_details.php">인쇄용 보기</a></div>
	</li>
	
	<!-- Query box, sql file loader and bookmark support -->
	<li>
		<a name="querybox"></a>
		<form method="get" action="http://<?=$HTTP_HOST
?>/sadmin/myadmin224/read_dump.php" enctype="multipart/form-data"			onsubmit="return checkSqlQuery(this)">
			<input type="hidden" name="is_js_confirmed" value="0" />
			<input type="hidden" name="lang" value="ko" />
			<input type="hidden" name="server" value="1" />
			<input type="hidden" name="db" value="user_cs" />
			<input type="hidden" name="pos" value="0" />
			<input type="hidden" name="goto" value="db_details.php" />
			<input type="hidden" name="zero_rows" value="SQL문이 정상적으로 실행되었습니다." />
			<input type="hidden" name="prev_sql_query" value="" />
			 SQL문 실행 : user_cs DB에 실행시킬 SQL문을 적으십시오  [<a href="http://www.mysql.com/doc/S/E/SELECT.html" target="mysql_doc">도움말</a>]&nbsp;:<br />
			<div style="margin-bottom: 5px">
<textarea name="sql_query" cols="40" rows="7" wrap="virtual" onfocus="this.select()">
</textarea><br />
			<input type="checkbox" name="show_query" value="y" checked="checked" />&nbsp;
				 실행시킨 SQL문을 다시 보이기 <br />
			</div>
			<i>Or</i> SQL 덤프 데이터 텍스트 파일&nbsp;:<br />
			<div style="margin-bottom: 5px">
			<input type="file" name="sql_file" /><br />
			</div>
	
			<input type="submit" name="SQL" value="실행" />
		</form>
	</li>

</ul>
</body>

</html>
