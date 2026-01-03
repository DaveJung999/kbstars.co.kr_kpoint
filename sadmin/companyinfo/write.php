<?php
//=======================================================
// 설	명 : 사이트 회사 정보 입력/수정 - Modernized for PHP 7.4+
// 책임자 : 박선민 (sponsor@new21.com), 검수: 05/02/03
// Project: sitePHPbasic
// ChangeLog
//	DATE	  수정인		  수정 내용
// -------- ---------- --------------------------------------
// 25/08/11 Gemini AI PHP 7.4+ 호환성 업데이트, MySQLi 적용, 보안 강화
// 05/02/03 박선민	마지막 수정
//=======================================================
$HEADER = array(
	'priv' => '운영자', // 인증유무 (비회원,회원,운영자,서버관리자)
	'usedb2' => 1, // DB 커넥션 사용
	'useSkin' => 1, // 템플릿 사용
);
require($_SERVER['DOCUMENT_ROOT'] . '/sinc/header.php');
$thisPath = dirname(__FILE__) . '/'; // 마지막이 '/'으로 끝나야함
$thisUrl = './'; // 마지막이 '/'으로 끝나야함

//=======================================================
// Ready.. . (변수 초기화 및 넘어온값 필터링)
//=======================================================
global $SITE; // $mysqli는 사용하지 않으므로 제거
$table_companyinfo = ($SITE['th'] ?? '') . 'companyinfo'; // 회사정보테이블
$dbinfo = array(
	'table' => $table_companyinfo,
	'enable_upload' => 'Y',
	'enable_uploadextension' => 'gif',
	'goto_modify' => 'write.php'
);
$_GET['mode'] = 'modify';

// db_arrayone 함수를 사용하여 단일 행을 가져옵니다.
$sql = "select * from {$dbinfo['table']} where uid=1";
$list = db_arrayone($sql);

// $list 체크
if ($list) {
	// - 사업자등록번호 나누기
	$list['c_num'] = preg_replace('/[^0-9]/', '', $list['c_num'] ?? '');
	if (strlen($list['c_num']) == 10) {
		$list['c_num1'] = substr($list['c_num'], 0, 3);
		$list['c_num2'] = substr($list['c_num'], 3, 2);
		$list['c_num3'] = substr($list['c_num'], 5);
	} elseif (strlen($list['c_num']) == 14) { // 주민등록번호가 들어가있다면
		$list['c_idnum'] = $list['c_num']; // c_num에 들어가 있는 값으로 주민번호 변경
	}

	// - 주민번호 나누기
	$list['c_idnum'] = preg_replace('/[^0-9]/', '', $list['c_idnum'] ?? '');
	$list['c_idnum1'] = substr($list['c_idnum'] ?? '', 0, 6);
	$list['c_idnum2'] = substr($list['c_idnum'] ?? '', 6);

	// - input value로 넣기 위해
	if ($fieldlist = userGetAppendFields($dbinfo['table'], array('uid'))) {
		foreach ($fieldlist as $value) {
			if (isset($list[$value])) {
				$list[$value] = htmlspecialchars($list[$value], ENT_QUOTES, 'UTF-8');
			}
		}
	}

	// 업로드파일 처리
	if (($dbinfo['enable_upload'] ?? 'N') !== 'N' and isset($list['upfiles'])) {
		$upfiles = @unserialize($list['upfiles']);
		if ($upfiles === false) { // 시리얼화된 변수가 아닌 파일 명으로 되어 있을 경우
			$upfiles['upfile']['name'] = $list['upfiles'];
			$upfiles['upfile']['size'] = (int)($list['upfiles_totalsize'] ?? 0);
		}
		foreach ($upfiles as $key => $value) {
			if (isset($value['name'])) {
				$upfiles[$key]['href'] = '/smember/companytax/download.php?' . href_qs("uid={$list['uid']}&upfile={$key}", ($qs_basic ?? ''));
			}
		} // end foreach
		$list['upfiles'] = $upfiles;
		unset($upfiles);
	} // end if 업로드파일 처리
}

