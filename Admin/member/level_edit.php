<html>
<head>
<title>회원등급조정</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
body {
	background-color:F8F8EA;
}
-->
</style>


<body>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
	<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
	<td background="/images/admin/tbox_bg.gif"><strong>회원 등급 조정</strong></td>
	<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
	</tr>
</table>
<br>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor='#aaaaaa'>
	<tr>
	<td height="25" bgcolor="#D2BF7E">
	<p align="center">회원 등급 조정</p></td>
	</tr>
	<form style='margin : 0px' action='ok.php' method='post'>
	<tr>
	<td height="35" bgcolor="#F8F8EA">		<div align="center">
			<input type="hidden" name="mode" size="30" style='height:16;' value="index_level">
			<input type="hidden" name="uid" size="30" style='height:16;' value="1">
			<input type="hidden" name="total_num" size="30" style='height:16;' value="<?=$total_num?>">
			<input name="level" type="text" class="input01" style='height:16; text-align:center' value="1" size="5">
		(1-99사이)</div>
	</td>
	</tr>
	<tr>
	<td height="25" valign="baseline" bgcolor="#F0EBD6">
		<div align="center"> 
			<input name="image" type="submit" class="input02" value=" 조정하기 " src="/sboard/stpl/yboard_enjoy/images/button_save.gif" align="top" width="80" height="22">
			&nbsp;
			<input name="image2" type="button" class="input02" value=" 취	소 " src="/sboard/stpl/yboard_enjoy/images/button_save.gif" align="top" width="80" height="22" onClick="self.close();">
		</div>		</td>
	</tr></form>
</table>
</body>
</html>
