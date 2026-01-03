<?php
//=======================================================
// 설	명 : 게시판 종합관리(setup.php) - 신규 게시판 생성
// 책임자 : 박선민 (sponsor@new21.com), 검수: 02/09/30
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 02/09/30 박선민 마지막 수정
// 03/08/25 박선민 마지막 수정
// 25/09/08 Gemini PHP 7+ 호환성 업데이트, 오류 수정 및 기능 개선
//=======================================================

$HEADER=array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp' => 1,
	'useSkin' =>	1,
	'useBoard2' => 1,
	'html_echo' => ''	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
);
require("{$_SERVER['DOCUMENT_ROOT']}/sinc/header.php");
// $HTTP_HOST는 PHP 5.4+ 부터 제거됨. $_SERVER['HTTP_HOST'] 사용
page_security("", $_SERVER['HTTP_HOST']);

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

// opendir/readdir 대신 scandir를 사용하여 코드 간소화
$skin_dir = "{$_SERVER['DOCUMENT_ROOT']}/sboard2/skin";
$skin_options = '';
if (is_dir($skin_dir)) {
	$files = scandir($skin_dir);
	foreach ($files as $file) {
		// 디렉토리가 아닌 파일만, 그리고 . 과 .. 제외
		if ($file !== "." && $file !== ".." && is_dir($skin_dir . '/' . $file)) {
			// 보안을 위해 htmlspecialchars 적용
			$file_safe = htmlspecialchars($file, ENT_QUOTES, 'UTF-8');
			$skin_options .= "<option value='{$file_safe}'>{$file_safe}</option>";
		}
	}
}
?>
<script src="/scommon/join_check.js"></script>
<script>
// 아이디 존재 여부 확인
function certify_userid() {
	// JavaScript에서 찾으려는 input name 'db'와 일치시킴 (기존: table_name)
	var userid = document.ad_table.db.value;
	if (!userid) {
		alert("게시판 아이디는 2-10자까지 숫자, 영문자로만 조합이 가능합니다.\n\n첫 문자는 영문자여야 합니다.\n게시판 아이디를 올바른 형식으로 먼저 입력해주세요.");
		document.ad_table.db.focus();
		return;
	}
	// find_table.php로 table_name 대신 db 변수명으로 전달
	window.open('./find_table.php?db=' + userid, 'idcheck', 'width=300,height=200,resizable=1,scrollbars=0');
}

// 가입하기 클릭후 폼문을 제대로 입력했는지 체크
function checkForm() {
	var f = document.ad_table;

	// 입력값 공백 체크 (JavaScript에서 찾으려는 input name 'db'와 일치시킴)
	if (isEmpty(f.db.value)) {
		alert('게시판 아이디를 입력하시기 바랍니다.');
		f.db.focus();
		return false;
	}
	if (isEmpty(f.title.value)) {
		alert('게시판 제목을 입력하시기 바랍니다.');
		f.title.focus();
		return false;
	}
	
	return true; // 모든 검사를 통과하면 true를 반환하여 form을 submit
}
</script>

<script src="/scommon/js/chkform.js"></script>

<link href="/css/basic_text.css" rel="stylesheet" type="text/css">
<link href="/css/link01.css" rel="stylesheet" type="text/css">
<style>
body {
	margin: 15px 5px 5px 5px;
	background-color: #F8F8EA;
}
.style1 { color: #F8F8EA; }
</style>

<span class="style1"></span>
<table width="100%" align="center" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td>
			<table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
					<td background="/images/admin/tbox_bg.gif"><strong>게시판 정보관리</strong></td>
					<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
				</tr>
			</table>
			
			<br>
			<form name="ad_table" method="post" action="ok.php?mode=write" onsubmit="return checkForm();">
				<table width="97%" border="0" align="center" cellpadding="2" cellspacing="1" bgcolor="#666666">
					<tr bgcolor="#F0EBD6">
						<td height="25" colspan="2" align="center" style="font-size:9pt;">&nbsp;▒ 게시판 생성 ▒</td>
					</tr>
					<tr>
						<td width="20%" height="25" bgcolor="#D2BF7E" align="center" style="font-size:9pt;">게시판 아이디</td>
						<td width="80%" height="25" bgcolor="#F8F8EA" style="font-size:9pt;">
							&nbsp;
							<input name="db" type="text" class="input01" value="" size="30" maxlength="20" required hname="게시판 아이디를 입력해 주세요.">
							<input type="button" class="CCbox03" value="중복확인" onclick="certify_userid();">
						</td>
					</tr>
					<tr>
						<td width="20%" height="25" bgcolor="#D2BF7E" align="center" style="font-size:9pt;">게시판 제목</td>
						<td width="80%" bgcolor="#F8F8EA" style="font-size:9pt;">
							&nbsp;
							<input name="title" type="text" class="input01" value="" size="60" maxlength="200" required hname="게시판 제목을 입력해 주세요.">
						</td>
					</tr>
					<tr>
						<td width="20%" bgcolor="#D2BF7E" align="center" style="font-size:9pt;">스킨 설정</td>
						<td width="80%" bgcolor="#F8F8EA" style="font-size:9pt;">
							&nbsp;
							<select name="skin" class="styleselect">
								<?php echo $skin_options; ?>
							</select>
						</td>
					</tr>
					<tr bgcolor="#F8F8EA">
						<td height="55" colspan="2" align="center">
							<div align="center" style="font-size:9pt;">
								<input name="ok" type="submit" class="CCbox03" value="게시판 생성">
								&nbsp;&nbsp;
								<input name="ok2" type="button" class="CCbox03" value="뒤로" onclick="history.go(-1);">
							</div>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>