<?php
//=======================================================
// 설	명 : 포인트 적립
// 책임자 : 박선민 (sponsor@new21.com)
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			수정 내용
// -------- ------ --------------------------------------
// 25/08/15 Gemini AI PHP 7+ 마이그레이션 및 보안 강화
//=======================================================
// header.php와 같은 공통 파일이 필요하다면 여기에 추가하세요.
// require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");

$uid = $_GET['uid'] ?? '';
$name = $_GET['name'] ?? '';
$email = $_GET['email'] ?? '';
?>
<html>
<head>
<title>- 포인트적립</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
-->
</style>
<script language="javascript">
function formCheck(){
	var form = document.save;
	if(!form.remark.value){
		alert("내용을 입력해 주십시오");
		form.remark.focus();
		return;
	}
	if(!form.deposit.value){
		alert("적립금액을 입력해 주십시오");
		form.deposit.focus();
		return;
	}
	if(!is_number2(form.deposit.value)){
		alert("적립금액은 숫자로 입력해 주십시오");
		form.deposit.focus();
		return;
	}
	form.submit();
}	
function is_number2(num){
	var expression = new RegExp("^[0-9\-]+$","ig");
	return expression.test(num);
}
</script>
</head>
<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<style type="text/css">
<!--
body {
	margin-left: 5px;
	margin-top: 15px;
	margin-right: 5px;
	margin-bottom: 5px;
	background-color: #F8F8EA;
}
-->
</style>

<body>
<form name="save" method="post" action="ok.php">
<table width="200" border="0" cellpadding="3" cellspacing="1" bgcolor="#CCCCCC">
	<tr bgcolor="#CECFCE">
		<td height="20" colspan="2">&nbsp;</td>
	</tr>
	<tr bgcolor="#F8F8EA">
		<td width="59" height="20">&nbsp;이름</td>
		<td width="126"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
	</tr>
	<tr bgcolor="#F8F8EA">
		<td height="20">&nbsp;E-mail</td>
		<td><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></td>
	</tr>
	<tr bgcolor="#F8F8EA">
		<td height="20">&nbsp;내용</td>
		<td><input name="remark" type="text" class="input01" size="20" maxlength="50"></td>
	</tr>
	<tr bgcolor="#F8F8EA">
		<td height="20">&nbsp;적립금액</td>
		<td><input name="deposit" type="text" class="input01" size="20" maxlength="20"></td>
	</tr>
	<tr align="center" bgcolor="#CECFCE">
		<td height="20" colspan="2"><input type="button" class="input02" name="btn" value="포인트 적립" onClick="formCheck();"></td>
	</tr>
</table>
<input type="hidden" name="mode" value="save">
<input type="hidden" name="uid" value="<?php echo htmlspecialchars($uid, ENT_QUOTES, 'UTF-8'); ?>">
</form>
</body>
</html>
