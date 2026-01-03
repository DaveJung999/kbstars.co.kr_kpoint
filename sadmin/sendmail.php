<?php
if($_POST['mode'] == "send") {
	$to = $_POST['emails'];
	/* 
	// 파일 리스트를 읽어서
	$handle = fopen ($emails, "r");
	$to = fread ($handle, filesize ($emails));
	fclose ($handle);
	*/

	$subject = $_POST['subject'];

	$message = $_POST['html'];
	$message = stripslashes($message);

$HEADERs	= "MIME-Version: 1.0\r\n";
$HEADERs .= "Content-type: text/html; charset=utf-8\r\n";
$HEADERs .= "From: ".$fromName." <".$fromEmail.">\r\n";
	
	$email = explode("\n", $to);
	$i = 0;
	while($email[$i]) {
		if(mail($email[$i], $subject, $message, $headers))
			echo "<font color=green face=verdana size=1>* $i - ".$email[$i]."</font> <font color=green face=verdana size=1>OK</font><br>";
		else
			echo "* $i	".$email[$i]." <font color=red>NO</font><br><hr>";
		$i++;
	}
}
?>
<html>
<head>
<title>[ Program by Sunmin Park ]</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style>
.normal {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #000000;
}
.form {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;
	color: #333333;
	background-color: #FFFFFF;
	border: 1px dashed #666666;
}

</style>
</head>
<body leftmargin="0" topmargin="0" rightmargin="0" bottommargin="0" 
marginwidth="0" marginheight="0">
<form action="" method="post" enctype="multipart/form-data" 
name="form1">
<input type="hidden" name="mode" value="send">
<table width="468" height="248" border="0" cellpadding="0" cellspacing="1" 
bgcolor="#CCCCCC" class="normal">
<tr>
<td width="468" height="39" align="center" bgcolor="#F4F4F4">[ Program by Sunmin Park ]</td>
</tr>
<tr>
<td height="194" valign="top" bgcolor="#FFFFFF"><table width="100%"	
border="0" cellpadding="0" cellspacing="5" class="normal">
<tr>
<td width="16%" align="right">보내는 메일:</td>
<td width="84%"><input name="fromEmail" type="text" class="form" id="fromEmail" 
size="84" value="<?=$_REQUEST['fromEmail'] 
?>"></td>
</tr>
<tr>
<td align="right">보내는 사람:</td>
<td><input name="fromName" type="text" class="form" id="fromName" 
size="84" value="<?=$_REQUEST['fromName'] 
?>"></td>
</tr>
<tr align="center" bgcolor="#F4F4F4">
<td height="25" colspan="2">메일 리스트 </td>
</tr>
<tr>
	<td height="51" colspan="2" align="center" valign="top"><br>
	<textarea name="emails" 
cols="100" rows="10" wrap="VIRTUAL" class="form" id="emails"><?=$_REQUEST['emails']  ?></textarea>
	<br></td>
</tr>
<tr align="center" bgcolor="#F4F4F4">
<td height="24" colspan="2">HTML</td>
</tr>
<tr>
<td height="71" colspan="2" align="center" valign="top"><textarea name="html" 
cols="100" rows="20" wrap="VIRTUAL" class="form" id="html"><?=$_REQUEST['html']  ?></textarea></td>
</tr>

<tr>
<td height="22" align="right" valign="top"> </td>
<td align="center" valign="top"><input type="submit" name="Submit" 
value="메일발송"></td>
</tr>
</table></td>
</tr>
<tr>
<td height="15" align="center" bgcolor="#F4F4F4"> </td>
</tr>
</table>
</form>
<center> BY	ki-suco :D
</center>
</body>
</html>
