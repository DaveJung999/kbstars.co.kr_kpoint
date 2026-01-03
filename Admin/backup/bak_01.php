<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title></title>

<style type="text/css">
<!--
.desc {color:#9090C0;}
-->
</style>
<style type="text/css">
<!--
body {
	margin-left: 5px;
	margin-top: 15px;
	margin-right: 5px;
	margin-bottom: 5px;
	background-color:F8F8EA;
}
-->
</style>
</head>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">

<body>

<form name="form" method='post' action="./ok.php" enctype="multipart/form-data">
<input type="hidden" name="mode" value="db_backup">
<br>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td>
		<table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
			<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
			<td background="/images/admin/tbox_bg.gif"><strong>데이터베이스 백업 </strong></td>
			<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
		</tr>
	</table>
		<br>		<table width='97%' border='0' align="center" cellpadding='4' cellspacing='1' bgcolor='#aaaaaa'>
		<tr height=25 bgcolor=#F8F8EA>
			<td height="25" bgcolor="#F0EBD6" align="center">데이터베이스 백업 </td>
		</tr>
		<tr bgcolor=#F8F8EA>
			<td width="97%" bgcolor="#F8F8EA">
			<table border=0 cellpadding=0 cellspacing=0 width='100%' bgcolor='#F8F8EA'>
				<tr>
				<td colspan=2 width=97%>
				<table border='0' cellpadding='4' cellspacing='1' width='100%' bgcolor='#aaaaaa'>
					<tr height=25>
						<td width="120" align="center" bgcolor="#D2BF7E">&nbsp;</td>
						<td height="80" bgcolor=#F8F8EA><div>
						<input type="radio" name="type" id="radio2" value="all" checked>
						<label for="radio2">전체 <span class="desc">: 전체 데이터를 백업합니다.</span></label>
						</div>
						</td>
					</tr>
				</table></td>
				</tr>
				<tr valign=middle>
				<td height=50 align="center" bgcolor="#F0EBD6"><input name="submit" type="submit" class="bttn" value=" 데이터를 백업합니다 " /> </td>
				</tr>
			</table></td>
		</tr>
		</table>
	</td>
	</tr>
</table>
</form>

</body>
</html>
