<?php
//=======================================================
// 설	명 : (/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/01/06
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 04/01/06 박선민 추가 수정
//=======================================================
$HEADER=array(
	'priv' => '', // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php"); 
?>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
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

<script language='javascript'>
<!--
function default_imgview(){
	if (eval("document.form.default_images.checked") == false){
		document.form.default_images.value = "";
		document.form.new_images.value = "";
		document.form.check_default.value = "0";
	}
	else if (eval("document.form.default_images.checked") == true){
		document.form.default_images.value = "/h_images/main_baner_3.swf";
		document.form.new_images.value = "/h_images/main_baner_3.swf";
		document.form.check_default.value = "1";
	}
}

function dreamkos_imgview(){
	img_pre = 'pre';
	if(event.srcElement.value.match(/(.jpg|.jpeg|.gif|.png|.JPG |.GIF|.PNG|.JPEG)/)){
		document.form[img_pre].src = event.srcElement.value;
		document.form[img_pre].style.display = '';
	swf.innerHTML="";
	}
	else if(event.srcElement.value.match(/(.swf|.SWF)/)){
	swf.innerHTML="&nbsp;<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://active.macromedia.com/flash4/cabs/swflash.cab#version=4,0,0,0' width='470' height='194'> <param name='movie' value='"+event.srcElement.value+"'>	<param name='play' value='true'>	<param name='loop' value='true'>	<param name='quality' value='high'>	<embed src='"+event.srcElement.value+"' play='true' loop='true' quality='high' pluginspage='http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash' width='470' height='194'></embed></object>";
	document.form[img_pre].style.display = 'none';
 } else {
	swf.innerHTML="";
	document.form[img_pre].style.display = 'none';
	}
}

//-->
</script>
<form name="form" method='post' action="./ok.php" enctype="multipart/form-data">
<input type="hidden" name="mode" value="main_banner">
<input type="hidden" name="new_images" value="">
<input type="hidden" name="check_default" value="0">
<table width="880" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td width="20%" valign="top"><table width="159" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td width="10"><img src="/images/admin/smt_01.gif" width="10" height="19"></td>
		<td width="100%" background="/images/admin/smt_02.gif">&nbsp;</td>
		<td width="10" align="right"><img src="/images/admin/smt_03.gif" width="10" height="19"></td>
	</tr>
	</table>
	<table width="159" border="0" cellspacing="1" cellpadding="2" bgcolor="#F5F5F5">
		<tr>
		<td width="159" background="/images/admin/line2.gif" height="1"></td>
		</tr>
		<tr>
		<td width="159">&nbsp;<img src="/images/admin/bul01.gif" width="10" height="10" align="absmiddle"> <a href="index.php">상단 배너관리</a></td>
		</tr>
		<tr>
		<td width="159" background="/images/admin/line2.gif" height="1"></td>
		</tr>
		<tr>
		<td width="159">&nbsp;<img src="/images/admin/bul01.gif" width="10" height="10" align="absmiddle"> <a href="main.php">메인 배너관리</a></td>
		</tr>
		<tr>
		<td width="159" background="/images/admin/line2.gif" height="1"></td>
		</tr>
		<tr>
		<td width="159">&nbsp;<img src="/images/admin/bul01.gif" width="10" height="10" align="absmiddle"> <a href="banner.php">우측 배너관리</a></td>
		</tr>
		<tr>
		<td width="159" background="/images/admin/line2.gif" height="1"></td>
		</tr>
	</table></td>
	<td width="80%">
		<table width="97%" align="center" border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
			<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
			<td background="/images/admin/tbox_bg.gif"><b>배너관리 &gt;&gt; 메인 배너관리</b></td>
			<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
		</tr>
	</table>
		<br><table border=0 cellpadding=0 cellspacing=0 width=100% bgcolor=#F8F8EA>
				<tr>
				<td colspan=2 width=97%>
				<table width='97%' border='0' align="center" cellpadding='4' cellspacing='1' bgcolor='#aaaaaa'>
					<tr height=25 bgcolor=#F8F8EA>
						<td height="25" colspan=2	bgcolor="#F0EBD6" align="center"><strong>메인 배너관리</strong></td>
					</tr>
					<tr height=25>
						<td width=120 height="30" align="center" bgcolor="#D2BF7E">기본설정</td>
					<td bgcolor=#F8F8EA><input type="checkbox" name="default_images" value="/h_images/banner.giff" onClick='default_imgview();dreamkos_imgview()' >
						기본설정으로 (배너 사이즈 : 470px * 194 px) </td>
					</tr>
					<tr height=25>
						<td bgcolor="#D2BF7E" align="center">현재 이미지</td>
					<td height="80" bgcolor=#F8F8EA>						&nbsp; 
<?php	
					if(strtolower(substr($SITE['main_banner'], -3)) == 'gif' || strtolower(substr($SITE['main_banner'], -3)) == 'jpg' || strtolower(substr($SITE['main_banner'], -3)) == 'bmp') 
						echo "<img src='{$SITE['main_banner']}'>";
					elseif (strtolower(substr($SITE['main_banner'], -3)) == 'swf') 
						echo "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://active.macromedia.com/flash4/cabs/swflash.cab#version=4,0,0,0' width='470' height='194'> 
								<param name='movie' value='{$SITE['main_banner']}'>	<param name='play' value='true'>
								<param name='loop' value='true'>
								<param name='quality' value='high'>	
								<embed src='{$SITE['main_banner']}' play='true' loop='true' quality='high' pluginspage='http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash' width='470' height='194'></embed>
							</object>";
					else 
						echo "/images/admin/dot.gif"; 
?> 
					</td>
					</tr>
					<tr height=25>
					<td bgcolor="#D2BF7E" align="center">바꿀 이미지</td>
					<td height="80" bgcolor=#F8F8EA>&nbsp;<img id='pre' style='display:none;' border='0'>&nbsp;<div id="swf">&nbsp;</div> </td>
					</tr>
					<tr height=25>
						<td height="30" align="center" bgcolor="#D2BF7E">업로드</td>
						<td bgcolor=#F8F8EA>
						<input name='upfile' type='file' id="upfile" onChange='dreamkos_imgview()' size='50' src="123"> 
						</td>
					</tr>
					<tr valign=middle bgcolor="#F8F8EA" height=50>
					<td colspan=2 align="center" > 
						<input type="submit" value=" 저장하기 ">
					</td>
					</tr>
				</table></td>
		</tr>
		</table>
	</td>
	</tr>
</table>
</form>