$form_default = "<form method='post' action='{$thisUrl}ok.php' ENCTYPE='multipart/form-data'>";
$form_default .= href_qs('mode=modify&rdate=' . ($_GET['rdate'] ?? ''), ($qs_basic ?? ''), 1);
$form_default = substr($form_default, 0, -1) . ">"; // 폼 태그 닫기 오류 수정

//=======================================================
// Start.. . (DB 작업 및 display)
//=======================================================
// 템플릿 기반 웹 페이지 제작
$skinfile = basename(__FILE__, '.php') . '.html';
if (!is_file($thisPath . 'skin/' . ($dbinfo['skin'] ?? 'basic') . '/' . $skinfile)) {
	$dbinfo['skin'] = 'basic';
}
$tpl = new phemplate($thisPath . 'skin/' . ($dbinfo['skin'] ?? 'basic')); // 템플릿 시작
$tpl->set_file('html', $skinfile, TPL_BLOCK);

// 템플릿 마무리 할당
userEnumSetFieldsToOptionTag($dbinfo['table'], $list); // $list['필드_option']에 enum,set필드 <option>..</option>생성
$tpl->tie_var('list', $list);
$tpl->set_var('form_default', $form_default);

// 마무리
$val = '\\1' . $thisUrl . 'skin/' . ($dbinfo['skin'] ?? 'basic') . '/images/';
echo preg_replace('/([="\'\s])images\//', $val, $tpl->process('', 'html', TPL_OPTIONAL));

//=======================================================
// User functions.. . (사용자 함수 정의)
//=======================================================
/**
 * 추가 입력해야할 필드를 가져옵니다. (Modernized version)
 * @param string $table The table name.
 * @param array $skip_fields Fields to exclude.
 * @return array|false List of additional fields or false on failure.
 */
function userGetAppendFields(string $table, array $skip_fields = [])
{
	if (empty($table)) {
		return false;
	}

	$result = db_query("SHOW COLUMNS FROM {$table}");

	if (!$result) {
		return false;
	}

	$fieldlist = [];
	while($row = db_array($result)) {
		if(!in_array($row['Field'], $skip_fields)){
			$fieldlist[] = $row['Field'];
		}
	}
	db_free($result); 

	return isset($fieldlist) ? $fieldlist : false;
}

/**
 * enum,set필드라면, $list['필드_option'] 만들어줌 (Modernized version)
 * @param string $table
 * @param array &$list
 */
function userEnumSetFieldsToOptionTag(string $table, array &$list){
	// SHOW FIELDS는 db_query를 사용하여 여러 행을 가져옵니다.
	$table_def = db_query("SHOW FIELDS FROM {$table}");
	if (!$table_def) {
		return;
	}

	while ($row_table_def = db_array($table_def)) {
		$field = $row_table_def['Field'];

		// preg_replace 수정: 괄호와 내부 내용만 제거하도록
		$row_table_def['True_Type'] = preg_replace('/\([^\)]*\)/', '', $row_table_def['Type']);

		if ($row_table_def['True_Type'] == 'enum') {
			$aFieldValue = array($list[$field] ?? null);
		} elseif ($row_table_def['True_Type'] == 'set') {
			$aFieldValue = explode(',', $list[$field] ?? '');
		} else {
			continue;
		}

		$return = '';

		// The value column (depends on type)
		// ----------------
		$enum = substr($row_table_def['Type'], strpos($row_table_def['Type'], '(') + 1, -1);
		$enum = explode("','", $enum);

		// show dropdown or radio depend on length
		foreach ($enum as $enum_atom) {
			// Removes automatic MySQL escape format
			$enum_atom = str_replace("''", "'", str_replace('\\\\', '\\', $enum_atom));
			$return .= '<option value="' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . '"';
			if ((isset($list[$field]) && in_array($enum_atom, $aFieldValue))
				or (!isset($list[$field]) && ($row_table_def['Null'] ?? 'YES') != 'YES'
					&& $enum_atom == ($row_table_def['Default'] ?? ''))
			) {
				$return .= ' selected="selected"';
			}
			$return .= '>' . htmlspecialchars($enum_atom, ENT_QUOTES, 'UTF-8') . "</option>\n";
		} // end for
		
		$list[$field . '_option'] = $return;
	} // end for
	db_free($table_def);
} // end function
?>
