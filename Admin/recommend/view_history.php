<?php
//=======================================================
// 설	명 : (/index.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/01/06
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/01/06 박선민 추가 수정
// 25/01/XX PHP 7+ 호환성: 단축 태그 <?php echo  → <?php echo 변환
//=======================================================
$HEADER=array(
	'priv'	 => 99, // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	 => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard' => 1, // privAuth()
	'useApp'	 => 1
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

$sql = "select * from {$SITE['th']}board2_books_history where uid = {$uid}";
$list = db_arrayone($sql);

$list['content'] = replace_string($list['content'], $list['docu_type']);
$list['rdate'] = date("Y-m-d", $list['rdate']);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>도서목록 관리 - 상세보기</title>

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
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td><table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
		<tr>
		<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
		<td background="/images/admin/tbox_bg.gif"><strong>도서목록 관리 - 상세보기 </strong></td>
		<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
		</tr>
	</table>
		<br>
		<table width="97%"	border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#666666">
			<tr bgcolor="#F0EBD6">
			<td width="12%" height="22" align="center" bgcolor="#D2BF7E">제&nbsp; 목</td>
			<td colspan="3" bgcolor="#F0EBD6"><table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td><?php echo $list['title']?></td>
				</tr>
			</table></td>
			</tr>
			<tr bgcolor="#F0EBD6">
			<td height="22" align="center" bgcolor="#D2BF7E">건&nbsp; 수 </td>
			<td width="38%" align="center"><?php echo $list['data2']?>
				건 </td>
			<td width="11%" align="center" bgcolor="#D2BF7E">날&nbsp; 짜 </td>
			<td width="39%" align="center"><?php echo $list['rdate']?></td>
			</tr>
			<tr bgcolor="#F0EBD6">
			<td height="22" align="center" bgcolor="#D2BF7E">상세정보</td>
			<td colspan="3"><table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td><?php echo $list['content']?></td>
				</tr>
			</table></td>
			</tr>
		</table></td>
	</tr>
	<tr>
	<td height="40" align="center"><input type="button" value=" 닫 기 " onClick="self.close();"></td>
	</tr>
</table>
</body>
</html>
