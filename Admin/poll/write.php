<?php
//=======================================================
// 설	명 : 설문 종합관리
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/25
// Project: sitePHPbasic
// ChangeLog
//	DATE	수정인				수정 내용
// -------- ------ --------------------------------------
// 03/08/25 박선민 마지막 수정
// 25/08/13 Gemini PHP 7+ 호환성 및 보안 강화
//=======================================================
$HEADER = array(
	'priv'		 =>	1, // 인증유무 (0:모두에게 허용)
	'class'	 => 'root', // 관리자만 로그인
	'usedb2'	 =>	1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp'	 =>	1,
	'useBoard'	 =>	1,
	'html_echo' => 0	// html header, tail 삽입(tail은 파일 마지막에 echo $SITE['tail'])
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
// Helper Function (헬퍼 함수)
//=======================================================
/**
 * 숫자 범위에 대한 <select> 드롭다운을 생성합니다. (년, 월, 일 선택용)
 * @param string $name - select 태그의 name 속성
 * @param int $start - 루프 시작 숫자
 * @param int $end - 루프 끝 숫자
 * @param int $selectedValue - 기본으로 선택될 값
 * @param string $unit - 숫자 뒤에 붙일 단위 (년, 월, 일 등)
 */
function generate_date_dropdown($name, $start, $end, $selectedValue, $unit = '') {
	echo "<select name=\"{$name}\">";
	for ($i = $start; $i <= $end; $i++) {
		$selected = ($i == $selectedValue) ? 'selected' : '';
		echo "<option value=\"{$i}\" {$selected}>{$i}</option>";
	}
	echo "</select> {$unit}";
}

//=======================================================
// Ready... (변수 초기화 및 넘어온값 필터링)
//=======================================================
// GET 파라미터를 안전하게 받습니다. (PHP 7+ Null Coalescing Operator 사용)
$mode = $_GET['mode'] ?? 'write';
$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : 0; // SQL 인젝션 방지를 위해 정수형으로 변환
$db = $_GET['db'] ?? null;
$cateuid = $_GET['cateuid'] ?? null;
$pern = $_GET['pern'] ?? null;
$sc_column = $_GET['sc_column'] ?? null;
$sc_string = $_GET['sc_string'] ?? '';

// 기본 URL QueryString
$qs_basic = http_build_query([
	'db' => $db,
	'mode' => '',
	'cateuid' => $cateuid,
	'pern' => $pern,
	'sc_column' => $sc_column,
	'sc_string' => $sc_string,
	'page' => $_GET['page'] ?? 1
]);

$table_pollinfo = "{$SITE['th']}pollinfo"; //게시판 관리 테이블

// 관리자페이지 환경파일 읽어드림
$rs = db_query("select * from {$SITE['th']}admin_config where skin='{$SITE['th']}' or skin='basic' order by uid DESC");
$pageinfo = db_count($rs) ? db_array($rs) : back("관리자페이지 환경파일을 읽을 수가 없습니다");

if ($mode == "modify" && $uid > 0) {
	
	$rs = db_query("SELECT * FROM {$table_pollinfo} WHERE uid={$uid}");
	$list = db_count($rs) ? db_array($rs) : null;

	if (!$list) {
		back("게시물의 정보가 없습니다");
	}
	
	// XSS 방지를 위해 DB에서 가져온 데이터를 HTML 특수문자로 변환
	userFormSpecialChars($SITE['database'], $table_pollinfo, $list);

	// 날짜 정보 분리
	$Y = date('Y', $list['startdate']);
	$M = date('n', $list['startdate']);
	$D = date('j', $list['startdate']);
	$T = date('t', $list['startdate']);

	$y = date('Y', $list['enddate']);
	$m = date('n', $list['enddate']);
	$d = date('j', $list['enddate']);
	$t = date('t', $list['enddate']);

} else {
	$mode = "write";
	$list['skin'] = "poll_basic";
	// 쓰기 모드일 때 날짜 기본값 설정
	$Y = $y = date('Y');
	$M = $m = date('n');
	$D = $d = date('j');
	$T = $t = date('t');
}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<!DOCTYPE html>
<html>
<head>
<title>설문 작성</title>
<meta charset="utf-8">
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
</head>
<body leftmargin="0" topmargin="0">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td>
			<table width="97%" border="0" align="center" cellpadding="0" cellspacing="0">
				<tr>
					<td width="22"><img src="/images/admin/tbox_l.gif" width="22" height="22"></td>
					<td background="/images/admin/tbox_bg.gif"><strong>설문조사 관리 </strong></td>
					<td align="right" width="5"><img src="/images/admin/tbox_r.gif" width="5" height="22"></td>
				</tr>
			</table>
			<br>
			<form name="form1" method="post" action="./ok.php" style="margin:0px">
			<input type="hidden" name="mode" value="<?php echo $mode; ?>">
			<input type="hidden" name="uid" value="<?php echo $list['uid'] ?? ''; ?>">
			<table width="97%" border="0" align="center" cellpadding="3" cellspacing="1" bgcolor="#aaaaaa">
			<?php if($mode == "write"): ?>
				<tr>
					<td width="120" height="31" align="center" bgcolor="#D2BF7E"><b>DB Name </b></td>
					<td bgcolor="#F8F8EA" height="31"><input type="text" name="db" size="16" value="<?php echo $list['db'] ?? ''; ?>"> (<?php echo $SITE['th']; ?>poll_??? 이렇게 생성이 됩니다.)</td>
				</tr>
			<?php else: ?>
				<tr>
					<td height="31" align="center" bgcolor="#D2BF7E"><b>DB Name </b></td>
					<td bgcolor="#F8F8EA" height="31"><?php echo $list['db'] ?? ''; ?> (변경불가)</td>
				</tr>
			<?php endif; ?>
				<tr>
					<td height="31" align="center" bgcolor="#D2BF7E"><b>SKIN</b></td>
					<td bgcolor="#F8F8EA" height="25"><input name="skin" type="text" id="skin" value="<?php echo $list['skin'] ?? ''; ?>" size="15"></td>
				</tr>
				<tr>
					<td height="25" align="center" bgcolor="#D2BF7E"><b>참여회원레벨</b></td>
					<td bgcolor="#F8F8EA" height="25"><input type="text" name="member" value="<?php echo $list['member'] ?? ''; ?>"> (0:모든사람참여, 1이상:로그인후 입력한 레벨 이상만 참여)</td>
				</tr>
				<tr>
					<td height="25" align="center" bgcolor="#D2BF7E"><b>성별</b></td>
					<td bgcolor="#F8F8EA" height="25">
						<input type="radio" name="sex" value="0" <?php if (($list['sex'] ?? 0) == 0) echo "checked"; ?>> 전체
						<input type="radio" name="sex" value="1" <?php if (($list['sex'] ?? 0) == 1) echo "checked"; ?>> 남자
						<input type="radio" name="sex" value="2" <?php if (($list['sex'] ?? 0) == 2) echo "checked"; ?>> 여자
					</td>
				</tr>
				<tr>
					<td height="25" align="center" bgcolor="#D2BF7E"><b>연령별</b></td>
					<td bgcolor="#F8F8EA" height="25">
					<select name="age">
						<?php
						$age_options = [
							'0' => '전체', '10/19' => '10대', '20/29' => '20대', '30/39' => '30대', '40/49' => '40대', '50/100' => '50대이상',
							'10/29' => '10대~20대', '10/39' => '10대~30대', '10/49' => '10대~40대', '20/39' => '20대~30대',
							'20/49' => '20대~40대', '20/100' => '20대이상', '30/49' => '30대~40대', '30/100' => '30대이상', '40/100' => '40대이상'
						];
						$current_age = $list['age'] ?? '0';
						foreach ($age_options as $value => $text) {
							$selected = ($current_age == $value) ? 'selected' : '';
							echo "<option value=\"{$value}\" {$selected}>{$text}</option>";
						}
						?>
					</select>
					</td>
				</tr>
				<tr>
					<td height="26" align="center" bgcolor="#D2BF7E"><b>투표 시작일</b></td>
					<td bgcolor="#F8F8EA" height="26">
						<?php
						$current_y = date('Y');
						generate_date_dropdown('start_time_y', $current_y, $current_y + 2, $Y, '년');
						generate_date_dropdown('start_time_m', 1, 12, $M, '월');
						generate_date_dropdown('start_time_d', 1, $T, $D, '일');
						?>
					</td>
				</tr>
				<tr>
					<td align="center" bgcolor="#D2BF7E"><b>투표 마감일</b></td>
					<td bgcolor="#F8F8EA">
						<?php
						$current_y = date('Y');
						generate_date_dropdown('end_time_y', $current_y, $current_y + 2, $y, '년');
						generate_date_dropdown('end_time_m', 1, 12, $m, '월');
						generate_date_dropdown('end_time_d', 1, $t, $d, '일');
						?>
					</td>
				</tr>
				<tr>
					<td align="center" bgcolor="#D2BF7E"><b>메인설문으로</b></td>
					<td bgcolor="#F8F8EA"><input name="enable_mainpoll" type="checkbox" id="enable_mainpoll" value="1" <?php if (($list['enable_mainpoll'] ?? 0) == 1) echo "checked"; ?>> (체크하면 새로 메인설문으로 설정함)</td>
				</tr>
				<tr>
					<td align="center" bgcolor="#D2BF7E"><b>투표 주제</b></td>
					<td bgcolor="#F8F8EA"><textarea name="title" cols="60" rows="5"><?php echo $list['title'] ?? ''; ?></textarea></td>
				</tr>
			<?php
			// 질문 항목수에 따라... 항목을 나열한다.
			for ($i = 1; $i <= 10; $i++) {
				$question_key = "q".$i;
			?>
				<tr>
					<td align="center" bgcolor="#D2BF7E"><b>항목 <?php echo $i; ?></b></td>
					<td bgcolor="#F8F8EA"><input type="text" name="q<?php echo $i; ?>" size="60" value="<?php echo $list[$question_key] ?? ''; ?>"></td>
				</tr>
			<?php
			}
			?>
				<tr>
					<td height="50" align="center" bgcolor="#D2BF7E">&nbsp;</td>
					<td bgcolor="#F8F8EA" height="50"><input type="submit" name="Submit" value=" :: 설문 만들기 :: "> &nbsp; <input type="button" name="back" value=" :: 뒤로가기 :: " onClick="javascript:history.go(-1);"></td>
				</tr>
			</table>
			</form>
		</td>
	</tr>
</table>
<br>
</body>
</html>
<?php
//=======================================================
// User functions... (사용자 함수 정의)
//=======================================================
/**
 * 03/08/25 - 박선민
 * @param string $database 데이터베이스 이름 (하위 호환성을 위해 유지되나, 현재 함수에서는 사용되지 않음)
 * @param string $table 테이블 이름 (하위 호환성을 위해 유지되나, 현재 함수에서는 사용되지 않음)
 * @param array &$list DB에서 가져온 데이터 배열 (레퍼런스 전달)
 * @return bool 성공 여부
 */
function userFormSpecialChars($table, &$list) {
	$safe_table = db_escape($table);
	$result = db_query("SHOW COLUMNS FROM {$safe_table}");
	if (!$result) return false;

	$string_types = ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'enum', 'set'];

	while ($row = db_array($result)) {
		$field_name = $row['Field'];
		$field_type = strtolower(preg_replace('/\(.*/', '', $row['Type']));

		if (isset($list[$field_name]) && in_array($field_type, $string_types)) {
			$list[$field_name] = htmlspecialchars($list[$field_name], ENT_QUOTES, 'UTF-8');
		}
	}
	db_free_result($result);
	
	return true;
}
?>
