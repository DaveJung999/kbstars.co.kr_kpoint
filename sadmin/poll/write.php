<?php
//=======================================================
// 설	명 : 설문 종합관리(list.php)
// 책임자 : 박선민 (sponsor@new21.com), 검수: 03/08/25
// Project: sitePHPbasic
// ChangeLog
//	 DATE	 수정인				 수정 내용
// -------- ------ --------------------------------------
// 03/08/25 박선민 마지막 수정
// 25/08/13 Gemini 최신 PHP 버전 호환성 수정
//=======================================================
$HEADER=array(
	'auth'		 =>	2, // 인증유무 (0:모두에게 허용)
	'priv'		 =>	'운영자', // 인증유무 (비회원,회원,운영자,서버관리자) // 관리자만 로그인
	'usedb2'	 =>	1, // DB 커넥션 사용 (0:미사용, 1:사용)
	'useApp'	 =>	1,
	'useBoard'	 =>	1,
);
require($_SERVER['DOCUMENT_ROOT'].'/sinc/header.php');
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

//===================================================
// REQUEST 값 대입......2025-09-10
$params = ['db', 'table', 'cateuid', 'pern', 'cut_length', 'row_pern', 'sql_where', 'sc_column', 'sc_string', 'page', 'mode', 'sup_bid', 'modify_uid', 'uid', 'goto', 'game', 'pid', 'gid', 'sid', 's_id', 'season', 'session_id', 'tid', 'rid', 'num', 'name', 'pback', 'search_text', 'upfile', 'notfound', 'limitno', 'limitrows'];
foreach ($params as $param) {
	$$param = $_REQUEST[$param] ?? $$param ?? null;
}
//===================================================

// 기본 URL QueryString
$qs_basic = http_build_query([
	'db' => $db,
	'mode' => '',
	'cateuid' => $cateuid,
	'pern' => $pern,
	'sc_column' => $sc_column,
	'sc_string' => $sc_string,
	'page' => $page
]);

$table_pollinfo = $SITE['th'] . "pollinfo";	//게시판 관리 테이블

