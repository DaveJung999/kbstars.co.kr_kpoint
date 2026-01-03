<?php
//=======================================================
// 설	명 :
// 책임자 : 박선민 (sponsor@new21.com), 검수: 04/11/26
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인			 수정 내용
// -------- ------ --------------------------------------
// 04/11/26 박선민 마지막 수정
// 25/08/13 Gemini PHP 7+ 호환성 및 보안 강화
//=======================================================
$HEADER = array (
	'auth'		 =>	1, // 인증유무 (0:모두에게 허용, 숫자가 logon테이블 Level)
	'usedb2'	 =>	1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useBoard'	 =>	1, // 보드관련 함수 포함
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

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
	// GET 파라미터를 안전하게 받습니다.
	$db = $_GET['db'] ?? null;
	if (empty($db)) {
		back("db값이 넘어오지 않았습니다");
	}
	
	global $SITE;

	$table_dmailinfo = "{$SITE['th']}dmailinfo";
	$table_dmail = "{$SITE['th']}dmail_{$db}";

	// SQL 인젝션 방지를 위해 db_escape 사용
	$db_safe = db_escape($db);
	$sql = "SELECT * FROM {$table_dmailinfo} WHERE db='{$db_safe}'";
	$dmailinfo = db_arrayone($sql);

	if (!$dmailinfo) {
		back("유효하지 않은 db값입니다.");
	}

	// 필드 리스트 가져오기 (PHP 7+ 호환 방식)
	$fieldlist = [];
	// 테이블 구조를 보기 위해 SHOW COLUMNS 쿼리 사용
	$sql_fields = "SHOW COLUMNS FROM {$table_dmail}";
	$result = db_query($sql_fields);
	if ($result) {
		while ($field = db_array($result)) {
			$field_name = $field['Field'];
			if ($field_name != "uid" && $field_name != "email" && $field_name != "sendok" && $field_name != "readtime") {
				$fieldlist[] = $field_name;
			}
		}
		db_free($result);
	}

	// URL List..
	$href['list'] = "list_modify.php?db=" . urlencode($db);

//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<html>
<head>
<title>대량메일발송</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" href="style.css" type="text/css">
</head>
<body bgcolor="#FFFFFF" text="#000000">
	<form name="ITwizard" method="post" action="ok.php">
	<input type="hidden" name="mode" value="write_textarea">
	<input type="hidden" name="db" value="<?php echo htmlspecialchars($db, ENT_QUOTES, 'UTF-8'); ?>">
	<table width="500" border="0" cellspacing="0" cellpadding="1">
		<tr bgcolor="#000000">
			<td colspan="2" height="1"></td>
		</tr>
		<tr bgcolor="#000000">
			<td colspan="2" height="1"></td>
		</tr>
		<tr bgcolor="#F1F1EB">
			<td width="81">메일리스트</td>
			<td width="513" bgcolor="#F1F1EB"><font size="2">
			<textarea name="emaillist" cols="40" rows="20"></textarea>
			</font></td>
		</tr>
		<tr bgcolor="#F1F1EB">
			<td width="81"></td>
			<td width="513"><font size="2"></font><font size="2">
			<input type="submit" name="Submit" value="일괄메일 추가">
			<input type="button" value="Back" onClick="document.location='<?php echo htmlspecialchars($href['list'], ENT_QUOTES, 'UTF-8'); ?>'" class="submit" >
			</font></td>
		</tr>
		<tr bgcolor="#DDDDCC">
			<td colspan="2" height="5"></td>
		</tr>
		<tr bgcolor="#000000">
			<td colspan="2" height="1"></td>
		</tr>
	</table>
		<p>&nbsp;</p>
	</form>
	<a href="<?php echo htmlspecialchars($href['add_textarea'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">일괄메일추가</a>&nbsp;&nbsp;&nbsp;&nbsp;회원DB에서 가져오기
</body>
</html>