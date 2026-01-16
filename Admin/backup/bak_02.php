<?php
$HEADER=array(
	'html_echo'	=>0, // html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
	'useClassSendmail' => 1
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
?>
<STYLE TYPE="text/css">
<!--
	A:link	{text-decoration:none; color:darkblue}
	A:visited{text-decoration:none; color:663399}
	A:hover {text-decoration:none; color:red}
	A:active {text-decoration:none; color:red}
	TD {font-size:9pt}
-->
</STYLE>
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


<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<?php
if($mode == 'mailsend')
{

		if(!$from)
			{	 echo( " <script>
							window.alert('보내는이의 E-mail 을 기입해 주세요.')
							history.go(-1)
						 </script>
						 "); exit;
			}
		if(!$to)
			{	 echo( " <script>
							window.alert('받는이의 E-mail 을 기입해 주세요.')
							history.go(-1)
						 </script>
						 "); exit;
			} 
		
		if(!$subject)
			{	 echo( " <script>
							window.alert('메일 제목을 입력해 주세요.')
							history.go(-1)
						 </script>
						 "); exit;
			}
		
		if(!$mail_body)
			{	 echo( " <script>
							window.alert('메일 내용을 입력해 주세요.')
							history.go(-1)
						 </script>
						 "); exit;
			}	

		$mail = new mime_mail;
		$mail->from		= $from;
		$mail->name		= $_SESSION['seName'];
		$mail->to		= $to;
		$mail->subject	= $subject;
		$mail->body		= $mail_body;	
		$mail->html	= 0;
		
		$userfile_name = $_FILES['userfile'][name];
		$userfile = $_FILES['userfile'][tmp_name];
		$userfile_size = $_FILES['userfile'][size];
		$userfile_type =$_FILES['userfile'][type];
		
		
		if($userfile && $userfile_size) {
			$filename=basename($userfile_name);
			$fd = fopen($userfile, "r");
			$data = fread($fd, $userfile_size);
			fclose($fd);
	
			$mail->add_attachment($data, $filename, $userfile_type);
		}	

		if($mail->send()){
			go_url($PHP_SELF, 1, "메일이 성공적으로 발송되었습니다.");
		}else {
				echo( "<script>
				window.alert(' 메일 전송에 실패했습니다. 다시 시도해 주세요 ')
				history.go(-1)
				</script>");
		}
		 exit;
				
}
?>

<script>
function checkmailform(){
	var f = document.mailsend;
	
	if(f.from.value==""){
			alert('보내는이의 E-mail 을 기입해 주세요.');
			f.from.focus();
			return false;
	}
	if(f.to.value==""){
			alert('받는이의 E-mail 을 기입해 주세요.');
			f.to.focus();
			return false;
	} 
	
	if(f.subject.value==""){
			alert('메일 제목을 입력해 주세요.');
			f.subject.focus();
			return false;
	}
	
	if(f.mail_body.value==""){
			alert('메일 내용을 입력해 주세요.');
			f.mail_body.focus();
			return false;
	}	
	f.submit();
	return true;
}

</script>
<style type="text/css">
<!--
.desc {color:#9090C0;}
-->
</style>
<form name='mailsend' enctype='multipart/form-data' method='post' action='<?php echo $PHP_SELF?>?mode=mailsend'>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
	<td><table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
		<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
		<td background="/images/admin/tbox_bg.gif"><strong>고객지원</strong></td>
		<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
		</tr>
	</table>
		<br>
		<table width='97%' border='0' align="center" cellpadding='4' cellspacing='1' bgcolor='#aaaaaa'>
		<tr height=25 bgcolor=#F8F8EA>
			<td height="25" bgcolor="#F0EBD6" align="center">고객지원</td>
		</tr>
		<tr bgcolor=#F8F8EA>
			<td width="97%" bgcolor="#F8F8EA"><table width="100%" border="0" align='center' cellpadding="2" cellspacing=1 bordercolorlight="#FFFFFF" bgcolor="#999999">
			<tr>
				<td width="19%" height="25" align=center nowrap bgcolor="#D2BF7E" ><font size='2'>받는 사람 </font> </td>
				<td width="81%" height="25" bgcolor="#F8F8EA" ><font color='#000000' size=-1>&nbsp; 고객지원팀
					<input type='hidden' name='to' class="styleinput" value='davej@dainit.com' size=35 readonly="true">
				</font> </td>
			</tr>
			<tr>
				<td align=center nowrap bgcolor="#D2BF7E" height="25" ><font size='2'>보내는 사람 </font> </td>
				<td height="25" bgcolor="#F8F8EA" ><font color='#000000' size=-1>
					&nbsp;
					<input name='from' class="ccbox"	value="<?php echo $_SESSION['seEmail']?>" size=35>
				</font> </td>
			</tr>
			<tr>
				<td align=center nowrap bgcolor="#D2BF7E" height="25" ><font size='2'>제 목 </font> </td>
				<td height="25" bgcolor="#F8F8EA" ><font color='#000000' size=-1>
				 &nbsp;
				 <input name='subject' class="ccbox" value='' size=38 style="width:80%">
				</font> </td>
			</tr>
			<tr>
				<td align=center nowrap bgcolor="#D2BF7E" height="25" ><font size='2'>전송할 파일 </font> </td>
				<td height="25" bgcolor="#F8F8EA" ><font color='#000000' size=-1>
				 &nbsp;
				 <input name='userfile' type=file class="ccbox" value='' style="width:70% ">
				</font> <font color='#000000' size=-1>(최대 파일 사이즈 : 2M)</font> </td>
			</tr>
			<tr bgcolor="#F8F8EA">
				<td height="40" colspan=2 valign='top' ><p align="center">
					<textarea name='mail_body' cols="70" rows=23 wrap=hard class="textarea01" style="width:95%;"></textarea>
				</td>
			</tr>
			<tr bgcolor="#FFFFFF">
				<td height="40" colspan=2	bgcolor="#F0EBD6"><div align='center'> <font size=-1>
					<input name='send' type=button class="input02" id="send3" onClick="javascript:return checkmailform();" value='메일전송'>&nbsp;
					<input name='Reset2' type='reset' class="input02" value='취소하기'>&nbsp;</font> </div></td>
			</tr>
			</table>
			</td>
		</tr>
	</table></td>
</tr>
</table>
<br>
<br>
</form>
<br>	
			
<?php echo $SITE['tail']?>