if ($mode == "modify" && $uid) {
	$rs_list = db_query("SELECT * FROM {$table_pollinfo} WHERE uid='{$uid}'");
	
	// db_count() 함수가 결과 리소스 없이 작동하는 것을 가정합니다.
	// 최신 DB 래퍼에서는 db_count($rs_list) 형태가 일반적입니다.
	if (db_count()) {
		$list = db_array($rs_list);
		userFormSpecialChars($SITE['database'], $table_pollinfo, $list);

		## 대문자는 설문 시작날짜 소문자는 마감날짜
		$Y = date('Y', $list['startdate']);
		$M = date('n', $list['startdate']);
		$D = date('j', $list['startdate']);
		$T = date('t', $list['startdate']);

		$y = date('Y', $list['enddate']);
		$m = date('n', $list['enddate']);
		$d = date('j', $list['enddate']);
		$t = date('t', $list['enddate']);
	} else {
		back("게시물의 정보가 없습니다");
	}
} else {
	$mode = "write";
	// 쓰기 모드일 때 날짜 기본값 설정
	$Y = $y = date('Y');
	$M = $m = date('n');
	$D = $d = date('j');
	$T = $t = date('t');
	$list = []; // $list 배열 초기화
}
//=======================================================
// Start... (DB 작업 및 display)
//=======================================================
?>
<html>
<head>
<title>설문 작성</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body bgcolor="#FFFFFF" text="#000000">
<br>
<table width="100%">
	<tr>
	<td>
		<form name="form1" method="post" action="./ok.php" style="margin:0px" >
		<input type="hidden" name="mode" value="<?php echo $mode; ?>">
		<input type="hidden" name="uid" value="<?php echo $list['uid'] ?? ''; ?>">
		<table width="100%" cellpadding="5">
		<tr>
			<td bgcolor="#CCCCCC" width="14%" height="31"><div align="right"><b><font size="2">DB Name </font></b></div></td>
			<td bgcolor="#f6f6f6" width="86%" height="31">
			<?php if($mode == "write"): ?>
				<input type="text" name="db" size="16" value="<?php echo $list['db'] ?? ''; ?>">
				<font size="2"> (<?=$SITE['th']?>poll_??? 이렇게 생성이 됩니다.) </font>
			<?php else: ?>
				<font size="2"><?php echo $list['db'] ?? ''; ?> (변경불가)</font>
			<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td bgcolor="#CCCCCC" width="14%" height="25"><div align="right"><b><font color="#000000" size="2">권한</font></b></div></td>
			<td bgcolor="#f6f6f6" width="86%" height="25"><font size="2">
			<input type="text" name="priv" value="<?php echo $list['priv'] ?? ''; ?>">(회원,운영자 등등)</font></td>
		</tr>
		<tr>
			<td bgcolor="#CCCCCC" width="14%" height="25"><div align="right"><font size="2"><b><font color="#000000">성별</font></b></font></div></td>
			<td bgcolor="#f6f6f6" width="86%" height="25"><font size="2">전체
			<input type="radio" name="sex" value="0" <?php echo (($list['sex'] ?? 0) == 0 ? "checked" : ""); ?>>남자
			<input type="radio" name="sex" value="1" <?php echo (($list['sex'] ?? 0) == 1 ? "checked" : ""); ?>>여자
			<input type="radio" name="sex" value="2" <?php echo (($list['sex'] ?? 0) == 2 ? "checked" : ""); ?>>
			</font></td>
		</tr>
		<tr>
			<td bgcolor="#CCCCCC" width="14%" height="25"><div align="right"><font size="2"><b><font color="#000000">연령별</font></b></font></div></td>
			<td bgcolor="#f6f6f6" width="86%" height="25"><font size="2">
			<select name="age">
				<?php
				$age_options = [
					'0' => '전체', '10/19' => '10대', '20/29' => '20대', '30/39' => '30대',
					'40/49' => '40대', '50/100' => '50대이상', '10/29' => '10대~20대',
					'10/39' => '10대~30대', '10/49' => '10대~40대', '20/39' => '20대~30대',
					'20/49' => '20대~40대', '20/100' => '20대이상', '30/49' => '30대~40대',
					'30/100' => '30대이상', '40/100' => '40대이상'
				];
				$current_age = $list['age'] ?? '0';
				foreach ($age_options as $value => $text) {
					$selected = ($current_age == $value) ? 'selected' : '';
					echo "<option value=\"{$value}\" {$selected}>{$text}</option>";
				}
				?>
			</select>
			</font></td>
		</tr>
		<tr>
			<td bgcolor="#CCCCCC" width="14%" height="26"><div align="right"><b><font color="#000000"><font size="2">투표 시작일</font></font></b></div></td>
			<td bgcolor="#f6f6f6" width="86%" height="26"><font size="2">
			<?php
				$current_y = date('Y');
				generate_date_dropdown('start_time_y', $current_y, $current_y + 2, $Y, '년');
				generate_date_dropdown('start_time_m', 1, 12, $M, '월');
				generate_date_dropdown('start_time_d', 1, $T, $D, '일');
			?>
			</font></td>
		</tr>
		<tr>
			<td bgcolor="#CCCCCC" width="14%"><div align="right"><b><font color="#000000"><font size="2">투표 마감일</font></font></b></div></td>
			<td bgcolor="#f6f6f6" width="86%"><font size="2">
			<?php
				$current_y = date('Y');
				generate_date_dropdown('end_time_y', $current_y, $current_y + 2, $y, '년');
				generate_date_dropdown('end_time_m', 1, 12, $m, '월');
				generate_date_dropdown('end_time_d', 1, $t, $d, '일');
			?>
			</font></td>
		</tr>
		<tr>
			<td bgcolor="#CCCCCC" width="14%"><div align="right"><b><font color="#000000"><font size="2">투표 주제</font></font></b></div></td>
			<td bgcolor="#f6f6f6" width="86%"><textarea name="title" cols="60" rows="5"><?php echo $list['title'] ?? ''; ?></textarea></td>
		</tr>
<?php
// 질문 항목수에 따라... 항목을 나열한다.
for ($i = 1; $i <= 10; $i++) {
	$question_key = "q".$i;
?>
		<tr>
			<td width="14%" bgcolor="#CCCCCC"><div align="right"><b><font color="#000000"><font size="2">항목 <?php echo $i; ?> </font></font></b></div></td>
			<td width="86%" bgcolor="#f6f6f6"><input type="text" name="q<?php echo $i; ?>" size="70" value="<?php echo $list[$question_key] ?? ''; ?>"></td>
		</tr>
<?php
}
?>
		<tr>
			<td width="14%" bgcolor="#CCCCCC" height="50"><div align="right">&nbsp;</div></td>
			<td width="86%" bgcolor="#f6f6f6" height="50"><input type="submit" name="Submit" value="	:: 설문 만들기 ::	"></td>
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
 * 전달된 배열 데이터에서 문자열 타입의 값들에 대해 htmlspecialchars 처리를 적용합니다.
 *
 * @param string $table 대상 테이블 이름.
 * @param array &$list htmlspecialchars를 적용할 데이터 배열 (참조 전달).
 * @return bool 성공 시 true, 실패 시 false.
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